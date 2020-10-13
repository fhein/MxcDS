<?php

namespace MxcDropship\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use Shopware\Models\Order\Status;

class SendOrders implements AugmentedObject
{
    use ServicesAwareTrait;
    use DatabaseAwareTrait;

    public function run()
    {
        // get list of new dropship orders and return if none is found
        $newDropshipOrders = $this->db->fetchAll('
            SELECT * FROM s_order o 
            LEFT JOIN s_order_attributes oa ON oa.orderID = o.id 
            WHERE o.cleared = :paymentStatus AND oa.mxcbc_dsi_ordertype > 1 AND oa.mxcbc_dsi_status = :dropshipStatus
        ', [
            'paymentStatus' => Status::PAYMENT_STATE_COMPLETELY_PAID,
            'dropshipStatus' => DropshipManager::DROPSHIP_STATUS_OPEN
        ]);
//        $newDropshipOrders = $this->db->fetchAll('
//            SELECT * FROM s_order o
//            LEFT JOIN s_order_attributes oa ON oa.orderID = o.id
//            WHERE oa.mxcbc_dsi_ordertype > 1 AND oa.mxcbc_dsi_status = :dropshipStatus
//        ', [
//            'dropshipStatus' => DropshipManager::DROPSHIP_STATUS_OPEN
//        ]);
        if (empty($newDropshipOrders)) return;
        /** @var DropshipManager $dropshipManager */
        $dropshipManager = $this->services->get(DropshipManager::class);
        foreach ($newDropshipOrders as $newDropshipOrder) {
            $dropshipManager->sendOrder($newDropshipOrder);
        }
    }
}