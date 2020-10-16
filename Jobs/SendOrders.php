<?php

namespace MxcDropship\Jobs;

use MxcCommons\EventManager\EventInterface;
use MxcCommons\EventManager\SharedEventManagerInterface;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcDropship\Dropship\DropshipManager;
use MxcVapee\Workflow\WorkflowEngine;
use Shopware\Models\Order\Status;

class SendOrders implements AugmentedObject
{
    use ServicesAwareTrait;
    use DatabaseAwareTrait;

    protected $attached = false;

    /** @var DropshipManager */
    protected $dropshipManager;

    public function run(array $openOrders = null)
    {
        // get list of new dropship orders and return if none is found
        $openOrders = $openOrders ?? $this->getOrdersByStatus(Status::ORDER_STATE_OPEN);
        if (empty($openOrders)) return;
        $dropshipManager = $this->services->get(DropshipManager::class);
        foreach ($openOrders as $newDropshipOrder) {
            $dropshipManager->sendOrder($newDropshipOrder);
        }
    }

    protected function getOrdersByStatus(int $statusId)
    {
        return $this->db->fetchAll('
            SELECT * FROM s_order o LEFT JOIN s_order_attributes oa ON oa.orderID = o.id 
            WHERE o.status = :orderStatus 
        ', [
            'orderStatus' => $statusId,
        ]);
    }

}