<?php

use MxcDropship\Dropship\DropshipManager;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Status;
use MxcDropship\MxcDropship;

class Shopware_Controllers_Backend_MxcDropship extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    private $statusPanelTemplate = '<div style="width:100%%;text-align:center;background:%s;color:%s;padding:5px;">%s</div>';

    protected $db;

    protected $panels;
    public function getWhitelistedCSRFActions()
    {
        return [
            'getDropshipStatusPanel'
        ];
    }

    protected function getDropshipStatusPanel($status, string $message = null)
    {
        $panel = $this->getPanels()[$status];
        $this->view->assign([
            'panel' => sprintf(
                $this->statusPanelTemplate,
                $panel['background'],
                $panel['text'],
                $message ?? $panel['message'])
        ]);
        return null;
    }

    public function getDropshipStatusPanelAction()
    {
        $nrModules = $this->getDb()->fetchOne('SELECT COUNT(id) FROM s_mxcbc_dropship_module');
        if ($nrModules == 0) {
            return $this->getDropshipStatusPanel($this->panels['NO_DROPSHIP_MODULE']);
        }

        $orderId = $this->Request()->getParam('orderId');
        $attr = $this->getOrderStatusInfo($orderId);
        $orderType = $attr['orderType'];
        if ($orderType == DropshipManager::ORDER_TYPE_OWNSTOCK) {
            return $this->getDropshipStatusPanel('OWNSTOCK_ONLY');
        }
        $status = $attr['dropshipStatus'];
        $message = $attr['message'];
        return $this->getDropshipStatusPanel($status, $message);
    }

    protected function getOrderStatusInfo(int $orderId)
    {
        return $this->getDb()->fetchAll('
            SELECT 
                mxcbc_dsi_status as dropshipStatus,
                mxcbc_dsi_ordertype as orderType,
                mxcbc_dsi_message as message
                
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

// companion junk

//$orderId = $this->Request()->getParam('orderId');
//
//    // Check if order has dropships
//if ($fullOrderDetails = $this->getOrderArticleAttributesByDetailId($orderId)) {
//foreach ($fullOrderDetails as $fullOrderDetail) {
//if (!empty($fullOrderDetail['dc_name_short'])) {
//$orderHasDropship = true;
//}
//}
//}
//
//if (!$orderHasDropship) {
//    $html = $this->getDropshipStatusPanel('#434343', 'Bestellung ohne Dropship-Artikel.')
//        } else {
//    $orderDropshipStatus = $this->getOrderDropshipStatus($orderId);
//    $orderDropshipIsActive = $this->getOrderDropshipIsActive($orderId);
//    $fullOrder = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId);
//
//    if ($fullOrder->getPaymentStatus()->getName() != self::PAYMENT_COMPLETELY_PAID && Shopware()->Config()->get('dc_auto_order')) {
//        // orange
//        $html = '<div style="width:100%;text-align:center;background:#ff7e00;color:white;padding:5px;">Bei Zahlungseingang den Status bitte auf "Komplett bezahlt" ändern.<br>Die Bestellung wird anschließend automatisch an den Großhandelspartner verschickt.</div>';
//    } elseif ($fullOrder->getPaymentStatus()->getName() != self::PAYMENT_COMPLETELY_PAID && !Shopware()->Config()->get('dc_auto_order')) {
//        $html = '<div style="width:100%;text-align:center;background:#ff7e00;color:white;padding:5px;">Bei Zahlungseingang den Status bitte auf "Komplett bezahlt" ändern.<br>Die Bestellung können Sie anschließend im Tab Positionen an den Großhandelspartner verschicken.</div>';
//    } else {
//        if ($orderDropshipStatus == 100 && $orderDropshipIsActive == 1) {
//            $html = '<div style="width:100%;text-align:center;background:limegreen;color:white;padding:5px;">Dropshipping-Auftrag an Großhandelspartner übermittelt.</div>';
//        } else if ($orderDropshipStatus == 200 && $orderDropshipIsActive == 1) {
//            $html = '<div style="width:100%;text-align:center;background:#ff0090;color:white;padding:5px;">Dropshipping-Auftrag Tracking-Informationen vorhanden. Bitte kontrollieren Sie Ihre E-Mails.</div>';
//        } else if ($orderDropshipStatus == 0 && $orderDropshipIsActive == 1) {
//            $html = '<div style="width:100%;text-align:center;background:#009fe3;color:white;padding:5px;">Dropshipping-Auftrag ist zur Übermittlung an Großhandelspartner vorgemerkt.</div>';
//        } else if ($orderDropshipIsActive == 0) {
//            $html = '<div style="width:100%;text-align:center;background:#ff7e00;color:white;padding:5px;">Eine Übermittlung des Dropshipping-Auftrages an den Großhandelspartner ist jetzt möglich. Bitte prüfen Sie die Bestellpositionen<br>des Auftrages und geben den Auftrag mit einem Klick auf "Dropshipping-Bestellung aufgeben" für die Bestellng frei.</div>';
//        }
//
//        $orderArticleAttributes = $this->getOrderArticleAttributesByDetailId($orderId);
//        foreach ($orderArticleAttributes as $orderArticleAttribute) {
//            if ($orderArticleAttribute['dc_dropship_status'] == 'NOK' && $orderDropshipStatus == -100) {
//                $html = '<div style="width:100%;text-align:center;background:#b10000;color:white;padding:5px;">Fehler in der Übermittlung des Dropshipping-Auftrages. Bitte kontrollieren Sie Ihre Mails.</div>';
//            }
//        }
//    }
//}
//
//$this->View()->assign(array(
//    'html' => $html
//));

