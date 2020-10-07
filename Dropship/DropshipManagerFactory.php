<?php

namespace MxcDropship\Dropship;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class DropshipManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dropshipLogger = $container->get(DropshipLogger::class);
        $config = Shopware()->Config();
        return new DropshipManager($dropshipLogger, $config);
    }
}