<?php

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use Shopware\Models\Order\Status;
use Throwable;
use PDO;

class OrderSendCronJob implements SubscriberInterface
{
    protected $valid = null;

    protected $log = null;
    protected $db = null;

    protected $modelManager = null;

    /** @var DropshipManager */
    protected $dropshipManager;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDropshipOrderSend' => 'run',
        ];
    }

    public function run(/** @noinspection PhpUnusedParameterInspection */ $job)
    {
        $start = date('d-m-Y H:i:s');

        $services = MxcDropship::getServices();
        $this->dropshipManager = $services->get(DropshipManager::class);

        $log = $services->get('logger');
        $this->db = Shopware()->Db();

        $result = true;
        try {
            $this->onProcessOrders();
        } catch (Throwable $e) {
            $this->log->except($e, false, false);
            $result = false;
        }
        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $end = date('d-m-Y H:i:s');
        $msg = 'Order send cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $log->info($msg) : $log->err($msg);

        return $result;
    }

    protected function onProcessOrders()
    {
        $orders = $this->getNewDropshipOrders();
        foreach ($orders as $order) {
            $id = $order['orderID'];
            $order['details'] = $this->getOrderDetailsAndAttributes($id);
            $this->dropshipManager->processOrder($order);
        }
    }

    private function getNewDropshipOrders()
    {
        return $this->db->fetchAll('
            SELECT * FROM s_order o 
            LEFT JOIN s_order_attributes oa ON oa.orderID = o.id 
            WHERE AND o.cleared = ? AND oa.mxcbc_dsi_active = 1 AND oa.mxcbc_dsi_status = 0
            ', [ Status::PAYMENT_STATE_COMPLETELY_PAID ]
        );
    }

    private function getOrderDetailsAndAttributes($orderId)
    {
        return $this->db->fetchAll('
            SELECT * FROM s_order_details
            LEFT JOIN s_order_details_attributes ON s_order_details_attributes.detailID = s_order_details.id
            WHERE s_order_details.orderID = ?
        ', [$orderId]);
    }

    private function getDropshipOrderIds()
    {
        return $this->db->fetchAll(
            'SELECT orderID FROM s_order_attributes WHERE mxcbc_dsi_active = 1 AND mxcbc_dsi_status = 0',
            [], PDO::FETCH_COLUMN
        );
    }
}