<?php

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropship\Jobs\SendOrders;
use MxcDropship\MxcDropship;
use Throwable;

class OrderSendCronJob implements SubscriberInterface
{
    protected $log = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDropshipOrderSend' => 'run',
        ];
    }

    public function run($job)
    {
        $services = MxcDropship::getServices();
        $log = $services->get('logger');

        $result = true;
        try {
            $job = $services->get(SendOrders::class);
            $log->info('OrderSend cronjob triggered.');
            $job->run();
        } catch (Throwable $e) {
            if ($log) $log->except($e, false, false);
            $result = 'Exception occured.';
        }
        return $result;
    }
}