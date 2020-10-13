<?php
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropship\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MxcCommons\Plugin\Service\Logger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Jobs\SendOrders;
use MxcDropship\MxcDropship;
use Shopware\Models\Mail\Mail;
use Shopware_Components_Config;
use Enlight_Hook_HookArgs;
use Enlight_Components_Db_Adapter_Pdo_Mysql;

class BackendOrderSubscriber implements SubscriberInterface
{

    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db;

    /** @var Logger */
    private $log;

    /** @var Shopware_Components_Config  */
    private $config;

    public function __construct()
    {
        $this->log = MxcDropship::getServices()->get('logger');
        $this->db = Shopware()->Db();
        $this->config = Shopware()->Config();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_Send'                              => 'onOrderMailSend',
            'Shopware_Modules_Order_SaveOrder_OrderCreated'                     => 'onOrderCreated',

// next two moved to MxcDropshipInnocigs
//            'Enlight_Controller_Action_PostDispatch_Backend_Order'              => 'onBackendOrderPostDispatch',
//            'Enlight_Controller_Dispatcher_ControllerPath_Backend_MxcDropship'  => 'onGetControllerPath',

//            'Shopware_Controllers_Backend_Order::savePositionAction::after' => 'onSavePositionActionAfter',
//            'Shopware_Controllers_Backend_Order::saveAction::after'         => 'onSaveActionAfter',
        ];
    }

    public function onGetControllerPath(Enlight_Event_EventArgs $args)
    {
        return MxcDropship::PLUGIN_DIR . '/Controllers/Backend/MxcDropship.php';

    }

    // Gets triggered after a position gets saved from the positions tab of a backend order
    // Saves GUI modified values of instock, supplier and purchasePrice
    // **!** Probably obsolete
    public function onSavePositionActionAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('onSavePositionAfter');
//        $params = $args->getSubject()->Request()->getParams();
//
//        $this->db->Query('
//            UPDATE
//                s_order_details_attributes
//            SET
//                mxcbc_dsi_supplier = :supplier,
//                mxcbc_dsi_instock = :instock,
//                mxcbc_dsi_purchaseprice = :purchasePrice
//            WHERE
//                id = :id
//            ', [
//                'id'            => $params['id'],
//                'instock'       => $params['mxcbc_dsi_instock'],
//                'supplier'      => $params['mxcbc_dsi_supplier'],
//                'purchasePrice' => $params['mxcbc_dsi_purchaseprice'],
//            ]
//        );
    }

    // Gets triggered after an order was saved via the backend module
    // Saves GUI modified values of active and status
    // **!** Probably obsolete
    public function onSaveActionAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('onSaveActionAfter');
