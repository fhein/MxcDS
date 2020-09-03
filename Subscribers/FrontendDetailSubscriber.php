<?php

namespace MxcDropship\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;

class FrontendDetailSubscriber implements SubscriberInterface
{
    protected $dropshipManager;

    public function __construct()
    {
        $this->dropshipManager = MxcDropship::getServices()->get(DropshipManager::class);
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Detail::indexAction::after' => 'onFrontendDetailIndexAfter',
        ];
    }

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