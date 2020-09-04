<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcCommons\Plugin\Service\LoggerInterface;
use MxcCommons\Toolbox\Strings\StringTool;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\MxcDropship;
use MxcDropshipInnocigs\MxcDropshipInnocigs;
use MxcDropshipInnocigs\Api\ApiClient;
use MxcDropshipInnocigs\Article\ArticleRegistry;
use MxcCommons\Toolbox\Shopware\ArticleTool;
use Shopware\Models\Article\Detail;
use Shopware\Models\Plugin\Plugin;
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
        /** @var LoggerInterface $log */
        $result = true;

        $start = date('d-m-Y H:i:s');

        try {
            /** @var DropshipManager $dropshipManager */
            $dropshipManager = $services->get(DropshipManager::class);
            $dropshipManager->updateStock();
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