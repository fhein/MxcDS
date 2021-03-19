<?php

namespace MxcDropship\Jobs;

use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ServicesAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\OrderTool;
use MxcDropship\Dropship\DropshipManager;
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

    public function run(array $inProgessOrders = null)
    {
        // get list of new dropship orders and return if none is found
        $inProgressOrders = $inProgessOrders ?? $this->orderTool->getOrdersByOrderStatus(Status::ORDER_STATE_IN_PROCESS);
        if (empty($inProgressOrders)) return;
        $dropshipManager = $this->services->get(DropshipManager::class);
        foreach ($inProgressOrders as $orderId) {
            $dropshipManager->sendOrder($orderId);
        }
    }
}