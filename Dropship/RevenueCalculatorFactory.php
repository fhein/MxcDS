<?php

namespace MxcDropship\Dropship;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\MxcCommons;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcCommons\Toolbox\Shopware\MailTool;
use MxcCommons\Toolbox\Shopware\OrderTool;

class RevenueCalculatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $orderTool = MxcCommons::getServices()->get(OrderTool::class);
        $dropshipManager = $container->get(DropshipManager::class);
        $config = Shopware()->Config();
        return new RevenueCalculator($dropshipManager, $orderTool, $config);
    }
}