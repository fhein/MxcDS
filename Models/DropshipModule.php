<?php

namespace MxcDropship\Models;

use Doctrine\ORM\Mapping as ORM;
use MxcCommons\Toolbox\Models\PrimaryKeyTrait;
use MxcCommons\Toolbox\Models\TrackCreationAndUpdateTrait;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="s_mxcbc_dropship_module")
 */
class DropshipModule extends ModelEntity
{
    use PrimaryKeyTrait;
    use TrackCreationAndUpdateTrait;

    /** @ORM\Column(name="supplier_id", type="integer", nullable=false) */
    private $supplierId;

    /** @ORM\Column(type="string", nullable=false) */
    private $supplier;

    /** @ORM\Column(type="string", nullable=false) */
    private $name;

    /** @ORM\Column(type="string", nullable=false) */
    private $plugin;

    /** @ORM\Column(type="boolean", nullable=false) */
    private $active = false;

    // these properties are run time
    private $services;
    private $moduleClass;

    public function getSupplierId() { return $this->supplierId; }
    public function setSupplierId($supplierId) { $this->supplierId = $supplierId; }

    public function getSupplier() { return $this->supplier; }
    public function setSupplier($supplier) { $this->supplier = $supplier; }

    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; }

    public function getPlugin() { return $this->plugin; }
    public function setPlugin($plugin) { $this->plugin = $plugin; }

    public function isActive() { return $this->active; }
    public function setActive(bool $active) { $this->active = $active; }

    public function setServices($services) { $this->services = $services; }
    public function getServices() { return $this->services; }

    public function setModuleClass($moduleClass) { $this->moduleClass = $moduleClass; }
    public function getModuleClass() { return $this->moduleClass; }
}

