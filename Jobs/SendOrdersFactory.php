<?php

namespace MxcDropship\Jobs;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\MxcCommons;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcCommons\Toolbox\Shopware\OrderTool;

class SendOrdersFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $orderTool = MxcCommons::getServices()->get(OrderTool::class);
        return new SendOrders($orderTool);
    }
}