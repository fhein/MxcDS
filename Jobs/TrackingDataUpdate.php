<?php

namespace MxcDropship\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use Shopware\Models\Order\Status;

class TrackingDataUpdate implements AugmentedObject
{
    use DatabaseAwareTrait;

    public function run()
    {
        $sentDropshipOrders = $this->db->fetchAll('
            SELECT * FROM s_order o 
            LEFT JOIN s_order_attributes oa ON oa.orderID = o.id 
            WHERE oa.mxcbc_dsi_ordertype > 1 AND oa.mxcbc_dsi_status = :status
            ', [
                'status'        => DropshipManager::ORDER_STATUS_SENT
            ]
        );
    }
}