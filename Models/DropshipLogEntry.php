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

    /** @ORM\Column(name="order_number", type="string", nullable=true) */
    private $orderNumber;

    /** @ORM\Column(type="string", nullable=true) */
    private $productNumber;

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

    public function getProductNmber() { return $this->productNumber; }
    public function setProductNumber($productNumber) { $this->productNumber = $productNumber; }

    public function getQuantity() { return $this->quantity; }
    public function setQuantity($quantity) { $this->quantity = $quantity; }

    public function set($level, $module, $message, $orderNumber = null, $productNumber = null, $quantity = null)
    {
        $this->level = $level;
        $this->module = $module;
        $this->message = $message;
        $this->orderNumber = $orderNumber;
        $this->quantity = $quantity;
        $this->productNumber = $productNumber;
    }
}
