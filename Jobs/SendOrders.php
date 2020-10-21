<?php

namespace MxcDropship\Jobs;

use MxcCommons\EventManager\EventInterface;
use MxcCommons\EventManager\SharedEventManagerInterface;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\OrderTool;
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

    protected $orderTool;

    public function __construct(OrderTool $orderTool)
    {
        $this->orderTool = $orderTool;
    }

    public function run(array $openOrders = null)
    {
        // get list of new dropship orders and return if none is found
        $openOrders = $openOrders ?? $this->orderTool->getOrdersByStatus(Status::ORDER_STATE_OPEN);
        if (empty($openOrders)) return;
        $dropshipManager = $this->services->get(DropshipManager::class);
        foreach ($openOrders as $newDropshipOrder) {
            $dropshipManager->sendOrder($newDropshipOrder);
        }
    }
}