<?php

namespace MxcDropship\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;

class UpdateTrackingData implements AugmentedObject
{
    use ServicesAwareTrait;
    use DatabaseAwareTrait;

    public function run()
    {
        $sentDropshipOrders = $this->getSentDropshipOrders();
        if (empty($sentDropshipOrders)) return;
        /** @var DropshipManager $dropshipManager */
        $dropshipManager = $this->services->get(DropshipManager::class);
        foreach ($sentDropshipOrders as $sentDropshipOrder) {
            $dropshipManager->updateTrackingData($sentDropshipOrder);
        }
    }

    protected function getSentDropshipOrders()
    {
        return $this->db->fetchAll('
            SELECT 
                * 
            FROM 
                s_order o 
            LEFT JOIN 
                s_order_attributes oa ON oa.orderID = o.id 
            WHERE 
                oa.mxcbc_dsi_ordertype > :ownStockType AND oa.mxcbc_dsi_status = :status
            ', [
                'status'        => DropshipManager::DROPSHIP_STATUS_SENT,
                'ownStockType'  => DropshipManager::ORDER_TYPE_OWNSTOCK,
            ]
        );

    }
}