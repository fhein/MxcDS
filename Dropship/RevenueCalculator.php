<?php

namespace MxcDropship\Dropship;

use MxcCommons\Plugin\Service\ClassConfigAwareTrait;
use MxcCommons\Plugin\Service\DatabaseAwareTrait;
use MxcCommons\ServiceManager\AugmentedObject;
use MxcCommons\Toolbox\Shopware\OrderTool;
use MxcCommons\Toolbox\Shopware\TaxTool;
use Shopware_Components_Config;


class RevenueCalculator implements AugmentedObject
{
    use DatabaseAwareTrait;
    use ClassConfigAwareTrait;

    /** @var OrderTool */
    protected $orderTool;
    /** @var DropshipManager */
    protected $dropshipManager;
    /** @var \Shopware_Components_Config */
    protected $config;

    public function __construct(DropshipManager $dropshipManager, OrderTool $orderTool, Shopware_Components_Config $config)
    {
        $this->orderTool = $orderTool;
        $this->dropshipManager = $dropshipManager;
        $this->config = $config;
    }

    public function calculate(int $orderId)
    {
        $dropshipCost = $this->dropshipManager->getCost($orderId);
        $productCost = $this->getOwnStockPositionCost($orderId);
        if ($productCost > 0) {
            $dropshipCost[] = [
                'product' => $productCost,
                'shipping' => $this->classConfig['orderCost']['ownstock']['DHL']
            ];
        }
        // calculate paypal cost
        $order = $this->orderTool->getOrder($orderId);
        $paypalCost = $this->getPaypalCost($order);
        $invoiced = $order['invoice_amount'];
        $vatFactor = 1 + TaxTool::getCurrentVatPercentage() / 100;
        $received = $invoiced - $paypalCost;
        $receivedNet = round($received / $vatFactor, 2);

        $productCost = 0;
        $shippingCost = 0;
        foreach ($dropshipCost as $cost) {
            $productCost += $cost['product'];
            $shippingCost += $cost['shipping'];
        }
        $totalCost = $productCost + $shippingCost;
        $revenue = $receivedNet - $totalCost;
        $margin = round($revenue / $receivedNet * 100, 2);
        return [
            'shippingCost' => number_format($shippingCost,2, ',', '.'),
            'productCost'  => number_format($productCost,2, ',', '.'),
            'totalCost' => number_format($totalCost,2, ',', '.'),
            'paypalCost' => number_format($paypalCost,2, ',', '.'),
            'amountInvoiced'  => number_format($invoiced,2, ',', '.'),
            'amountReceived' => number_format($invoiced - $paypalCost,2, ',', '.'),
            'amountReceivedNet' => number_format($receivedNet,2, ',', '.'),
            'margin' => number_format($margin,2, ',', '.'),
            'revenue' => number_format($revenue,2, ',', '.'),
        ];
    }

    protected function getOwnStockPositionCost($orderId)
    {
        $shopName = $this->config->get('shopName');
        $details = $this->dropshipManager->getSupplierOrderDetails($shopName, $orderId);

        // calculate product cost and shipping cost of order positions coming from own stock
        $productCost = 0;
        foreach ($details as $detail) {
            $productInfo = $this->getProductInfo($detail['articleDetailID']);
            $productCost += $productInfo['purchasePrice'] * $detail['quantity'];
        }
        return round($productCost,2);
    }

    protected function getPaypalCost(array $order)
    {
        $paypalCost = 0;
        if ($this->orderTool->isPaypal($order['paymentID'])) {
            $paypal = $this->classConfig['orderCost']['paypal'];
            $paypalCost = $paypal['base'] + $paypal['percentage'] / 100 * $order['invoice_amount'];
        }
        return round($paypalCost, 2);
    }

    private function getProductInfo(int $articleDetailId)
    {
        return $this->db->fetchAll('
            SELECT 
                ad.ordernumber as productNumber,
                ad.purchaseprice as purchasePrice 
            FROM 
                s_articles_details ad
            WHERE 
                ad.id = :articleDetailId
        ', ['articleDetailId' => $articleDetailId])[0];
    }
}