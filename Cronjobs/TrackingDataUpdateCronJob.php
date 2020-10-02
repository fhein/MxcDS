<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropship\Jobs\UpdateTrackingData;
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
        $result = true;
        $log = null;
        try {
            $services = MxcDropship::getServices();
            $job = $services->get(UpdateTrackingData::class);
            $log = $services->get('logger');
            $log->info('UpdateTrackingData cronjob triggered.');
            $job->run();
        } catch (Throwable $e) {
            if ($log) $log->except($e, false, false);
            $result = 'Exception occured.';
        }
        // displayed in Backend/Settings/Cronjobs
        return $result;
    }
}