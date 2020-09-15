<?php

namespace MxcDropship\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Hook_HookArgs;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use MxcVapee\MxcVapee;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Enlight_Controller_Request_RequestHttp;

class FrontendDetailSubscriber implements SubscriberInterface
{
    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db;
    /** @var DropshipManager */
    private $dropshipManager;

    private $modelManager;

    private $log;

    public function __construct()
    {
        $services = MxcDropship::getServices();
        $this->log = $services->get('logger');
        $this->modelManager = $services->get('models');
        $this->db = $services->get('db');
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch_Frontend_Detail' => 'onEnlightControllerActionPreDispatchFrontendDetail',
            'Enlight_Controller_Action_PostDispatch_Frontend_Detail' => 'onEnlightControllerActionPostDispatchFrontendDetail',
        ];
    }

    // Before dispatching the request to the controller we save the current local instock values of all details (if there
    // is more than one) to an article attribute (mxcbc_dsi_instock). We replace the own stock value with the stock information
    // of the attached dropship modules. Doing so enables Shopware to select an available detail (if any) to open and
    // to put into the basket. This replacement supports detail change and page reload.
    public function onEnlightControllerActionPreDispatchFrontendDetail(Enlight_Event_EventArgs $args)
    {
        $details = $this->getDetails($args);
        if (! $details) return;

        // lazy instantiation of dropship manager, does not occur on own stock products
        $this->dropshipManager = MxcDropship::getServices()->get(DropshipManager::class);

        /** @var Detail $detail */
        foreach ($details as $detail) {
            $attr = $detail->getAttribute();
            $this->log->debug('Saving own stock: '. $detail->getInStock());
            // this member function exists only if mxc_dsi_ownstock is a properly registered article attribute
            /** @noinspection PhpUndefinedMethodInspection */
            $attr->setMxcbcDsiOwnstock($detail->getInStock());
            $newStock = $this->getDetailStock($detail);
            $detail->setInStock($newStock);
            $this->log->debug('Temporarily setting stock to ' . $newStock);
        }
        $this->modelManager->flush();
    }

    // After the index request is done, we restore the local instock information.
    public function onEnlightControllerActionPostDispatchFrontendDetail(Enlight_Event_EventArgs $args)
    {
        // Note: using the object interface seems to invalidate the cache making sure that we get called the next time
        // a customer opens the detail view
        $details = $this->getDetails($args);
        if (! $details) return;
        /** @var Detail $detail */
        foreach ($details as $detail) {
            $attr = $detail->getAttribute();
            // this member function exists only if mxc_dsi_ownstock is a properly registered article attribute
            /** @noinspection PhpUndefinedMethodInspection */
            $ownStock = $attr->getMxcbcDsiOwnstock();
            $detail->setInStock($ownStock);
            $this->log->debug('Restoring detail stock to:  '. $ownStock);
        }
        $this->modelManager->flush();
    }

    // get stock info for given detail
    public function getDetailStock(Detail $detail)
    {
        $detail = $this->db->fetchAll('
            SELECT d.instock, d.minpurchase, d.laststock, aa.* FROM s_articles_details d 
            LEFT JOIN s_articles_attributes aa ON aa.articledetailsID = d.id
            WHERE d.id = ?
        ', [$detail->getId()]);
        if (empty($detail)) return 0;
        $detail = $detail[0];

        // query the attached dropship adapters for stock
        $stockData = $this->dropshipManager->getStockInfo($detail, true);

        $lastStock = $detail['lastStock'];
        $instock = $detail['instock'];
        $minPurchase = $detail['minpurchase'];

        $this->log->debug('Stock data');
        $this->log->debug(var_export($stockData, true));

        foreach ($stockData as $stockInfo) {
            $mode = $stockInfo['mode'];
            switch (true) {
                case $mode == DropshipManager::MODE_OWNSTOCK_ONLY:
                    return $instock;
                case $mode != DropshipManager::MODE_DROPSHIP_ONLY:
                    if ($lastStock * $instock >= $lastStock * $minPurchase) {
                        return $instock;
                    }
                    break;
                default:
                    // dropship only mode
                    $dsInstock = $stockInfo['instock'];
                    if ($lastStock * $dsInstock >= $lastStock * $minPurchase) {
                        return $dsInstock;
                    }
                    break;
            }
        }
        return 0;
    }

    // Get details to process
    //
    // returns null if action name != index or there is only one detail
    // returns a collection of detail objects to process otherwise
    private function getDetails(Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Request_RequestHttp $request*/
        $request = $args->getRequest();
        $action = $request->getActionName();
        if ($action != 'index') {
            $this->log->debug('Frontend detail request. Action is ' . $action . '. Nothing done.' );
            return null;
        }

        // get requested article id
        $article = $this->modelManager->getRepository(Article::class)->find($request->sArticle);
        $this->log->debug ('Frontend detail request for ' . $article->getName());
        $details = $article->getDetails();
        if ($details->count() < 2) {
            $this->log->debug('Only one detail. Nothing done.');
            return null;
        }
        // do nothing if product's delivery mode is set to own stock
        $detail = $details[0];
        $attr = $detail->getAttribute();
        $deliveryMode = $attr->getMxcbcDsiMode() ?? DropshipManager::MODE_OWNSTOCK_ONLY;
        if ($deliveryMode == DropshipManager::MODE_OWNSTOCK_ONLY) {
            $this->log->debug('Delivery from own stock. Nothing done.');
            return null;
        }

        return $details;
    }

    // ***!***
    // Old stuff beginning here (kept for lookup reasons only, not needed any longer

    public function onFrontendDetailIndexAfter(Enlight_Hook_HookArgs $args)
    {
        $view = $args->getSubject()->View();
        $sArticle = $view->getAssign('sArticle');
        $stockInfo = $this->dropshipManager->getStockInfo($sArticle);
        $this->enableArticle($sArticle, $stockInfo);

        // if this is an article with variants setup variants also
        if (! empty($sArticle['sConfigurator'])) {
            // Article with variants
            $details = $this->getArticleDetails($sArticle['articleID']);
            foreach ($details as $detail) {
                $stockInfo = $this->dropshipManager->getStockInfo($detail);
                $active = intval(! empty($stockInfo) && $detail['active'] == 1);
                $this->enableConfiguratorOption($detail, $sArticle, $active);
            }
        }
        $view->assign('sArticle', $sArticle);
    }

    protected function enableArticle(array &$sArticle, array $stockInfo)
    {
        if (! empty($stockInfo)) {
            $instock = max(array_column($stockInfo, 'instock'));
            $sArticle['isAvailable'] = 1;
            $sArticle['instock'] = $instock;
            // @todo: Do we have to provide maxpurchase also here?
        }
    }

    protected function enableConfiguratorOption(array $article, array &$sArticle, int $active)
    {
        foreach ($sArticle['sConfigurator'] as &$sConfiguratorList) {
            foreach ($sConfiguratorList['values'] as &$sConfiguratorValues) {
                if (
                    $article['group_id'] == $sConfiguratorValues['groupID']
                    && $article['option_id'] == $sConfiguratorValues['optionID']
                ) {
                    $sConfiguratorValues['selectable'] = $active;
                }
            }
        }
    }

    private function getArticleDetails($articleId) {
        return Shopware()->Db()->fetchAll('
            SELECT * FROM s_articles_details 
            LEFT JOIN s_article_configurator_option_relations 
                ON s_article_configurator_option_relations.article_id = s_articles_details.id
            LEFT JOIN s_article_configurator_options 
                ON s_article_configurator_options.id = s_article_configurator_option_relations.option_id 
            LEFT JOIN s_articles_attributes 
                ON s_articles_attributes.articledetailsID = s_articles_details.id 
            WHERE 
              s_articles_details.articleID = ?
            ', array($articleId)
        );
    }

}