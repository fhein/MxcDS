<?php

namespace MxcDropship\WorkflowActions;

use MxcCommons\EventManager\EventInterface;
use MxcCommons\Toolbox\Shopware\DocumentRenderer;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use MxcWorkflow\Workflow\WorkflowAction;
use MxcWorkflow\Workflow\WorkflowEngine;
use Shopware\Models\Order\Status;
use Throwable;

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
    protected $log;

    public function __construct(DropshipManager $dropshipManager)
    {
        $this->dropshipManager = $dropshipManager;
        $this->log = MxcDropship::getServices()->get('logger');
    }

    public function run(EventInterface $e)
    {
        /** @var WorkflowEngine $engine */
        $engine = $e->getTarget();
        $orderId = $e->getParam('orderID');
        $order = $engine->getOrder($orderId);
        if ($order['status'] != $this->config['statusId']) return;

        if ($this->dropshipManager->isClarificationRequired($orderId)) {
            $engine->setOrderStatus($orderId, Status::ORDER_STATE_CLARIFICATION_REQUIRED);
            return;
        }
        $trackingDataComplete = $this->dropshipManager->isTrackingDataComplete($orderId);
        if (! $trackingDataComplete) return;

        $this->dropshipManager->deleteDropshipLog($orderId);
        $engine->sendStatusMail($orderId, Status::ORDER_STATE_COMPLETELY_DELIVERED);
        $statusId = Status::ORDER_STATE_COMPLETED;
        $engine->setOrderStatus($orderId, $statusId);
        $engine->sendStatusMail($orderId, $statusId, [DocumentRenderer::DOC_TYPE_INVOICE]);
        $order = $engine->getOrder($orderId);
        $context = $this->getNotificationContext($this->notificationTemplate, $order);
        $context['revenue'] = $this->dropshipManager->calculateRevenue($orderId);
        $engine->sendNotificationMail($orderId, $context, [DocumentRenderer::DOC_TYPE_INVOICE]);

        if ($engine->isKlarna($order) && $order['cleared'] == Status::PAYMENT_STATE_PARTIALLY_INVOICED) {
            $order = $engine->getOrder($orderId);
            $amount = $order['invoice_amount'];
            $klarnaId = $order['transactionID'];
            $this->log->debug('Transaction Id: ' . $klarnaId . ', amount: ' . $amount);
            if (! $this->klarnaCapture($klarnaId, $amount)) {
                $engine->setPaymentStatus($orderId, Status::PAYMENT_STATE_REVIEW_NECESSARY);
                $this->log->debug('Klarna Capture failed');
            } else {
                $this->log->debug('Klarna Capture successful.');
            }
        }
    }

    public function klarnaCapture(string $klarnaId, float $amount) : bool
    {
        $this->log->debug('Start Klarna Capture');
        $services = MxcDropship::getServices();

        // Klarna capture
        $calculator = $services->get('bestit_klarna_order_management.components.calculator.calculator');
        $capture = $services->get('bestit_klarna_order_management.components.facade.capture');

        $amountCents = $calculator->toCents($amount);
        $result = true;
        try {
            $response = $capture->create($klarnaId, $amountCents);
            if ($response->isError()) {
                $error = $response->getError();
                $this->log->err('Klarna: ' . $error->errorCode);
                $result = false;
            }
        } catch(Throwable $t) {
            $this->log->debug('Failed to create capture for Klarna Id: ' . $klarnaId);
            $this->log->except($t, true, false);
            $result = false;
        }
        $this->log->debug('End Klarna Capture.');
        return $result;
    }
}