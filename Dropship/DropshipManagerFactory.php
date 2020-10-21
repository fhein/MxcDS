<?php

namespace MxcDropship\Dropship;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\MxcCommons;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcCommons\Toolbox\Shopware\MailTool;
use MxcCommons\Toolbox\Shopware\OrderTool;

class DropshipManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dropshipLogger = $container->get(DropshipLogger::class);
        $config = Shopware()->Config();
        $mxcCommons = MxcCommons::getServices();
        $mailer = $mxcCommons->get(MailTool::class);
        $orderTool = $mxcCommons->get(OrderTool::class);
        return new DropshipManager($dropshipLogger, $mailer, $orderTool, $config);
    }
}