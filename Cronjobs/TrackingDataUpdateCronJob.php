<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropship\Jobs\TrackingDataUpdate;
use MxcDropship\MxcDropship;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use Throwable;

class TrackingDataUpdateCronJob implements SubscriberInterface
{
    protected $valid = null;

    protected $log = null;

    protected $modelManager = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDropshipTrackingDataUpdate' => 'onTrackingDataUpdate',
        ];
    }

    public function onTrackingDataUpdate(/** @noinspection PhpUnusedParameterInspection */$job)
    {
        $start = date('d-m-Y H:i:s');

        $services = MxcDropship::getServices();
        $trackingDataUpdate = $services->get(TrackingDataUpdate::class);
        $log = $services->get('logger');

        $result = true;
        try {
            $trackingDataUpdate->run();
        } catch (Throwable $e) {
            $log->except($e, false, false);
            $result = false;
        }
        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $end = date('d-m-Y H:i:s');
        $msg = 'Tracking data update cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $log->info($msg) : $log->err($msg);

        return $result;
    }
}