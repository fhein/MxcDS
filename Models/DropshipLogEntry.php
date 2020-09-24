<?php

namespace MxcDropship\Models;

use Doctrine\ORM\Mapping as ORM;
use MxcCommons\Toolbox\Models\PrimaryKeyTrait;
use MxcCommons\Toolbox\Models\TrackCreationAndUpdateTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_mxcbc_dropship_log")
 */
class DropshipLogEntry extends ModelEntity  {

    use PrimaryKeyTrait;
    use TrackCreationAndUpdateTrait;

    /** @ORM\Column(type="integer", nullable=false) */
    private $level;

    /** @ORM\Column(type="string", nullable=false) */
    private $module;

    /** @ORM\Column(type="string", nullable=false) */
    private $message;

    /** @ORM\Column(name="order_id", type="integer", nullable=true) */
    private $orderId;

    /** @ORM\Column(name="order_number", type="string", nullable=true) */
    private $orderNumber;

    /** @ORM\Column(type="string", nullable=true) */
    private $product;

    /** @ORM\Column(type="integer", nullable=true) */
    private $quantity;

    public function getLevel() { return $this->level; }
    public function setLevel($level) { $this->level = $level; }

    public function getModule() { return $this->module; }
    public function setModule($module) { $this->module = $module; }

    public function getMessage() { return $this->message; }
    public function setMessage($message) { $this->message = $message; }

    public function getOrderNumber() { return $this->orderNumber; }
    public function setOrderNumber($orderNumber) { $this->orderNumber = $orderNumber; }

    public function getOrderId() { return $this->orderId; }
    public function setOrderId($orderId) { $this->orderId = $orderId; }

    public function getProduct() { return $this->product; }
    public function setProduct($product) { $this->product = $product; }

    public function getQuantity() { return $this->quantity; }
    public function setQuantity($quantity) { $this->quantity = $quantity; }

    public function set($level, $module, $message, $orderId = null, $orderNumber = null, $product = null, $quantity = null)
    {
        $this->level = $level;
        $this->module = $module;
        $this->message = $message;
        $this->orderNumber = $orderNumber;
        $this->quantity = $quantity;
        $this->product = $product;
        $this->orderId = $orderId;
    }
}
