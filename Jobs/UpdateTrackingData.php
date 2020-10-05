<?php

namespace MxcDropship\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use Shopware\Models\Order\Status;

class UpdateTrackingData implements AugmentedObject
{
    use ServicesAwareTrait;
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
        if (empty($sentDropshipOrders)) return ([true]);
        /** @var DropshipManager $dropshipManager */
        $dropshipManager = $this->services->get(DropshipManager::class);
        foreach ($sentDropshipOrders as $sentDropshipOrder) {
            $dropshipManager->updateTrackingData($sentDropshipOrder);
        }
    }
}