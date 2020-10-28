<?php

namespace MxcDropship\WorkflowActions;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;
use MxcDropship\Dropship\DropshipManager;

class SendOrderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dropshipManager = $container->get(DropshipManager::class);
        return new SendOrder($dropshipManager);
    }
}