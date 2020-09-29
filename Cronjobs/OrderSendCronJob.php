<?php

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Jobs\SendOrders;
use MxcDropship\MxcDropship;
use Shopware\Models\Order\Status;
use Throwable;
use PDO;

class OrderSendCronJob implements SubscriberInterface
{
    protected $log = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDropshipOrderSend' => 'run',
        ];
    }

    public function run(/** @noinspection PhpUnusedParameterInspection */ $job)
    {
        $start = date('d-m-Y H:i:s');

        $services = MxcDropship::getServices();
        $sendOrders = $services->get(SendOrders::class);
        $log = $services->get('logger');

        $results = $sendOrders->run();
        $result = true;
        foreach ($results as $r) {
            if ($r === false) {
                $result = false;
                break;
            }
        }
        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $end = date('d-m-Y H:i:s');
        $msg = 'Order send cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $log->info($msg) : $log->err($msg);

        return $result;
    }
}