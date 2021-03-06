<?php

namespace MxcDropship\WorkflowActions;

use MxcCommons\EventManager\EventInterface;
use MxcDropship\Dropship\DropshipManager;
use MxcWorkflow\Workflow\WorkflowAction;
use Shopware\Models\Order\Status;

class SendOrder extends WorkflowAction
{
    protected $config = [
        'statusId' => Status::ORDER_STATE_IN_PROCESS,
        'listener'    => __CLASS__,
        'priority' => 100,
    ];

    protected $dropshipManager;

    public function __construct(DropshipManager $dropshipManager)
    {
        $this->dropshipManager = $dropshipManager;
    }

    public function run(EventInterface $e)
    {
        $engine = $e->getTarget();
        $orderId = $e->getParam('orderID');
        $order = $engine->getOrder($orderId);
        if ($order['status'] != $this->config['statusId']) return;

        $this->dropshipManager->sendOrder($orderId);
    }
}