<?php

namespace MxcDropship\WorkflowActions;

use MxcCommons\EventManager\EventInterface;
use MxcCommons\Toolbox\Shopware\DocumentRenderer;
use MxcDropship\Dropship\DropshipManager;
use MxcWorkflow\Workflow\WorkflowAction;
use MxcWorkflow\Workflow\WorkflowEngine;
use Shopware\Models\Order\Status;

class CheckTrackingData extends WorkflowAction
{
    protected $config = [
        'statusId' => Status::ORDER_STATE_IN_PROCESS,
        'listener'    => __CLASS__,
        'priority' => 50,
    ];

    protected $notificationTemplate = [
        'mailTemplate'      => 'sMxcWorkflowNotification',
        'mailSubject'       => 'Rechnung zu Bestellung {$orderNumber}',
        'mailTitle'         => 'Bestellung erfolgreich abgeschlossen',
        'mailBody'          => 'die Bestellung <strong>{$orderNumber}</strong> wurde erfolgreich abgeschlossen. '
                                . 'Anhängend die Rechnung für die Buchhaltung.',
        'message'           => 'Bestellung erfolgreich abgeschlossen',
    ];

    protected $dropshipManager;

    public function __construct(DropshipManager $dropshipManager)
    {
        $this->dropshipManager = $dropshipManager;
    }

    public function run(EventInterface $e)
    {
        $order = $e->getParam('order');
        /** @var WorkflowEngine $engine */
        $engine = $e->getTarget();
        $orderId = $order['orderID'];
        if ($this->dropshipManager->isClarificationRequired($order)) {
            $engine->setOrderStatus($orderId, Status::ORDER_STATE_CLARIFICATION_REQUIRED);
            return;
        }
        $trackingDataComplete = $this->dropshipManager->isTrackingDataComplete($order);
        if (! $trackingDataComplete) return;

        $this->dropshipManager->deleteDropshipLog($orderId);
        $engine->sendStatusMail($orderId, Status::ORDER_STATE_COMPLETELY_DELIVERED);
        $statusId = Status::ORDER_STATE_COMPLETED;
        $engine->setOrderStatus($orderId, $statusId);
        $engine->sendStatusMail($orderId, $statusId, [DocumentRenderer::DOC_TYPE_INVOICE]);
        $context = $this->getNotificationContext($this->notificationTemplate, $order);
        $engine->sendNotificationMail($orderId, $context, [DocumentRenderer::DOC_TYPE_INVOICE]);
    }
}