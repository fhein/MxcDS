<?php
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropship\Subscribers;

use Doctrine\ORM\EntityManager;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Exception;
use MxcCommons\Plugin\Service\Logger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use Shopware\Models\Mail\Mail;
use Shopware_Components_Config;
use Shopware\Models\Order\Order;
use Enlight_Hook_HookArgs;
use Shopware\Models\Order\Status;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Throwable;
use sAdmin;

class BackendOrderSubscriber implements SubscriberInterface
{
    /** @var EntityManager */
    private $modelManager;

    /** @var DropshipManager */
    private $dropshipManager;

    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db;

    /** @var Logger */
    private $log;

    /** @var Shopware_Components_Config  */
    private $config;

    public function __construct()
    {
        $services = MxcDropship::getServices();
        $this->modelManager = $services->get('models');
        $this->log = $services->get('logger');
        $this->dropshipManager = $services->get(DropshipManager::class);
        $this->db = Shopware()->Db();
        $this->config = Shopware()->Config();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_Send'                          => 'onOrderMailSend',
            'Shopware_Modules_Order_SaveOrder_OrderCreated'                 => 'onOrderCreated',
            // 'Enlight_Controller_Action_PostDispatch_Backend_Order'          => 'onBackendOrderPostDispatch',
            'Shopware_Modules_Order_SaveOrder_ProcessDetails'               => 'onSaveOrderProcessDetails',
            'Shopware_Controllers_Backend_Order::savePositionAction::after' => 'onSavePositionActionAfter',
            'Shopware_Controllers_Backend_Order::saveAction::after'         => 'onSaveActionAfter',
        ];
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
        $this->log->debug('onSaveOrderProcessDetails');
        $order = $args->getSubject();
        foreach ($args->details as $idx => $item) {
            $orderDetailId = $item['orderDetailId'];
            $article = $item['additional_details'];
            $supplier = $article['mxcbc_dsi_supplier'];
            // save article detail supplier id to order detail
            $sql = '
                UPDATE s_order_details_attributes oda 
                SET oda.mxcbc_dsi_supplier = :supplier
                WHERE oda.detailID = :id
            ';
            Shopware()->Db()->executeUpdate($sql,
                [ 'supplier' => 'InnoCigs', 'id' => $orderDetailId]);
        }
    }

    public function onOrderMailSend(Enlight_Event_EventArgs $args)
    {
        $context = $args->context;
        $context = $this->getOrderInfos($context);
        /** @var Mail $dsMail */
        $dsMail = Shopware()->Models()->getRepository(Mail::class)->findOneBy(['name' => 'sMxcOrder']);
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

    public function getOrderInfos(array $context)
    {
        $orderType = 0;
        foreach ($context['sOrderDetails'] as &$detail) {
            if (! isset($detail['additional_details']['mxcbc_dsi_supplier'])) continue;
            $supplier = $detail['additional_details']['mxcbc_dsi_supplier'];
            if ($supplier === null) {
                $detail['additional_details']['mxcbc_dsi_supplier'] = 'vapee.de';
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
        return $context;
    }

    // this is the backend gui
    public function onBackendOrderPostDispatch(Enlight_Event_EventArgs $args)
    {
//        switch ($args->getRequest()->getActionName()) {
//            case 'save':
//                return true;
//                break;
//            default:
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
//                // Add tempolate-dir
//                $view = $args->getSubject()->View();
//                $view->addTemplateDir(
//                    $this->Path() . 'Views/'
//                );
//
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/store/dc_sources.js'
//                );
//
//                // Extends the extJS-templates
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/view/detail/overview.js'
//                );
//
//                // Extends the extJS-templates
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/model/position.js'
//                );
//
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/view/detail/position.js'
//                );
//
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/view/list/list.js'
//                );
//
//                $view->extendsTemplate(
//                    'backend/dcompanion/order/model/order.js'
//                );
//                $this->__logger('return: ' . $args->getReturn());
//                break;
//        }
    }
}