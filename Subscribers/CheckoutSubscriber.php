<?php

namespace MxcDropship\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventManager;
use Enlight_Hook_HookArgs;
use MxcCommons\Plugin\Service\Logger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use sBasket;
use Shopware_Components_Config;

class CheckoutSubscriber implements SubscriberInterface
{
    /** @var DropshipManager */
    protected $dropshipManager;

    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var sBasket */
    protected $basket;

    /** @var Enlight_Components_Session_Namespace */
    protected $session;

    /** @var Enlight_Event_EventManager */
    protected $events;

    /** @var Logger object  */
    protected $log;

    /** @var Shopware_Components_Config  */
    protected $config;

    public function __construct()
    {
        $services = MxcDropship::getServices();
        $this->dropshipManager = $services->get(DropshipManager::class);
        $this->db = Shopware()->Db();
        $this->basket = Shopware()->Modules()->Basket();
        $this->session = Shopware()->Session();
        $this->events = Shopware()->Events();
        $this->log = $services->get('logger');
        $this->config = Shopware()->Config();
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Checkout::ajaxCartAction::after'           => 'onFrontendCheckoutAjaxCartAfter',
            'Shopware_Controllers_Frontend_Checkout::ajaxAddArticleCartAction::after' => 'onFrontendCheckoutAjaxAddArticleCartAfter',
            'Shopware_Controllers_Frontend_Checkout::cartAction::after'               => 'onFrontendCheckoutCartAfter',
            'Shopware_Controllers_Frontend_Checkout::confirmAction::after'            => 'onFrontendCheckoutConfirmAfter',
            'sBasket::sCheckBasketQuantities::replace'                                => 'onCheckBasketQuantitiesReplace',
        ];
    }

    public function onFrontendCheckoutAjaxCartAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('Event: onFrontendCheckoutAjaxCartAfter');
        $view = $args->getSubject()->View();
        $sBasket = $view->sBasket;

        // Iterate the basket
        foreach ($sBasket['content'] as $idx => $item) {
            $sArticle = $item['additional_details'];
            if (isset($item['instock'])) {
                $stockInfo = $this->dropshipManager->getStockInfo($sArticle);
                if (! empty($stockInfo)) {
                    $sBasket['content'][$idx]['instock'] = max(array_column($stockInfo, 'instock'));
                }
            }
        }

        $view->assign('sBasket', $sBasket);
    }

    public function onFrontendCheckoutAjaxAddArticleCartAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('Event: onFrontendCheckoutAjaxAddArticleCartAfter');
        // Get basketmessage
        $view = $args->getSubject()->View();

        $productNumber = $args->getSubject()->Request()->getParam('sAdd');
        $quantity = $args->getSubject()->Request()->getParam('sQuantity');

        // Get the dc-article-attributes
        $attributes = $this->getArticleAttributes($productNumber);

        $stockInfo = $this->dropshipManager->getStockInfo($attributes);

        if (! empty($stockInfo)) {
            $view->assign('basketInfoMessage', null);
            $originalInStock = $this->getArticleStock($productNumber);
            if ($attributes['laststock'] == 1 && $attributes['instock'] <= 0) {
                $instock = max(array_column($stockInfo, 'instock'));
                $this->setArticleStock($productNumber, $instock);
                $this->basket->sAddArticle($productNumber, $quantity);
                $this->setArticleStock($productNumber, $originalInStock);
            }
        }
    }

    /**
     * @param Enlight_Hook_HookArgs $args
     */
    protected function onCheckoutCartAfter(Enlight_Hook_HookArgs $args): void
    {
        $view = $args->getSubject()->View();
        $sBasket = $view->sBasket;

        foreach ($sBasket['content'] as $idx => &$item) {
            $attributes = &$item['additional_details'];
            if (isset($item['instock'])) {
                $stockInfo = $this->dropshipManager->getStockInfo($attributes);
                $maxPurchase = $this->config->get('maxpurchase');
                if (! empty($stockInfo)) {
                    $instock = strval(max(array_column($stockInfo, 'instock')));
                    $item['instock'] = $attributes['instock'] = $instock;
                    $item['maxpurchase'] = $attributes['maxpurchase'] = min($instock, $maxPurchase);
                }
            }
        }
        $view->assign('sBasket', $sBasket);
    }

    public function onFrontendCheckoutCartAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('Event: onFrontendCheckoutCartAfter');
        $this->onCheckoutCartAfter($args);
    }

    public function onFrontendCheckoutConfirmAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('Event: onFrontendCheckoutConfirmAfter');
        $this->onCheckoutCartAfter($args);
    }

    public function onCheckBasketQuantitiesReplace(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('Event: onCheckBasketQuantitiesReplace');
        $result = $this->db->fetchAll(
            'SELECT d.instock, b.quantity, b.ordernumber,
                d.laststock, IF(a.active=1, d.active, 0) as active
            FROM s_order_basket b
            LEFT JOIN s_articles_details d
              ON d.ordernumber = b.ordernumber
              AND d.articleID = b.articleID
            LEFT JOIN s_articles a
              ON a.id = d.articleID
            WHERE b.sessionID = ?
              AND b.modus = 0
            GROUP BY b.ordernumber',
            [$this->session->get('sessionId')]
        );
        $hideBasket = false;
        $products = [];
        foreach ($result as $product) {
            $attributes = $this->getArticleAttributes($product['ordernumber']);
            $stockInfo = $this->dropshipManager->getStockInfo($attributes);
            $stock = max(array_column($stockInfo, 'instock'));
            $diffStock = max($product['instock'], $stock - $product['quantity']);
            if (empty($product['active'])
                || (! empty($product['laststock']) && $diffStock < 0)
            ) {
                $hideBasket = true;
                $products[$product['ordernumber']]['OutOfStock'] = true;
            } else {
                $products[$product['ordernumber']]['OutOfStock'] = false;
            }
        }

        $products = $this->events->filter('Shopware_Modules_Basket_CheckBasketQuantities_ProductsQuantity', $products, [
            'subject'    => $this,
            'hideBasket' => $hideBasket,
        ]);

        return ['hideBasket' => $hideBasket, 'articles' => $products];
    }

    private function getArticleAttributes($productNumber)
    {
        return $this->db->fetchRow(
            'SELECT * FROM s_articles_details as sad '
            . 'LEFT JOIN s_articles_attributes as saa '
            . 'ON saa.articledetailsID = sad.id '
            . 'WHERE sad.ordernumber = ?',
            [$productNumber]
        );
    }

    private function getArticleStock($productNumber)
    {
        return $this->db->fetchOne(
            'SELECT instock FROM s_articles_details WHERE ordernumber = ?',
            [$productNumber]
        );
    }

    private function setArticleStock($productNumber, $stock)
    {
        return $this->db->Query(
            'UPDATE s_articles_details SET instock = ? WHERE ordernumber = ?',
            [$stock, $productNumber]
        );
    }
}