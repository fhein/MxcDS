<?php
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropship\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MxcCommons\MxcCommons;
use MxcCommons\Plugin\Service\Logger;
use MxcCommons\Toolbox\Shopware\MailTool;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use Shopware_Components_Config;
use Enlight_Hook_HookArgs;
use Enlight_Components_Db_Adapter_Pdo_Mysql;

class BackendOrderSubscriber implements SubscriberInterface
{
    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db;

    /** @var Logger */
    private $log;

    /** @var Shopware_Components_Config */
    private $config;

    /** @var DropshipManager */
    private $dropshipManager;

    /** @var MailTool */
    private $mailer;

    public function __construct()
    {
        $this->log = MxcDropship::getServices()->get('logger');
        $this->db = Shopware()->Db();
        $this->config = Shopware()->Config();
        $this->dropshipManager = MxcDropship::getServices()->get(DropshipManager::class);
        $this->mailer = MxcCommons::getServices()->get(MailTool::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_Send'          => 'onOrderMailSend',
            'Shopware_Modules_Order_SaveOrder_OrderCreated' => 'onOrderCreated',

            'Enlight_Controller_Action_PostDispatch_Backend_Order'             => 'onBackendOrderPostDispatch',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_MxcDropship' => 'onGetControllerPath',

            // not needed currently
            'Shopware_Controllers_Backend_Order::savePositionAction::after' => 'onSavePositionActionAfter',
            'Shopware_Controllers_Backend_Order::saveAction::after'         => 'onSaveActionAfter',
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
        $this->dropshipManager->initOrder($args->orderId);
    }

    public function onOrderMailSend(Enlight_Event_EventArgs $args)
    {
        $this->log->debug('onOrderMailSend');
        $context = $args->context;
        $context['mailTemplate'] = 'sMxcDsiOrder';
        $this->getMailDeliveryContextInfo($context);
        // note: orderId is not relevant here
        $this->mailer->sendNotificationMail(0, $context, $this->dropshipManager->getMailAddress());
        // Because this is a notifyUntil Event we have to return something falsish if we want Shopware to proceed as default
        // If we would return something different from false, Shopware would not send an order confirmation to the customer
        return null;
    }

    protected function getMailDeliveryContextInfo(array &$context)
    {
        $orderType = 0;
        foreach ($context['sOrderDetails'] as &$detail) {
            if (! array_key_exists('additional_details', $detail)) {
                continue;
            }
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

    // this is the backend gui
    public function onBackendOrderPostDispatch(Enlight_Event_EventArgs $args)
    {
        $request = $args->getRequest();
        $action = $request->getActionName();

        if ($action == 'save') {
            return;
        }
        $view = $args->getSubject()->View();
        if ($action == 'getList') {
            $orderList = $view->getAssign('data');
            foreach ($orderList as &$order) {
                $bullet = $this->getBullet($order);
                $order['mxcbc_dropship_bullet_background_color'] = $bullet['color'];
                $order['mxcbc_dropship_bullet_title'] = $bullet['message'] ?? '';
            }
            $view->clearAssign('data');
            $view->assign('data', $orderList);
        }

        $view->extendsTemplate('backend/mxc_dropship/order/view/detail/overview.js');
        $view->extendsTemplate('backend/mxc_dropship/order/view/list/list.js');
    }

    public function getBullet(array $order)
    {
        $attr = $this->db->fetchAll(
            'SELECT oa.mxcbc_dsi_ordertype as orderType from s_order_attributes oa WHERE oa.orderID = :orderId',
            ['orderId' => $order['id']]
        )[0];
        $orderType = $attr['orderType'];
        $bullet = null;
        if ($orderType & DropshipManager::ORDER_TYPE_OWNSTOCK) {
            $bullet = [
                'color' => 'PaleVioletRed',
                'message' => 'Bestellung enthält Produkte aus eigenem Lager.',
            ];
        }
        return $bullet;
    }

    protected function getPanels()
    {
        return $this->panels ?? $this->panels = MxcDropship::getPanelConfig();
    }
}