<?php

namespace MxcDropship\Dropship;

use MxcCommons\EventManager\EventManagerAwareTrait;
use MxcCommons\EventManager\ListenerAggregateInterface;
use MxcCommons\EventManager\ListenerAggregateTrait;
use MxcCommons\Plugin\Plugin;
use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\Plugin\Service\LoggerAwareTrait;
use MxcCommons\Plugin\Service\ModelManagerAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\MailTool;
use MxcCommons\Toolbox\Shopware\OrderTool;
use MxcDropship\Exception\DropshipException;
use MxcDropship\Models\DropshipModule;
use MxcDropshipIntegrator\Jobs\ApplyPriceRules;
use Shopware\Models\Order\Status;
use Throwable;
use Shopware_Components_Config;

class DropshipManager implements AugmentedObject
{
    use ModelManagerAwareTrait;
    use DatabaseAwareTrait;
    use EventManagerAwareTrait;
    use ClassConfigAwareTrait;
    use LoggerAwareTrait;

    // initial order status depends on order type
    protected $initialOrderStatus = [
        DropshipManager::ORDER_TYPE_OWNSTOCK                                        => [
            'status'  => DropshipManager::DROPSHIP_STATUS_INACTIVE,
            'message' => 'Nicht aktiv.',
        ],
        DropshipManager::ORDER_TYPE_DROPSHIP                                        => [
            'status'  => DropshipManager::DROPSHIP_STATUS_OPEN,
            'message' => 'Dropship-Auftrag wartend. Wird versandt, wenn vollständig bezahlt',

        ],
        DropshipManager::ORDER_TYPE_DROPSHIP | DropshipManager::ORDER_TYPE_OWNSTOCK => [
            'status'  => DropshipManager::DROPSHIP_STATUS_OPEN,
            'message' => 'Dropship-Auftrag wartend. Wird versandt, wenn vollständig bezahlt.'
        ]
    ];

    protected $moduleServices = [
        'DropshipEventListener',
        'ArticleRegistry',
        'ApiClient',
        'OrderProcessor',
        'DropshippersCompanion',
    ];

    // bit flags to mark an order as ownstock or dropship or both
    const ORDER_TYPE_OWNSTOCK = 1;
    const ORDER_TYPE_DROPSHIP = 2;

    // new dropship order, not transmitted to supplier
    const DROPSHIP_STATUS_OPEN = 0;

    // dropship order succeessfully submitted, waiting for tracking data
    const DROPSHIP_STATUS_SENT = 1;

    // tracking data received, job done
    const DROPSHIP_STATUS_CLOSED = 2;

    // dropship order cancelled by supplier
    const DROPSHIP_STATUS_CANCELLED = 3;

    // order does not contain dropship products
    const DROPSHIP_STATUS_INACTIVE = 4;

    const DROPSHIP_STATUS_ERROR = 90;
    const DROPSHIP_STATUS_POSITION_ERROR = 99;
    const DROPSHIP_STATUS_ADDRESS_ERROR = 98;
    const DROPSHIP_STATUS_XML_ERROR = 97;
    const DROPSHIP_STATUS_SUPPLIER_ERROR = 96;
    const DROPSHIP_STATUS_UNKNOWN_ERROR = 95;
    const DROPSHIP_STATUS_API_ERROR = 94;

    // delivery modes
    const MODE_OWNSTOCK_ONLY = 0;
    const MODE_PREFER_OWNSTOCK = 1;
    const MODE_PREFER_DROPSHIP = 2;
    const MODE_DROPSHIP_ONLY = 3;

    protected $services = [];

    protected $modules = [];

    protected $moduleIdsByName;

    /** @var DropshipLogger  */
    protected $dropshipLogger;

    /** @var MailTool */
    protected $mailer;

    /** @var OrderTool */
    protected $orderTool;

    protected $config;

