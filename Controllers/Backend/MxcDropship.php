<?php

use MxcDropship\Dropship\DropshipManager;
use Shopware\Components\CSRFWhitelistAware;
use MxcDropship\MxcDropship;

class Shopware_Controllers_Backend_MxcDropship extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    private $statusPanelTemplate = '<div style="width:100%%;text-align:center;background:%s;color:%s;padding:5px;">%s</div>';

    protected $db;

    protected $panels;

    protected $panelKey = [
        DropshipManager::ORDER_TYPE_OWNSTOCK => 'OWNSTOCK_ONLY',
        DropshipManager::ORDER_TYPE_DROPSHIP => 'DROPSHIP_ONLY',
        DropshipManager::ORDER_TYPE_DROPSHIP | DropshipManager::ORDER_TYPE_OWNSTOCK => 'DROPSHIP_OWNSTOCK',
    ];

    public function getWhitelistedCSRFActions()
    {
        return [
            'getDropshipStatusPanel'
        ];
    }

    protected function getDropshipStatusPanel($status, string $message = null, string $color = null)
    {
        $panel = $this->getPanels()[$status];
        $this->view->assign([
            'panel' => sprintf(
                $this->statusPanelTemplate,
                $color ?? $panel['background'],
                $panel['text'],
                $message ?? $panel['message'])
        ]);
        return null;
    }

    public function getDropshipStatusPanelAction()
    {
        $nrModules = $this->getDb()->fetchOne('SELECT COUNT(id) FROM s_mxcbc_dropship_module');
        if ($nrModules == 0) {
            return $this->getDropshipStatusPanel('NO_DROPSHIP_MODULE');
        }

        $orderId = $this->Request()->getParam('orderId');
        $attr = $this->getOrderStatusInfo($orderId);
        $orderType = $attr['orderType'];
        return $this->getDropshipStatusPanel($this->panelKey[$orderType]);
    }

    protected function getOrderStatusInfo(int $orderId)
    {
        return $this->getDb()->fetchAll('
            SELECT 
                o.cleared as paymentStatus,
                oa.mxcbc_dsi_ic_status as dropshipStatus,
                oa.mxcbc_dsi_ordertype as orderType,
                oa.mxcbc_dsi_message as message
                
            FROM s_order o
            LEFT JOIN s_order_attributes oa ON o.id = oa.orderID
            WHERE o.id = :orderId
        ', ['orderId' => $orderId])[0];
    }

    protected function getDb() {
        return $this->db ?? $this->db = MxcDropship::getServices()->get('db');
    }

    protected function getPanels() {
        return $this->panels ?? $this->panels = MxcDropship::getPanelConfig();
    }
}
