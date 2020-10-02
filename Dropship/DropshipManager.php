<?php

namespace MxcDropship\Dropship;

use MxcCommons\EventManager\EventManager;
use MxcCommons\EventManager\EventManagerAwareTrait;
use MxcCommons\EventManager\ResponseCollection;
use MxcCommons\Plugin\Plugin;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Stdlib\SplStack;
use MxcDropship\Exception\DropshipException;
use MxcDropship\Models\DropshipModule;
use MxcDropshipIntegrator\Jobs\ApplyPriceRules;

class DropshipManager implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use DatabaseAwareTrait;
    use EventManagerAwareTrait;
    use ClassConfigAwareTrait;
    use LoggerAwareTrait;

    protected $moduleServices = [
        'DropshipEventListener',
        'ArticleRegistry',
        'ApiClient',
        'OrderProcessor',
        'DropshippersCompanion',
    ];

    protected $auto = true;
    protected $mode;

    const NO_ERROR = 0;

    // bit flags to mark an order as ownstock or dropship or both
    const ORDER_TYPE_OWNSTOCK = 1;
    const ORDER_TYPE_DROPSHIP = 2;

    // new dropship order, not transmitted to supplier
    const ORDER_STATUS_OPEN = 0;

    // dropship order succeessfully submitted, waiting for tracking data
    const ORDER_STATUS_SENT = 1;

    // tracking data received
    const ORDER_STATUS_TRACKING_DATA = 2;

    // dropship order closed (success)
    const ORDER_STATUS_CLOSED = 3;

    // dropship order cancelled by supplier
    const ORDER_STATUS_CANCELLED = 4;

    // Auftrag konnte nicht übertragen werden, Auftrag wird ignoriert, manuelles Eingreifen erforderlich
    const ORDER_STATUS_POSITION_ERROR = 99;
    const ORDER_STATUS_ADDRESS_ERROR = 98;
    const ORDER_STATUS_XML_ERROR = 97;
    const ORDER_STATUS_SUPPLIER_ERROR = 96;
    const ORDER_STATUS_UNKNOWN_ERROR = 95;
    const ORDER_STATUS_API_ERROR = 94;

    // delivery modes
    const MODE_OWNSTOCK_ONLY = 0;
    const MODE_PREFER_OWNSTOCK = 1;
    const MODE_PREFER_DROPSHIP = 2;
    const MODE_DROPSHIP_ONLY = 3;

    protected $services = [];

    protected $modules = [];

    protected $sharedEvents;

    protected $moduleIdsByName;

    protected $dropshipLogger;

    public function __construct(DropshipLogger $dropshipLogger)
    {
        $this->dropshipLogger = $dropshipLogger;
    }

    public function init()
    {
        $modules = $this->modelManager->getRepository(DropshipModule::class)->findAll();
        /** @var DropshipModule $module */
        $this->modules = [];
        foreach ($modules as $module) {
            if (! $module->isActive()) {
                continue;
            }
            $this->setupModule($module);
            // at this point we have a properly configured active dropship adapter module
            $this->modules[$module->getName()] = $module;
        }

        $config = Shopware()->Config();
        $this->auto = $config->get('mxcbc_dsi_auto');
        $this->mode = $config->get('mxcbc_dsi_mode');
    }

    public function getService(string $supplier, string $requestedName)
    {
        // return from cache if available
        $service = @$this->services[$supplier][$requestedName];
        if ($service !== null) {
            return $service;
        }

        // retrieve service from service manager
        /** @var DropshipModule $module */
        $module = $this->modules[$supplier];
        // handle invalid supplier id
        if ($module === null) {
            throw DropshipException::fromUnregisteredModule($supplier);
        }
        $service = $module->getServices()->get($requestedName);
        $this->services[$supplier][$requestedName] = $service;
        return $service;
    }

    public function isAuto()
    {
        return $this->auto;
    }

    public function updatePrices()
    {
        $result = $this->events->trigger(__FUNCTION__, $this);
        ApplyPriceRules::run();
        return $result;
    }

    public function updateStock()
    {
        $result = $this->events->trigger(__FUNCTION__, $this);
        return $result->toArray();
    }

    // Important: order ID is $order['orderID'], e.g. not $order['id']
    public function sendOrder(array $order)
    {
        $status = $this->events->trigger(__FUNCTION__, $this, ['order' => $order])->toArray();
        $this->setOrderStatus($order['orderID'], $status);
    }

    public function updateTrackingData(array $order)
    {
        $status = $this->events->trigger(__FUNCTION__, $this, ['order' => $order])->toArray();
        $this->setOrderStatus($order['orderID'], $status);
    }

    protected function setOrderStatus(int $orderId, array $status)
    {
        // modules not involved in the current order processing return null, array_column silently ignores null entries
        $s = array_unique(array_column($status, 'status'));
        // empty($s) should never be true because DropshipManager functions should never be called for non dropship orders
        // we check that for sanitary reasons anyway
        if (empty($s)) return;

        if (count($s) > 1) {
            $this->log->warn('Dropship modules returned different status. Using first result');
        }
        $status = $status[0];

        // false is returned if the order was not processed
        if (empty($status)) return;

        $this->db->executeUpdate('
            UPDATE 
                s_order_attributes oa
            SET
                oa.mxcbc_dsi_status = :status,
                oa.mxcbc_dsi_message = :message
            WHERE                
                oa.orderID = :id
            ', [
                'status'  => $status['status'],
                'message' => $status['message'],
                'id'      => $orderId,
            ]
        );
    }

    protected function setupModule(DropshipModule $module)
    {
        $supplier = $module->getName();
        if (isset($this->modules[$supplier])) {
            throw DropshipException::fromDuplicateModule($supplier);
        }

        $v = $module->getName();
        if ($v === null || ! is_string($v)) {
            throw DropshipException::fromInvalidConfig('name', $v);
        }

        $v = $module->getSupplier();
        if ($v === null || ! is_string($v)) {
            throw DropshipException::fromInvalidConfig('name', $v);
        }

        $plugin = $module->getPlugin();
        if ($plugin === null || ! is_string($plugin)) {
            throw DropshipException::fromInvalidConfig('plugin', $v);
        }

        // do not register adapters which are not present and active or do not comply to our standards
        $pluginClass = $plugin . '\\' . $plugin;
        if (! class_exists($pluginClass)) {
            throw DropshipException::fromInvalidModule(DropshipException::MODULE_CLASS_EXIST, $plugin);
        }
        if (! is_a($pluginClass, Plugin::class, true)) {
            throw DropshipException::fromInvalidModule(DropshipException::MODULE_CLASS_IDENTITY, $plugin);
        }
        if (! $this->db->fetchOne('SELECT active FROM s_core_plugins WHERE name = ?', [$plugin])) {
            throw DropshipException::fromInvalidModule(DropshipException::MODULE_CLASS_INSTALLED, $plugin);
        }
        if (! method_exists($pluginClass, 'getServices')) {
            throw DropshipException::fromInvalidModule(DropshipException::MODULE_CLASS_SERVICES, $plugin);
        }

        $module->setModuleClass($pluginClass);
        $services = call_user_func($pluginClass . '::getServices');
        $module->setServices($services);

        // check if all required dropship modules are available
        foreach ($this->moduleServices as $moduleService) {
            if (! $services->has($moduleService)) {
                throw DropshipException::fromMissingModuleService($moduleService);
            }
        }
        // enable dropship module event listening
        $listener = $services->get('DropshipEventListener');
        $listener->attach($this->events->getSharedManager());
    }

    public function getNotificationContext(string $supplier, array $order, $key)
    {
        $context = $this->classConfig['notification_context'][$key];

        $replacements = [
            '{$orderNumber}' => $order['ordernumber'],
            '{$supplier}'    => $supplier,
        ];

        foreach ($context as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            foreach ($replacements as $search => $replace) {
                $context[$key] = str_replace($search, $replace, $context[$key]);
            }
        }
        $context['supplier'] = $supplier;
        return $context;
    }

    public function sendNotificationMail(array $context)
    {
        $dsMail = Shopware()->TemplateMail()->createMail($context['mailTemplate'], $context);
        $dsMail->addTo('support@vapee.de');
        $dsMail->clearFrom();
        $dsMail->setFrom('info@vapee.de', 'vapee.de Dropship');
        if (isset($context['mailSubject'])) {
            $dsMail->clearSubject();
            $dsMail->setSubject($context['mailSubject']);
        }
        $dsMail->send();

    }

    public function logStatus(array $order, array $context)
    {
        $this->dropshipLogger->log(
            $context['severity'],
            $context['supplier'],
            $context['message'],
            $order['orderID'],
            $order['ordernumber']
        );
        $errors = $context['errors'];
        if (empty($errors)) {
            return;
        }
        foreach ($errors as $error) {
            $this->dropshipLogger->log(
                $context['severity'],
                $context['supplier'],
                $error['message'],
                $order['orderID'],
                $order['ordernumber'],
                $error['productNumber'],
                $error['quantity']
            );
        }
    }

    // send status mail and add log entries
    public function notifyStatus(array $order, array $context)
    {
        $this->sendNotificationMail($context);
        $this->logStatus($order, $context);
    }

    public function getSupplierOrderDetails(string $supplier, int $orderId)
    {
        return $this->db->fetchAll('
            SELECT * FROM s_order_details od
            LEFT JOIN s_order_details_attributes oda ON oda.detailID = od.id
            WHERE od.orderID = :orderId AND oda.mxcbc_dsi_supplier = :supplier
        ', ['orderId' => $orderId, 'supplier' => $supplier]);
    }

}