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
use Throwable;

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
        $result = $this->events->trigger(__FUNCTION__, $this)->toArray();
        ApplyPriceRules::run();
        return array_unique($result)[0];
    }

    public function updateStock()
    {
        $result = $this->events->trigger(__FUNCTION__, $this)->toArray();
        return array_unique($result)[0];
    }

    // Important: order ID is $order['orderID'], e.g. not $order['id']
    public function sendOrder(array $order)
    {
        $status = $this->events->trigger(__FUNCTION__, $this, ['order' => $order])->toArray();
        return $this->setOrderStatus($order['orderID'], $status, self::ORDER_STATUS_SENT);
    }

    public function updateTrackingData(array $order)
    {
        $status = $this->events->trigger(__FUNCTION__, $this, ['order' => $order])->toArray();
        return $this->setOrderStatus($order['orderID'], $status, self::ORDER_STATUS_TRACKING_DATA);
    }

    public function setOrderStatus(int $orderId, array $status, int $expectedStatus)
    {
        // modules not involved in the current order processing return null, array_column silently ignores null entries
        $s = array_unique(array_column($status, 'status'));
        if (empty($s) || count($s) > 1) return true;

        // status progress happens only if all modules return expected status or null (which means not applicable)
        if ($s[0] != $expectedStatus) return false;
        // remove null elements and get first (and only) status
        // note: array_filter maintains the array indexes, so array_value is necessary to access the first element
        $status = array_values(array_filter($status))[0];


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
        return true;
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

    public function getNotificationContext(string $supplier, string $caller, $key, array $order = null)
    {
        $context = @$this->classConfig['notification_context'][$key][$caller];
        if ($context === null) return null;

        $replacements = [
            '{$orderNumber}' => @$order['ordernumber'] ?? '',
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

    public function logStatus(array $context, array $order = null)
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
            $this->dropshipLogger->done();
            return;
        }
        foreach ($errors as $error) {
            $this->dropshipLogger->log(
                $context['severity'],
                $context['supplier'],
                '- ' . $error['message'],
                $order['orderID'],
                $order['ordernumber'],
                $error['productNumber'],
                $error['quantity']
            );
        }
        $this->dropshipLogger->done();
    }

    // send status mail and add log entries
    public function notifyStatus(array $context, array $order = null, bool $sendMail = true)
    {
        if ($sendMail) $this->sendNotificationMail($context);
        $this->logStatus($context, $order);
    }

    public function getSupplierOrderDetailsCount(string $supplier, int $orderId)
    {
        return $this->db->fetchOne('
            SELECT 
                COUNT(od.id) 
            FROM 
                s_order_details od
            LEFT JOIN 
                s_order_details_attributes oda ON oda.detailID = od.id
            WHERE 
                od.orderID = :orderId AND oda.mxcbc_dsi_supplier = :supplier
        ', ['orderId' => $orderId, 'supplier' => $supplier]);
    }

    public function getSupplierOrderDetails(string $supplier, int $orderId)
    {
        return $this->db->fetchAll('
            SELECT 
                * 
            FROM 
                s_order_details od
            LEFT JOIN 
                s_order_details_attributes oda ON oda.detailID = od.id
            WHERE 
                od.orderID = :orderId AND oda.mxcbc_dsi_supplier = :supplier
        ', ['orderId' => $orderId, 'supplier' => $supplier]);
    }

    public function handleDropshipException(
        string $supplier,
        string $caller,
        Throwable $e,
        bool $sendMail,
        array $order = null,
        array $shippingAddress = null
    ) {
        $code = $e instanceof DropshipException ? $e->getCode() : 'UNKNOWN_ERROR';
        $context = $this->getNotificationContext($supplier, $caller, $code, $order);
        switch ($code) {
            case DropshipException::MODULE_API_SUPPLIER_ERRORS:
                $context['errors'] = $e->getSupplierErrors();
                break;
            case DropshipException::ORDER_POSITIONS_ERROR:
                $context['errors'] = $e->getPositionErrors();
                break;
            case DropshipException::ORDER_RECIPIENT_ADDRESS_ERROR:
                $context['errors'] = $e->getAddressErrors();
                $context['shippingaddress'] = $shippingAddress;
                break;
            case DropshipException::MODULE_API_XML_ERROR:
                $context['errors'] = $e->getXmlErrors();
                break;
            case DropshipException::MODULE_API_ERROR:
                $context['errors'] = $e->getApiErrors();
                break;
            case 'UNKNOWN_ERROR':
            default:
                $context = $this->getNotificationContext($supplier, $caller, 'UNKNOWN_ERROR', $order);
                $context['errors'] = [['code' => $e->getCode(), 'message' => $e->getMessage()]];
        }
        $this->notifyStatus($context, $order, $sendMail);
        return [
            'status' => $context['status'],
            'message' => $context['message'],
        ];
    }

    public function notifyOrderSuccessfullySent(string $supplier, string $caller, array $order)
    {
        $context = $this->getNotificationContext($supplier, $caller, 'STATUS_SUCCESS', $order);
        $this->notifyStatus($context, $order);
    }
}