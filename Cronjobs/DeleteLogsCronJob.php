<?php

namespace MxcDropship\Cronjobs;

use Enlight\Event\SubscriberInterface;
use MxcCommons\Toolbox\Shopware\CronjobLogDeleteTrait;

class DeleteLogsCronJob implements SubscriberInterface
{
    use CronjobLogDeleteTrait;

    public function __construct()
    {
        $this->logIds = [
            'mxc_dropship',
            'mxc_commons',
        ];
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_MxcDropshipDeleteLogs' => 'onDeleteLogs',
        ];
    }
}