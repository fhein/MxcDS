<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use Throwable;

class StockUpdateCronJob implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDropshipStockUpdate' => 'onStockUpdate',
        ];
    }

    public function onStockUpdate(/** @noinspection PhpUnusedParameterInspection */ $job)
    {
        $services = MxcDropship::getServices();
        $log = $services->get('logger');

        $start = date('d-m-Y H:i:s');
        $result = true;

        try {
            $dropshipManager = $services->get(DropshipManager::class);
            $log->info('Update Stock cronjob triggered.');
            $result = $dropshipManager->updateStock();
        } catch (Throwable $e) {
            $log->except($e, false, false);
            $result = false;
        }
        $end = date('d-m-Y H:i:s');

        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $msg = 'Update stock cronjob ran from ' . $start . ' to ' . $end . $resultMsg;
        $result === true ? $log->info($msg) : $log->err($msg);

        return $result;
    }

}