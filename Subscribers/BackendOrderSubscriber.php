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
use Throwable;
use sAdmin;

class BackendOrderSubscriber implements SubscriberInterface
{

    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db;

    /** @var Logger */
    private $log;

    /** @var Shopware_Components_Config  */
    private $config;

    protected $panels;

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
            'Enlight_Controller_Action_PostDispatch_Backend_Order'              => 'onBackendOrderPostDispatch',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_MxcDropship'  => 'onGetControllerPath',

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
            // save article detail supplier and product number to order detail
            $sql = '
                UPDATE s_order_details_attributes oda
                SET oda.mxcbc_dsi_supplier = :supplier
                WHERE oda.detailID = :id
            ';
            Shopware()->Db()->executeUpdate($sql,
                [ 'supplier' => $supplier, 'id' => $orderDetailId]);
        }
        // STATUS_OK -> nothing left to do regarding dropship, true if there are no dropship products
        $dropshipStatus = $orderType > DropshipManager::ORDER_TYPE_OWNSTOCK ? DropshipManager::ORDER_STATUS_OPEN : DropshipManager::ORDER_STATUS_SENT;
        $sql = '
            UPDATE s_order_attributes oa
            SET 
                oa.mxcbc_dsi_ordertype = :orderType,
                oa.mxcbc_dsi_status = :dropshipStatus
            WHERE oa.orderID = :orderId
        ';
        $this->db->executeUpdate($sql,
            ['orderType' => $orderType, 'dropshipStatus' => $dropshipStatus, 'orderId' => $args->orderId]
        );
        $sendOrders = MxcDropship::getServices()->get(SendOrders::class);
        $sendOrders->run();
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

    protected function getPanels()
    {
        return $this->panels ?? $this->panels = MxcDropship::getPanelConfig();
    }

    // this is the backend gui
    public function onBackendOrderPostDispatch(Enlight_Event_EventArgs $args)
    {
        $request = $args->getRequest();
        $action = $request->getActionName();

        if ($action == 'save') return;
        $view = $args->getSubject()->View();
        if ($action == 'getList') {
            $panels = $this->getPanels();
            $orderList = $view->getAssign('data');
            foreach ($orderList as &$order) {
                $attr = $this->db->fetchAll(
                    'SELECT * from s_order_attributes oa WHERE oa.orderID = :orderId',
                    ['orderId' => $order['id']]
                )[0];
                $panels = $this->getPanels();
                $status = $attr['mxcbc_dsi_status'];
                $color = $panels[$status]['background'];
                $order['mxcbc_dsi_bullet_background_color'] = $color;
                $order['mxcbc_dsi_bullet_title'] = $panels[$status]['message'];
            }
            $view->clearAssign('data');
            $view->assign('data', $orderList);
        }

        $view->extendsTemplate('backend/mxc_dsi_order/view/detail/overview.js');
        $view->extendsTemplate('backend/mxc_dsi_order/view/list/list.js');



//
//                $buttonStatus = 1;
//                $buttonDisabled = false;
//                $view = $args->getSubject()->View();
//                $orderList = $view->getAssign('data');
//
//                // Check here if dropship-article exist
//                foreach ($orderList as &$order) {
//                    foreach ($order['details'] as $details_key => $details_value) {
//
//                        $attribute = Shopware()->Db()->fetchRow('
//                          SELECT
//                              *
//                          FROM
//                              s_order_details_attributes
//                          WHERE
//                              detailID = ?
//                          ', array($order['details'][$details_key]['id'])
//                        );
//
//                        $order['details'][$details_key]['attribute'] = $attribute;
//
//                        $orderDropshipStatus = $this->getOrderDropshipStatus($order['id']);
//                        $orderDropshipIsActive = $this->getOrderDropshipIsActive($order['id']);
//
//                        $order['dc_dropship_status'] = $orderDropshipStatus;
//                        $order['dc_dropship_active'] = $orderDropshipIsActive;
//
//                        $fullOrder = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($order['id']);
//
//                        if (Shopware()->Config()->get('dc_auto_order')) {
//
//                            if ($fullOrder->getPaymentStatus()->getName() != self::PAYMENT_COMPLETELY_PAID) {
//                                $showEditor = true;
//                                $buttonDisabled = true;
//                                $buttonStatus = 1;
//                            } else if ($orderDropshipIsActive == 1) {
//                                $showEditor = false;
//                                $buttonDisabled = true;
//                                $buttonStatus = 0;
//                            }
//
//                            if ($orderDropshipStatus == 100 || $orderDropshipStatus == 200) {
//                                $showEditor = false;
//                                $buttonDisabled = true;
//                                $buttonStatus = 0;
//                            }
//
//                            if ($orderDropshipStatus == -100) {
//                                $buttonDisabled = false;
//                                $showEditor = true;
//                                $buttonStatus = 3;
//                            }
//
//                        } else {
//
//                            if ($fullOrder->getPaymentStatus()->getName() != self::PAYMENT_COMPLETELY_PAID) {
//                                $showEditor = false;
//                                $buttonDisabled = true;
//                                $buttonStatus = 0;
//                            } else {
//
//                                if ($orderDropshipIsActive == 1) {
//                                    $showEditor = true;
//                                    $buttonDisabled = true;
//                                    $buttonStatus = 0;
//                                }
//
//                                if ($orderDropshipIsActive == 0) {
//                                    $buttonDisabled = false;
//                                    $showEditor = true;
//                                    $buttonStatus = 1;
//                                }
//
//                                if ($orderDropshipStatus == 100 || $orderDropshipStatus == 200) {
//                                    $buttonDisabled = true;
//                                    $buttonStatus = 0;
//                                }
//
//                                if ($orderDropshipStatus == -100) {
//                                    $buttonDisabled = false;
//                                    $showEditor = true;
//                                    $buttonStatus = 3;
//                                }
//                            }
//                        }
//
//
//                        if ($fullOrder->getPaymentStatus()->getName() != self::PAYMENT_COMPLETELY_PAID) {
//                            $bulletColor = 'darkorange';
//                        } else if ($orderDropshipIsActive == 1) {
//                            $bulletColor = 'limegreen';
//                        } else if ($orderDropshipIsActive == 0) {
//                            $bulletColor = 'darkorange';
//                        }
//
//                        if ($orderDropshipStatus == 100 || $orderDropshipStatus == 200) {
//                            $bulletColor = $orderDropshipStatus == 100 ? 'limegreen' : 'dodgerblue';
//                        }
//
//                        if ($orderDropshipIsActive == 1 && $orderDropshipStatus == 200) {
//                            $bulletColor = '#ff0090';
//                        }
//
//                        if ($orderDropshipStatus == -100) {
//                            $bulletColor = 'red';
//                        }
//
//                        if (!empty($order['details'][$details_key]['attribute']['dc_name_short'])) {
//                            $order['is_dropship'] = '<div style="width:16px;height:16px;background:' . $bulletColor . ';color:white;margin: 0 auto;text-align:center;border-radius: 7px;padding-top: 2px;" title="Bestellung mit Dropshipping Artikel">&nbsp;</div>';
//                        }
//
//                        if ($buttonStatus == 1) {
//                            $order['dcUrl'] = './dc/markOrderAsDropship';
//                            $order['dcButtonText'] = 'Dropshipping-Bestellung aufgeben';
//                        } else if ($buttonStatus == 3) {
//                            $order['dcUrl'] = './dc/renewOrderAsDropship';
//                            $order['dcButtonText'] = 'Dropshipping-Bestellung erneut übermitteln';
//                        }
//
//                        $order['viewDCOrderButtonDisabled'] = $buttonDisabled;
//                        $order['viewDCOrderButton'] = $buttonStatus;
//                        $order['viewDCShowEditor'] = $showEditor;
//                    }
//                }
//
//                // Overwrite position data
//                $view->clearAssign('data');
//                $view->assign(
//                    array('data' => $orderList)
//                );
//
//                $view = $args->getSubject()->View();
//                $view->addTemplateDir($this->Path() . 'Views/');
//
//                $view->extendsTemplate('backend/dcompanion/order/store/dc_sources.js');
//                $view->extendsTemplate('backend/dcompanion/order/view/detail/overview.js');
//                $view->extendsTemplate('backend/dcompanion/order/model/position.js');
//                $view->extendsTemplate('backend/dcompanion/order/view/detail/position.js');
//                $view->extendsTemplate('backend/dcompanion/order/view/list/list.js');
//                $view->extendsTemplate('backend/dcompanion/order/model/order.js');
//                break;
    }
}