//        $params = $args->getSubject()->Request()->getParams();
//        $active = $params['mxcbc_dsi_active'];
//
//        if ($params['cleared'] === Status::PAYMENT_STATE_COMPLETELY_PAID) {
//            $active = $this->dropshipManager->isAuto();
//        }
//
//        $this->db->Query('
//			UPDATE
//            	s_order_attributes
//			SET
//            	mxcbc_dsi_active = :active,
//				mxcbc_dsi_status = :status
//			WHERE
//				orderID = :id
//		', [
//            'id'     => $params['id'],
//            'active' => $active,
//            'status' => $params['mxcbc_dsi_status'],
//        ]);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onOrderCreated(Enlight_Event_EventArgs $args)
    {
        $this->log->debug('onOrderCreated');
        $orderType = 0;
        foreach ($args->details as $idx => $item) {
            $articleId = $item['articleID'];
            if (empty($articleId)) continue;
            $orderDetailId = $item['orderDetailId'];
            $attr = $item['additional_details'];
            $supplier = $attr['mxcbc_dsi_supplier'];
            if ($supplier === null) {
                $orderType |= DropshipManager::ORDER_TYPE_OWNSTOCK;    // product from own stock
                $supplier = $this->config->get('shopName');
            } else {
                $orderType |= DropshipManager::ORDER_TYPE_DROPSHIP;    // dropship product
            }
            // update order detail supplier from article detail
            $this->setOrderDetailSupplier($supplier, $orderDetailId);
        }
        $this->setOrderTypeAndStatus($orderType, $args);
        if ($orderType > DropshipManager::ORDER_TYPE_OWNSTOCK) {
            // lazily load $dropshipManager for performance reasons
            $dropshipManager = MxcDropship::getServices()->get(DropshipManager::class);
            $dropshipManager->initOrder($args->orderId);
        }
//        $sendOrders = MxcDropship::getServices()->get(SendOrders::class);
//        $sendOrders->run();
    }

    public function onOrderMailSend(Enlight_Event_EventArgs $args)
    {
        $this->log->debug('onOrderMailSend');
        $context = $args->context;
        $this->getMailDeliveryContextInfo($context);
        /** @var Mail $dsMail */
        $dsMail = Shopware()->Models()->getRepository(Mail::class)->findOneBy(['name' => 'sMxcDsiOrder']);
        if ($dsMail) {
            /** @var \Enlight_Components_Mail $dsMail */
            $dsMail = Shopware()->TemplateMail()->createMail('sMxcDsiOrder', $context);
            $dsMail->addTo('support@vapee.de');
            $dsMail->clearFrom();
            $dsMail->setFrom('info@vapee.de', 'vapee.de Dropship');
            $dsMail->send();
        }
        // Because this is a notifyUntil Event we have to return something falsish if we want Shopware to proceed as default
        // If we would return something different from false, Shopware would not send an order confirmation to the customer
        return null;
    }

    protected function getMailDeliveryContextInfo(array &$context)
    {
        $orderType = 0;
        foreach ($context['sOrderDetails'] as &$detail) {
            if (! array_key_exists('additional_details', $detail)) continue;
            $supplier = $detail['additional_details']['mxcbc_dsi_supplier'];
            if ($supplier === null) {
                $detail['additional_details']['mxcbc_dsi_supplier'] = $this->config->get('shopName');
                $orderType |= 1;
            } else {
                $orderType |= 2;
            }
        }
        // $orderType values to use in email template
        //      0: can not occur, we do not check this error condition
        //      1: only products from own stock
        //      2: only products from dropship partner(s)
        //      3: products from both own stock and dropship partners
        $context['mxcbc_dsi']['orderType'] = $orderType;
    }

    protected function setOrderDetailSupplier($supplier, $orderDetailId): void
    {
        Shopware()->Db()->executeUpdate('
            UPDATE s_order_details_attributes oda
            SET oda.mxcbc_dsi_supplier = :supplier
            WHERE oda.detailID = :id
            ', [
                'supplier' => $supplier,
                'id'       => $orderDetailId
            ]
        );
    }

    protected function setOrderTypeAndStatus(int $orderType, Enlight_Event_EventArgs $args): void
    {
        // If the order does not contain dropship products, drophship status is 'closed'
        if ($orderType > DropshipManager::ORDER_TYPE_OWNSTOCK) {
            $dropshipStatus = DropshipManager::DROPSHIP_STATUS_OPEN;
            $dropshipMessage = 'Neue Dropship-Bestellung.';
        } else {
            $dropshipStatus = DropshipManager::DROPSHIP_STATUS_CLOSED;
            $dropshipMessage = 'Neue Bestellung ohne Dropship-Artikel.';
        }
        $this->db->executeUpdate('
            UPDATE s_order_attributes oa
            SET 
                oa.mxcbc_dsi_ordertype = :orderType,
                oa.mxcbc_dsi_status = :dropshipStatus,
                oa.mxcbc_dsi_message = :dropshipMessage
                
            WHERE oa.orderID = :orderId
        ', [
                'orderType'       => $orderType,
                'dropshipStatus'  => $dropshipStatus,
                'dropshipMessage' => $dropshipMessage,
                'orderId'         => $args->orderId,
            ]
        );
    }
}