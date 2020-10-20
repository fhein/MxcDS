<?php

namespace MxcDropship\Dropship;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\MxcCommons;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcCommons\Toolbox\Shopware\MailTool;

class DropshipManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dropshipLogger = $container->get(DropshipLogger::class);
        $config = Shopware()->Config();
        $mailer = MxcCommons::getServices()->get(MailTool::class);
        return new DropshipManager($dropshipLogger, $mailer, $config);
    }
}