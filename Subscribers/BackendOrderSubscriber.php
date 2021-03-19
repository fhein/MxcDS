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
use MxcWorkflow\MxcWorkflow;
use MxcWorkflow\Workflow\WorkflowEngine;
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

    /** @var array */
    private $panels;

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
            'Enlight_Controller_Action_PreDispatch_Backend_Order'              => 'onBackendOrderPreDispatch',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_MxcDropship' => 'onGetControllerPath',

            // recalculate the dropship settings if the order gets modified in the backend
            'Shopware_Controllers_Backend_Order::savePositionAction::after'    => 'onSavePositionActionAfter',
            'Shopware_Controllers_Backend_Order::deletePositionAction::after'  => 'onDeletePositionActionAfter',
            'Shopware_Controllers_Backend_Order::saveAction::after'            => 'onSaveActionAfter',
        ];
    }

    public function onGetControllerPath(Enlight_Event_EventArgs $args)
    {
        return MxcDropship::PLUGIN_DIR . '/Controllers/Backend/MxcDropship.php';

    }

    // Gets triggered after a position gets deleted from the positions tab of a backend order
    // Reinitialize dropship configuration
    public function onDeletePositionActionAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('onDeletePositionAfter');
        $params = $args->getSubject()->Request()->getParams();
        $this->dropshipManager->initOrder($params['orderId']);
    }

    // Gets triggered after a position gets saved from the positions tab of a backend order
    // Reinitialize dropship configuration
    public function onSavePositionActionAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('onSavePositionAfter');
        $params = $args->getSubject()->Request()->getParams();
        $this->dropshipManager->initOrder($params['orderId']);
    }

    // Gets triggered after an order was saved via the backend module
    // Reinitialize dropship configuration
    // Probably obsolete because PreDispatch gets called afterwards
    public function onSaveActionAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('onSaveActionAfter');
        $params = $args->getSubject()->Request()->getParams();
        $this->dropshipManager->initOrder($params['id']);

        // immediate workflow disabled because of concurrency with cronjob

//        if (class_exists(WorkflowEngine::class)) {
//            $engine = MxcWorkflow::getServices()->get(WorkflowEngine::class);
//            $engine->run();
//        }
    }

    // Reinitialize dropship configuration of all open dropship orders if action is getList
    public function onBackendOrderPreDispatch(Enlight_Event_EventArgs $args)
    {
        $action = $args->getRequest()->getActionName();
        if ($action != 'getList') return;
        $orderIds = $this->getOpenDropshipOrderIds();
        foreach ($orderIds as $orderId) {
            $this->dropshipManager->initOrder($orderId);
        }
    }

    // Initialize dropship configuration
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

    public function onBackendOrderPostDispatch(Enlight_Event_EventArgs $args)
    {
        $request = $args->getRequest();
        $action = $request->getActionName();
        $view = $args->getSubject()->View();

        switch ($action) {
            case 'save':
                return;

            case 'load':
                $view->extendsTemplate('backend/mxc_dropship/order/view/detail/overview.js');
                $view->extendsTemplate('backend/mxc_dropship/order/view/list/list.js');
                break;

            case 'getList':
                $orderList = $view->getAssign('data');
                foreach ($orderList as &$order) {
                    $bullet = $this->getBullet($order);
                    $order['mxcbc_dropship_bullet_background_color'] = $bullet['color'];
                    $order['mxcbc_dropship_bullet_title'] = $bullet['message'] ?? '';
                    $order['mxcbc_dsi_status'] = $this->getDropshipStatus($order['id']);
                }
                $view->clearAssign('data');
                $view->assign('data', $orderList);
                break;
        }
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
                'color'   => 'PaleVioletRed',
                'message' => 'Bestellung enthält Produkte aus eigenem Lager.',
            ];
        }
        return $bullet;
    }

    protected function getPanels()
    {
        return $this->panels ?? $this->panels = MxcDropship::getPanelConfig();
    }

    // return the ids of all orders where dropship status is either open or error
    // error state applies only to orders which failed to get sent
    protected function getOpenDropshipOrderIds()
    {
        return $this->db->fetchCol('
            SELECT o.id FROM s_order o LEFT JOIN s_order_attributes oa ON oa.orderID = o.id 
            WHERE oa.mxcbc_dsi_status = :dropshipStatus OR oa.mxcbc_dsi_status > :errorStatus 
        ', [
            'dropshipStatus' => DropshipManager::DROPSHIP_STATUS_OPEN,
            'errorStatus'    => DropshipManager::DROPSHIP_STATUS_ERROR,
        ]);
    }

    protected function getDropshipStatus(int $orderId)
    {
        return $this->db->fetchCol('
            SELECT oa.mxcbc_dsi_status FROM s_order_attributes oa WHERE oa.orderID = :orderId
        ' , [
            'orderId' => $orderId
        ]);
    }
}