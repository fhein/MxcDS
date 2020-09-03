<?php /** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcDropship\Dropship\DropshipManager;
use MxcDropshipIntegrator\Jobs\ApplyPriceRules;
use MxcDropshipInnocigs\Jobs\UpdatePrices;
use MxcDropship\MxcDropship;
use Throwable;

class PriceUpdateCronJob implements SubscriberInterface
{
    protected $valid = null;

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
        $start = date('d-m-Y H:i:s');

        $services = MxcDropship::getServices();
        $log = $services->get('logger');
        $result = true;

        try {
            /** @var DropshipManager $dropshipManager */
            $dropshipManager = $services->get(DropshipManager::class);
            $dropshipManager->updatePrices();
        } catch (Throwable $e) {
            $this->log->except($e, false, false);
            $result = false;
        }
        $resultMsg = $result === true ? '. Success.' : '. Failure.';
        $end = date('d-m-Y H:i:s');
        $msg = 'Update prices cronjob ran from ' . $start . ' to ' . $end . $resultMsg;

        $result === true ? $log->info($msg) : $log->err($msg);

        return $result;
    }
}