    public function __construct(
        DropshipLogger $dropshipLogger,
        MailTool $mailer,
        OrderTool $orderTool,
        Shopware_Components_Config $config)
    {
        $this->dropshipLogger = $dropshipLogger;
        $this->config = $config;
        $this->mailer = $mailer;
        $this->orderTool = $orderTool;
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

    // returns true if dropship status indicates an error

    // or if order was cancelled by supplier
    public function isClarificationRequired(array $order)
    {
        $status = $order['mxcbc_dsi_status'];
        $isError = $status > DropshipManager::DROPSHIP_STATUS_ERROR;
        return $isError || $status == DropshipManager::DROPSHIP_STATUS_CANCELLED;
    }
    public function isTrackingDataComplete(array $order)
    {
        $orderType = $order['mxcbc_dsi_ordertype'];
        $dropshipStatus = $order['mxcbc_dsi_status'];

        if ($orderType == self::ORDER_TYPE_DROPSHIP) {
            // true after all dropship modules delivered their tracking data
            return $dropshipStatus == self::DROPSHIP_STATUS_CLOSED;
        }
        $trackingCodes = explode(',', $order['trackingcode']);
        if ($orderType == self::ORDER_TYPE_OWNSTOCK) {
            // if there is a tracking code then tracking data is complete
            return (! empty($trackingCode));
        }
        // order with products from own stock and dropship products
        if ($dropshipStatus != self::DROPSHIP_STATUS_CLOSED) {
            return false;
        }
        $dropshipCodes = explode(',', $order['trackingcode']);
        // if there are more tracking codes available than provided by dropship modules
        // then own stock tracking codes must be available, too
        return (count($dropshipCodes) < count($trackingCodes));
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

    public function initOrder(int $orderId, bool $resetError = false)
    {
        $order = $this->orderTool->getOrder($orderId);
        $currentStatus = $order['mxcbc_dsi_status'];
        $isErrorStatus = $currentStatus > self::DROPSHIP_STATUS_ERROR;
        if ($currentStatus != self::DROPSHIP_STATUS_OPEN && ! $isErrorStatus) {
            // dropship order was successfully processed already
            return;
        }
        $orderType = 0;
        if (empty($order)) return;
        $details = $this->orderTool->getOrderDetails($orderId);
        foreach ($details as $detail) {
            $articleDetailID = $detail['articleDetailID'];
            if (empty($articleDetailID)) continue;
            $orderDetailId = $detail['detailID'];
            $article = $this->getArticleDetail($articleDetailID);
            $supplier = $article['mxcbc_dsi_supplier'];
            if ($supplier === null) {
                $orderType |= self::ORDER_TYPE_OWNSTOCK;
                $supplier = $this->config->get('shopName');
            } else {
                $orderType |= self::ORDER_TYPE_DROPSHIP;
            }
            $this->setOrderDetailSupplier($supplier, $orderDetailId);
        }
        if (! $isErrorStatus || $resetError) {
            $this->setOrderTypeAndStatus($orderId, $orderType);
        } else {
            $this->setOrderType($orderId, $orderType);
        }

        $this->events->trigger(__FUNCTION__, $this, ['order' => $order, 'resetError' => $resetError]);
    }

    public function isScheduledOrder(array $order) {
        $isDropshipOrder    = $order['mxcbc_dsi_ordertype'] != DropshipManager::ORDER_TYPE_OWNSTOCK;
        $dropshipStatusOpen = $order['mxcbc_dsi_status'] == DropshipManager::DROPSHIP_STATUS_OPEN;
        $isCompletelyPaid   = $order['cleared'] == Status::PAYMENT_STATE_COMPLETELY_PAID;
        return $isDropshipOrder && $dropshipStatusOpen && $isCompletelyPaid;
    }

    // Important: order ID is $order['orderID'], e.g. not $order['id']
    public function sendOrder(array $order)
    {
        if (! $this->isScheduledOrder($order)) return true;
        $context = $this->events->trigger(__FUNCTION__, $this, ['order' => $order])->toArray();
        return $this->setSendOrderStatus($order, $context);
    }

    public function updateTrackingData(array $order)
    {
        $context = $this->events->trigger(__FUNCTION__, $this, ['order' => $order])->toArray();
        $trackingIds = $this->getTrackingIds($order);
        $this->setTrackingIds($order, $trackingIds);
        return $this->setUpdateTrackingDataStatus($order, $context);
    }

    public function getTrackingIds(array $order)
    {
        return $this->events->trigger(__FUNCTION__, $this, ['order' => $order])->toArray();
    }

    public function setTrackingIds(array $order, array $trackingIds)
    {
        // create a unique list of all tracking ids from all dropship modules
        $dropshipTrackingIds = [];
        foreach ($trackingIds as $tracking) {
            if (empty($tracking)) {
                continue;
            }
            $dropshipTrackingIds = array_merge($dropshipTrackingIds, $tracking);
        }
        $dropshipTrackingIds = array_filter(array_unique($dropshipTrackingIds));
        if (empty($dropshipTrackingIds)) {
            return;
        }

        $swTrackingIds = $order['trackingcode'];
        if (empty($swTrackingIds)) {
            $swTrackingIds = $dropshipTrackingIds;
        } else {
            $swTrackingIds = array_map('trim', explode(',', $swTrackingIds));
            $swTrackingIds = array_unique(array_merge($swTrackingIds, $dropshipTrackingIds));
        }

        $this->db->executeUpdate('
            UPDATE 
                s_order o
            INNER JOIN
                s_order_attributes oa ON oa.orderID = o.id
            SET
                o.trackingcode            = :trackingCode,
                oa.mxcbc_dsi_tracking_ids = :trackingIds
            WHERE                
                o.id = :id
            ', [
                'trackingCode' => implode(', ', $swTrackingIds),
                'trackingIds'  => implode(', ', $dropshipTrackingIds),
                'id'           => $order['orderID'],
            ]
        );
    }

    public function setSendOrderStatus(array $order, array $contexts)
    {
        $nrModules = count($contexts);
        // remove null entries
        $contexts = array_filter($contexts);
        // nothing to do
        if (empty($contexts)) {
            return true;
        }

        // if there is only a single dropship module
        if ($nrModules == 1) {
            $context = $contexts[0];
            if ($context['status'] > self::DROPSHIP_STATUS_ERROR && $context['recoverable'] === true) {
                $context['status'] = self::DROPSHIP_STATUS_OPEN;
            }
            $this->setDropshipStatus($order, $context);
            return $context['status'] == self::DROPSHIP_STATUS_SENT;
        }

        // multiple dropship modules
        // at least two different non null $contexts available

        // handle success
        $s = array_unique(array_column($contexts, 'status'));
        $minStatus = min($s);
        $maxStatus = max($s);
        // all module returned DROPSHIP_STATUS_SENT
        if ($minStatus == $maxStatus && $maxStatus == self::DROPSHIP_STATUS_SENT) {
            $context = $contexts[0];
            $context['message'] = 'Dropship Auftrag erfolgreich versandt.';
            $this->setDropshipStatus($order, $context);
            return true;
        }

        // handle errors
        // at least one module returned an error code, which may be revocerable or not
        $errorMessage = 'Beim Versand des Dropship Auftrags ist ein Fehler aufgetreten. Siehe Log. ';
        foreach ($contexts as $context) {
            $status = $context['status'];
            if ($status == self::DROPSHIP_STATUS_SENT) {
                continue;
            }
            if ($status > self::DROPSHIP_STATUS_ERROR) {
                if ($context['recoverable'] === true) {
                    $context['message'] = $errorMessage . 'Automatischer Neuversuch';
                } else {
                    $context['message'] = $errorMessage . 'Manuelles Eingreifen erforderlich.';
                    break;
                }
            }
        }
        $this->setDropshipStatus($order, $context);
        return false;
    }

    public function setUpdateTrackingDataStatus(array $order, array $contexts)
    {
        // modules not involved in the current order processing return null, array_column silently ignores null entries
        $s = array_unique(array_column($contexts, 'status'));
        if (empty($s) || count($s) > 1) {
            return true;
        }
        // status progress happens only if all modules return expected status or null (which means not applicable)
        if ($s[0] != self::DROPSHIP_STATUS_CLOSED) {
            return false;
        }
        $context = array_values($contexts)[0];
        $context['message'] = 'Dropship Tracking Daten vollständig.';
        $this->setDropshipStatus($order, $context);
        return true;
    }

    public function setDropshipStatus(array $order, array $context)
    {
        $status = $context['recoverable'] ? $order['mxcbc_dsi_status'] : $context['status'];
        $this->dbSetDropshipStatus($order['orderID'], $status, $context['message']);
    }

    protected function dbSetDropshipStatus(int $orderId, int $status, string $message)
    {
        $this->db->executeUpdate('
            UPDATE 
                s_order_attributes oa
            SET
                oa.mxcbc_dsi_status = :status,
                oa.mxcbc_dsi_message = :message
            WHERE                
                oa.orderID = :id
            ', [
                'status'  => $status,
                'message' => $message,
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
        /** @var ListenerAggregateInterface $listener */
        $listener = $services->get('DropshipEventListener');
        $listener->attach($this->events);
    }

    public function getNotificationContext(string $supplier, string $caller, $key, array $order = null)
    {
        $context = @$this->classConfig['notification_context'][$key][$caller];
        if ($context === null) {
            return null;
        }

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
    public function notifyStatus(array $context, array $order, bool $sendMail = true)
    {
        if ($sendMail) {
            $this->mailer->sendNotificationMail($order['orderID'], $context, $this->classConfig['notification_address']);
        }
        $this->logStatus($context, $order);
    }

    public function getOriginator()
    {
        return [
            'COMPANY'        => $this->config->get('mxcbc_dsi_ic_company', 'vapee.de'),
            'COMPANY2'       => $this->config->get('mxcbc_dsi_ic_department', 'maxence operations gmbh'),
            'FIRSTNAME'      => $this->config->get('mxcbc_dsi_ic_first_name', 'Frank'),
            'LASTNAME'       => $this->config->get('mxcbc_dsi_ic_last_name', 'Hein'),
            'STREET_ADDRESS' => $this->config->get('mxcbc_dsi_ic_street', 'Am Weißen Stein 1'),
            'POSTCODE'       => $this->config->get('mxcbc_dsi_ic_zip', '41541'),
            'CITY'           => $this->config->get('mxcbc_dsi_ic_city', 'Dormagen'),
            'COUNTRY_CODE'   => $this->config->get('mxcbc_dsi_ic_country_code', 'DE'),
            'EMAIL'          => $this->config->get('mxcbc_dsi_ic_mail', 'info@vapee.de'),
            'TELEPHONE'      => $this->config->get('mxcbc_dsi_ic_phone', '02133-259925')
        ];
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
        return $context;
    }

    protected function setOrderDetailSupplier($supplier, $orderDetailId): void
    {
        Shopware()->Db()->executeUpdate('
            UPDATE 
                s_order_details_attributes oda
            SET 
                oda.mxcbc_dsi_supplier = :supplier
            WHERE 
                oda.detailID = :id
            ', [
                'supplier' => $supplier,
                'id'       => $orderDetailId
            ]
        );
    }

    protected function getArticleDetail(int $detailId)
    {
        return $this->db->fetchRow('
            SELECT 
                * 
            FROM 
                s_articles_details ad
            LEFT JOIN 
                s_articles_attributes ada ON ada.articledetailsID = ad.id
            WHERE 
                ad.id = :articleId
        ', ['articleId' => $detailId]);
    }

    protected function setOrderTypeAndStatus(int $orderId, int $orderType): void
    {
        // If the order does not contain dropship products, drophship status is 'closed'
        $initialStatus = $this->initialOrderStatus[$orderType];
        $this->db->executeUpdate('
            UPDATE s_order_attributes oa
            SET 
                oa.mxcbc_dsi_ordertype = :orderType,
                oa.mxcbc_dsi_status    = :dropshipStatus,
                oa.mxcbc_dsi_message   = :dropshipMessage
                
            WHERE oa.orderID = :orderId
        ', [
                'orderType'       => $orderType,
                'dropshipStatus'  => $initialStatus['status'],
                'dropshipMessage' => $initialStatus['message'],
                'orderId'         => $orderId,
            ]
        );
    }

    protected function setOrderType(int $orderId, int $orderType)
    {
        $this->db->executeUpdate('
            UPDATE s_order_attributes oa
            SET 
                oa.mxcbc_dsi_ordertype = :orderType
            WHERE oa.orderID = :orderId
        ', [
                'orderType'       => $orderType,
                'orderId'         => $orderId,
            ]
        );
    }


    public function getMailAddress()
    {
        return $this->classConfig['notification_address'];
    }

    public function getInitialOrderStatus()
    {
        return $this->initialOrderStatus;
    }
}