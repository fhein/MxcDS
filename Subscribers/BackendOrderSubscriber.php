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
        $this->log->debug('BackendOrderSubscriber loaded.');
        $this->config = Shopware()->Config();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            //            'Enlight_Controller_Action_PostDispatch_Backend_Order'          => 'onBackendOrderPostDispatch',
            //'Shopware_Modules_Order_SaveOrder_ProcessDetails'               => 'onSaveOrderProcessDetails',
            'Shopware_Controllers_Backend_Order::savePositionAction::after' => 'onSavePositionActionAfter',
            'Shopware_Controllers_Backend_Order::saveAction::after'         => 'onSaveActionAfter',
        ];
    }

    public function onSavePositionActionAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('onSavePositionAfter');
        $params = $args->getSubject()->Request()->getParams();

        $this->db->Query('
            UPDATE
                s_order_details_attributes
            SET
                mxcbc_dsi_supplier = :supplier,
                mxcbc_dsi_instock = :instock,
                mxcbc_dsi_purchaseprice = :purchasePrice
            WHERE
                id = :id
            ', [
                'id'            => $params['id'],
                'instock'       => $params['mxcbc_dsi_instock'],
                'supplier'      => $params['mxcbc_dsi_supplier'],
                'purchasePrice' => $params['mxcbc_dsi_purchaseprice'],
            ]
        );
    }

    public function onSaveActionAfter(Enlight_Hook_HookArgs $args)
    {
        $this->log->debug('onSaveActionAfter');
        $params = $args->getSubject()->Request()->getParams();
        $active = $params['mxcbc_dsi_active'];

        if ($params['cleared'] === Status::PAYMENT_STATE_COMPLETELY_PAID) {
            $active = $this->dropshipManager->isAuto();
        }

        $this->db->Query('
			UPDATE
            	s_order_attributes
			SET
            	mxcbc_dsi_active = :active,
				mxcbc_dsi_status = :status
			WHERE
				orderID = :id
		', [
            'id'     => $params['id'],
            'active' => $active,
            'status' => $params['mxcbc_dsi_status'],
        ]);
    }

    /**
     * @param $order
     * @param array $paymentData
     * @throws Enlight_Exception
     */
    protected function sendMail($order, array $paymentData): void
    {
        if (Shopware()->Config()->get('dc_mail_send')) {
            $mail = Shopware()->Models()->getRepository('\Shopware\Models\Mail\Mail')->findOneBy(['name' => 'DC_ORDER']);
            if ($mail) {

                $context = [
                    'orderNumber'   => $order->sOrderNumber,
                    'dc_auto_order' => Shopware()->Config()->get('dc_auto_order'),
                    'payment'       => [
                        'name'        => $paymentData['name'],
                        'description' => $paymentData['description'],
                    ],
                ];

                $mail = Shopware()->TemplateMail()->createMail('DC_ORDER', $context);
                $mail->addTo(Shopware()->Config()->get('mail'));

                $dcMailRecipients = $this->getConfigCcRecipients();
                if (! empty($dcMailRecipients)) {
                    foreach ($dcMailRecipients as $recipient) {
                        $mail->addCc($recipient);
                    }
                }

                $mail->send();
            }
        }
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onSaveOrderProcessDetails(Enlight_Event_EventArgs $args)
    {
        $order = $args->getSubject();
        foreach ($args->details as $idx => $item) {
            $sArticle = $item['additional_details'];
            if (isset($item['instock'])) {
                $stockInfo = $this->dropshipManager->getStockInfo($sArticle);
                // If dropship stock-value has item, get the item with max instock
                if (! empty($stockInfo)) {
                    $orderDetailsId = $item['orderDetailId'];

                    // @todo: Necessary or available already?
                    $orderId = $this->getOrderIdByOrderDetailsId(
                        $orderDetailsId,
                        $item['ordernumber']
                    );

                    // get supplier with the biggest stock
                    // @todo: This is nasty in many ways: Why the biggest stock?
                    // @todo: Implement getSupplier(productId) instead, returning the according supplierId and stock
                    $supplierId = null;
                    $maxStock = max(array_column($stockInfo, 'instock'));
                    foreach ($stockInfo as $stock) {
                        if ($maxStock === $stock['instock']) {
                            $supplierId = $stock['supplierId'];
                            break;
                        }
                    }
                    $this->setOrderDetailSupplierAndStock($orderDetailsId, $supplierId, $maxStock);

                    // @todo: Pickware support
                    if ($this->dropshipManager->isAuto()) {
                        $this->adjustArticleDetailStockAndSales($item['quantity'], $item['ordernumber']);
                    }

                    // @todo: this needs to be called once only, not throughout the loop
                    $this->setDropshipOrder($orderId);
                    $admin = Shopware()->Modules()->Admin();

                    // @todo: The payment data is equal for all positions, so this has to move out of the loop
                    if (! empty($order->sUserData['additional']['payment']['id'])) {
                        $paymentId = $order->sUserData['additional']['payment']['id'];
                    } else {
                        $paymentId = $order->sUserData['additional']['user']['paymentID'];
                    }
                    $userData = $admin->sGetUserData();
                    $paymentData = $admin->sGetPaymentMeanById($paymentId, $userData);

                    // @todo: This will send a mail for each order position which is bullshit
                    $this->sendMail($order, $paymentData);
                }
            }
        }
    }

    private function setOrderDetailSupplierAndStock($id, $supplierId, $instock)
    {
        return Shopware()->Db()->Query('
            UPDATE
                s_order_details_attributes
            SET
                mxcbc_dsi_supplier = :supplierId,
                mxcbc_dsi_instock = :instock
            WHERE
                detailID = :id
        ', [
            'id'         => $id,
            'supplierId' => $supplierId,
            'instock'    => $instock,
        ]);
    }

    private function adjustArticleDetailStockAndSales(string $productNumber, int $quantity)
    {
        Shopware()->Db()->Query('
            UPDATE 
                s_articles_details
            SET sales = sales - :quantity,
                instock = instock + :quantity
            WHERE 
                ordernumber = :number
            ', [
                'quantity' => $quantity,
                'number'   => $productNumber,
            ]
        );
    }

    private function setDropshipOrder($orderId)
    {

        return Shopware()->Db()->Query('
          UPDATE
            s_order_attributes
          SET
            dc_dropship_active = :active
          WHERE
            orderID = :id
        ', [
            'id'     => $orderId,
            'active' => 1,
        ]);
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