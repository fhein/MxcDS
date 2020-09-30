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
            WHERE o.cleared = ? AND oa.mxcbc_dsi_ordertype > 1 AND oa.mxcbc_dsi_status = 0
            ', [ Status::PAYMENT_STATE_COMPLETELY_PAID ]
        );
        $newDropshipOrders = $this->db->fetchAll('
            SELECT * FROM s_order o 
            LEFT JOIN s_order_attributes oa ON oa.orderID = o.id 
            WHERE oa.mxcbc_dsi_ordertype > 1 AND oa.mxcbc_dsi_status = 0
        ');
        if (empty($newDropshipOrders)) return ([true]);
        /** @var DropshipManager $dropshipManager */
        $dropshipManager = $this->services->get(DropshipManager::class);
        $result = true;
        foreach ($newDropshipOrders as $newDropshipOrder) {
            $results = $dropshipManager->sendOrder($newDropshipOrder);
            // if result is false already we can skip the evaluation
            if ($result === true) {
                foreach ($results as $r) {
                    if (! $r) {
                        $result = false;
                        break;
                    }
                }
            }
        }
        // $result is false, if at least one error occurs
        return $result;
    }
}