<?php

namespace MxcDropship\WorkflowActions;

use MxcCommons\EventManager\EventInterface;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use MxcWorkflow\Workflow\WorkflowAction;
use Shopware\Models\Order\Status;

class SendOrder extends WorkflowAction
{
    protected $config = [
        'statusId' => Status::ORDER_STATE_IN_PROCESS,
        'listener'    => __CLASS__,
        'priority' => 100,
    ];

    public function run(EventInterface $e)
    {
        $order = $e->getParam('order');
        MxcDropship::getServices()->get(DropshipManager::class)->sendOrder($order);
    }
}