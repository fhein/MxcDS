<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcCommons\Plugin\Service\Logger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipIntegrator\Jobs\ApplyPriceRules;
use MxcDropshipInnocigs\Jobs\UpdatePrices;
use MxcDropship\MxcDropship;
use Throwable;

class PriceUpdateCronJob implements SubscriberInterface
{
    protected $valid = null;

    /** @var Logger */
    protected $log = null;

    protected $modelManager = null;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDropshipPriceUpdate' => 'onUpdatePrices',
        ];
    }

    public function onUpdatePrices(/** @noinspection PhpUnusedParameterInspection */$job)
    {
        $services = MxcDropship::getServices();
        $log = $services->get('logger');

        $start = date('d-m-Y H:i:s');
        $result = true;

        try {
            $dropshipManager = $services->get(DropshipManager::class);
            $log->info('Update Stock cronjob triggered.');
            $dropshipManager->updatePrices();
        } catch (Throwable $e) {
            $log->except($e, false, false);
            $result = false;
        }
        $end = date('d-m-Y H:i:s');

        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $msg = 'Update prices cronjob ran from ' . $start . ' to ' . $end . $resultMsg;
        $result === true ? $log->info($msg) : $log->err($msg);

        return $result;
    }
}
