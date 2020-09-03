<?php

namespace MxcDropship\Dropship;

use MxcCommons\EventManager\EventManager;
use MxcCommons\EventManager\EventManagerAwareTrait;
use MxcCommons\Plugin\Plugin;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Stdlib\SplStack;
use MxcDropship\Exception\DropshipManagerException;
use MxcDropship\Models\DropshipModule;
use MxcDropshipIntegrator\Jobs\ApplyPriceRules;

class DropshipManager implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use DatabaseAwareTrait;
    use EventManagerAwareTrait;

    protected $moduleServices = [ 
        'DropshipEventListener',
        'ArticleRegistry',
        'ApiClient',
        'ImportClient',
        'StockInfo',
        'OrderProcessor',
        'DropshippersCompanion',
    ];

    protected $auto = true;
    protected $delivery;

    const NO_ERROR          = 0;

    const STATUS_NEW        = 0;
    const STATUS_OK         = 1;
    const STATUS_ERROR      = 2;

    // delivery modes
    const DELIVERY_OWNSTOCK_ONLY        = 0;
    const DELIVERY_PREFER_OWNSTOCK      = 1;
    const DELIVERY_PREFER_DROPSHIP      = 2;
    const DELIVERY_DROPSHIP_ONLY        = 3;

    // constants for all available modules
    const SUPPLIER_SELF     = 0;
    const SUPPLIER_INNOCIGS = 1;
    const SUPPLIER_DEMO     = 2;

    protected $services = [];

    protected $modules = [];

    protected $sharedEvents;

    public function init()
    {
        $modules = $this->modelManager->getRepository(DropshipModule::class)->findAll();
        /** @var DropshipModule $module */
        $this->modules = [];
        foreach ($modules as $module) {
            if (! $module->isActive()) continue;
            $this->setupModule($module);
            // at this point we have a properly configured active dropship adapter module
            $this->modules[$module->getSupplierId()] = $module;
        }

        $config = Shopware()->Config();
        $this->auto = $config->get('mxcbc_dsi_auto');
        $this->delivery = $config->get('mxcbc_dsi_delivery');
    }

    public function getService(int $supplierId, string $requestedName)
    {
        // return from cache if available
        $service = @$this->services[$supplierId][$requestedName];
        if ($service !== null) return $service;

        // retrieve service from service manager
        /** @var DropshipModule $module */
        $module = $this->modules[$supplierId];
        // handle invalid supplier id
        if ($module === null) throw DropshipManagerException::fromInvalidModuleId($supplierId);
        $service = $module->getServices()->get($requestedName);
        $this->services[$supplierId][$requestedName] = $service;
        return $service;
    }

    public function getStockInfo($sArticle)
    {
        // ask each dropship adapter about the # of items in stock
        $stockData = [];
        foreach ($this->modules as $supplierId => $module) {
            $stockInfo = $this->getService($supplierId, 'StockInfo')->getStockInfo($sArticle);

            if (empty($stockInfo)) {
                continue;
            }

            $stockData[] = $stockInfo;
        }
        return $stockData;
    }

    public function getSupplierAndStock(array $sArticle)
    {

    }

    public function isAuto() {
        return $this->auto;
    }

    public function processOrder(array $order)
    {
        $details = $order['details'];
        $supplierIds = array_unique(array_column($details, 'mxcbc_dsi_suppliers'));
        foreach ($supplierIds as $supplierId) {
            $processor = $this->getService($supplierId, 'OrderProcessor');
            $processor->processOrder($order);
        }
    }

    public function updatePrices()
    {
        $result = $this->events->trigger(__FUNCTION__, $this);
        ApplyPriceRules::run();
        return $result;
    }

    public function updateStock()
    {
        return $this->events->trigger(__FUNCTION__, $this);
    }

    protected function setupModule(DropshipModule $module)
    {
        $supplierId = $module->getSupplierId();
        if (isset($this->modules[$supplierId])) {
            throw DropshipManagerException::fromDuplicateModuleId($supplierId);
        }

        $v = $module->getName();
        if ($v === null || ! is_string($v)) {
            throw DropshipManagerException::fromInvalidConfig('name', $v);
        }

        $v = $module->getSupplier();
        if ($v === null || ! is_string($v)) {
            throw DropshipManagerException::fromInvalidConfig('name', $v);
        }

        $plugin = $module->getPlugin();
        if ($plugin === null || ! is_string($plugin)) {
            throw DropshipManagerException::fromInvalidConfig('plugin', $v);
        }

        // do not register adapters which are not present and active or do not comply to our standards
        $pluginClass = $plugin . '\\' . $plugin;
        if (! class_exists($pluginClass)) {
            throw DropshipManagerException::fromInvalidModule(DropshipManagerException::MODULE_CLASS_EXIST, $plugin);
        }
        if (! is_a($pluginClass, Plugin::class, true)) {
            throw DropshipManagerException::fromInvalidModule(DropshipManagerException::MODULE_CLASS_IDENTITY, $plugin);
        }
        if (!$this->db->fetchOne('SELECT active FROM s_core_plugins WHERE name = ?', [$plugin])) {
            throw DropshipManagerException::fromInvalidModule(DropshipManagerException::MODULE_CLASS_INSTALLED, $plugin);
        }
        if (! method_exists($pluginClass, 'getServices')) {
            throw DropshipManagerException::fromInvalidModule(DropshipManagerException::MODULE_CLASS_SERVICES, $plugin);
        }

        $module->setModuleClass($pluginClass);
        $services = call_user_func($pluginClass . '::getServices');
        $module->setServices($services);

        // check if all required dropship modules are available
        foreach ($this->moduleServices as $moduleService) {
            if (! $services->has($moduleService)) {
                throw DropshipManagerException::fromMissingModuleService($moduleService);
            }
        }
        // enable dropship module event listening
        $listener = $services->get('DropshipEventListener');
        $listener->attach($this->events->getSharedManager());
    }
}