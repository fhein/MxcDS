<?php

namespace MxcDropship\Exception;

use MxcCommons\Plugin\Plugin;
use RuntimeException;

class DropshipException extends RuntimeException
{
    // module registration and validation
    const UNREGISTERED_MODULE           = 100;
    const DUPLICATE_MODULE_ID           = 101;
    const INVALID_CONFIG                = 102;
    const INVALID_MODULE                = 103;
    const MISSING_MODULE_SERVICE        = 104;

    const MODULE_CLASS_EXIST            = 1;
    const MODULE_CLASS_IDENTITY         = 2;
    const MODULE_CLASS_INSTALLED        = 3;
    const MODULE_CLASS_SERVICES         = 4;

    // module API exception
    const MODULE_API_NO_RESPONSE        = 200;
    const MODULE_API_JSON_DECODE        = 201;
    const MODULE_API_JSON_ENCODE        = 202;
    const MODULE_API_INVALID_XML_DATA   = 203;
    const MODULE_API_HTTP_STATUS        = 204;
    const MODULE_API_SUPPLIER_ERRORS    = 205;

    // order send exceptions

    const ORDER_POSITIONS_ERROR         = 300;
    const ORDER_RECIPIENT_ADDRESS_ERROR = 301;
    const ORDER_DROPSHIP_NOK            = 302;


    // these will not be thrown

    const PRODUCT_NOT_AVAILABLE     = 2101;
    const PRODUCT_UNKNOWN           = 2102;
    const PRODUCT_NUMBER_MISSING    = 2103;
    const PRODUCT_OUT_OF_STOCK      = 2104;
    const POSITION_EXCEEDS_STOCK    = 2105;

    const RECIPIENT_COMPANY_TOO_LONG            = 2201;
    const RECIPIENT_COMPANY2_TOO_LONG           = 2202;
    const RECIPIENT_FIRST_NAME_TOO_SHORT        = 2203;
    const RECIPIENT_LAST_NAME_TOO_SHORT         = 2204;
    const RECIPIENT_NAME_TOO_LONG               = 2205;
    const RECIPIENT_STREET_ADDRESS_TOO_SHORT    = 2206;
    const RECIPIENT_STREET_ADDRESS_TOO_LONG     = 2207;
    const RECIPIENT_ZIP_TOO_SHORT               = 2208;
    const RECIPIENT_CITY_TOO_SHORT              = 2209;
    const RECIPIENT_INVALID_COUNTRY_CODE        = 2210;

    protected static $addressErrorMessages = [
        self::RECIPIENT_COMPANY_TOO_LONG         => 'Der Firmenname darf maximal 30 Zeichen lang sein.',
        self::RECIPIENT_COMPANY2_TOO_LONG        => 'Der Firmenname 2 darf maximal 30 Zeichen lang sein.',
        self::RECIPIENT_FIRST_NAME_TOO_SHORT     => 'Der Vorname muss mindestens aus zwei Zeichen bestehen.',
        self::RECIPIENT_LAST_NAME_TOO_SHORT      => 'Der Nachname muss mindestens aus zwei Zeichen bestehen.',
        self::RECIPIENT_NAME_TOO_LONG            => 'Vorname und Nachname dürfen zusammen nicht mehr als 34 Zeichen enthalten.',
        self::RECIPIENT_STREET_ADDRESS_TOO_SHORT => 'Die Straße mit Hausnummer muss mindestens aus fünf Zeichen bestehen.',
        self::RECIPIENT_STREET_ADDRESS_TOO_LONG  => 'Die Straße mit Hausnummer darf höchstens aus 35 Zeichen bestehen.',
        self::RECIPIENT_ZIP_TOO_SHORT            => 'Die Postleitzahl muss mindestens aus vier Zeichen bestehen.',
        self::RECIPIENT_CITY_TOO_SHORT           => 'Die Stadt muss mindestens aus drei Zeichen bestehen.',
        self::RECIPIENT_INVALID_COUNTRY_CODE     => 'Ungültiger Ländercode.',
    ];

    protected $supplier;
    protected $httpStatus;
    protected $supplierErrors;
    protected $positionErrors;
    protected $addressErrors;
    protected $dropshipInfo;


    // initializers regarding module registration and validation

    public static function fromMissingModuleService(string $service) {
        $code = self::MISSING_MODULE_SERVICE;
        $msg = sprintf('Dropship module does not provide required module service %s.', $service);
        return new self($msg, $code);
    }

    public static function fromUnregisteredModule(string $id) {
        $code = self::UNREGISTERED_MODULE;
        $msg = sprintf('Dropship module %s is not registered.', $id);
        return new self($msg, $code);
    }

    public static function fromDuplicateModule(string $id) {
        $code = self::DUPLICATE_MODULE_ID;
        $msg = sprintf('Duplicate dropship module %s.', $id);
        return new self($msg, $code);
    }

    public static function fromInvalidConfig(string $what, $item) {
        $code = self::INVALID_CONFIG;
        if (is_string($item)) {
            $msgx = 'Provided string is empty.';
        } else {
            $msgx = sprintf('Non empty string expected, but got a %s instead.',
            is_object($item) ? get_class($item) : gettype($item));
        }
        $msg = sprintf('Invalid config setting %s. %s', $what, $msgx);
        return new self($msg, $code);
    }

    public static function fromInvalidModule($what, $module)
    {
        $code = self::INVALID_MODULE;
        $msg = 'Unknown error.';
        switch ($what) {
            case self::MODULE_CLASS_EXIST:
                $moduleClass = $module . '\\' . $module;
                $msg = sprintf('Module class %s does not exist.', $moduleClass);
                break;
            case self::MODULE_CLASS_IDENTITY:
                $msg = sprintf(
                    'Invalid module class. Expected %s, but got %s.',
                    Plugin::class,
                    is_object($module) ? get_class($module) : gettype($module)
                );
                break;
            case self::MODULE_CLASS_INSTALLED:
                $msg = sprintf('Shopware plugin for module %s is not installed.', $module);
                break;
            case self::MODULE_CLASS_SERVICES:
                $msg = 'Module does not provide access to its services management. Static method getServices() missing.';
                break;
        }
        return new self($msg, $code);
    }

    // initializers regarding module API

    public static function fromInvalidXML(string $supplier)
    {
        $msg = sprintf('Module %s:<br/> Invalid XML data received', $supplier);
        $code = self::MODULE_API_INVALID_XML_DATA;
        $e = new DropshipException($msg, $code);
        $e->setSupplier($supplier);
        return $e;
    }

    public static function fromJsonEncode(string $supplier) {
        $msg = sprintf('Module %s:<br/> Failed to encode XML data to JSON.', $supplier);
        $code = self::MODULE_API_JSON_ENCODE;
        $e = new DropshipException($msg, $code);
        $e->setSupplier($supplier);
        return $e;
    }

    public static function fromJsonDecode(string $supplier) {
        $msg = sprintf('Module %s:<br/>Failed to decode JSON data.', $supplier);
        $code = self::MODULE_API_JSON_DECODE;
        $e = new DropshipException($msg, $code);
        $e->setSupplier($supplier);
        return $e;
    }

    public static function fromSupplierErrors(string $supplier, array $errors) {
        $code = self::MODULE_API_SUPPLIER_ERRORS;
        $msg = 'Supplier API error codes available.';
        $e =  new DropshipException($msg, $code);
        $e->setSupplierErrors($errors);
        $e->setSupplier($supplier);
        return $e;
    }

    public static function fromHttpStatus(string $supplier, int $status) {
        $code = $status;
        $msg = sprintf('InnoCigs API: <br\>HTTP Status: %u', $status);
        $e = new DropshipException($msg, $code);
        $e->setHttpStatus($status);
        $e->setSupplier($supplier);
        return $e;
    }

    public static function fromInvalidOrderPositions($supplier, $positionErrors)
    {
        $msg = 'Invalid order positions.';
        $e = new self($msg, self::ORDER_POSITIONS_ERROR);
        $e->setPositionErrors($positionErrors);
        $e->setSupplier($supplier);
        return $e;
    }

    public static function fromInvalidRecipientAddress($supplier, array $err)
    {
        $msg = 'Invalid recipient address.';
        $e = new self($msg, self::ORDER_RECIPIENT_ADDRESS_ERROR);

        $errors = [];
        foreach ($err as $error) {
            $errors[] = [
                'code' => $error,
                'message' => self::$addressErrorMessages[$error],
            ];
        }
        $e->setAddressErrors($errors);
        $e->setSupplier($supplier);
        return $e;
    }

    public static function fromDropshipNOK($supplier, array $errors, array $info)
    {
        $code = self::ORDER_DROPSHIP_NOK;
        $msg = 'InnoCigs API error codes available.';
        $e =  new self($msg, $code);
        $e->setSupplierErrors($errors);
        $e->setDropshipInfo($info);
        $e->setSupplier($supplier);
        return $e;
    }

    public function setSupplierErrors(array $errors)
    {
        // a single error was returned
        if (isset($errors['ERROR']['CODE'])) {
            $error = $errors['ERROR'];
            $this->supplierErrors[] = [
                'code' => $error['CODE'],
                'message' => $error['MESSAGE'],
            ];
            return;
        }
        // multiple errors were returned
        foreach ($errors['ERROR'] as $error)
        {
            $this->supplierErrors[] = [
                'code' => $error['CODE'],
                'message' => $error['MESSAGE'],
            ];
        }
    }

    public function getSupplierErrors()
    {
        return $this->supplierErrors;
    }

    public function getHttpStatus()
    {
        return $this->httpStatus;
    }

    public function setHttpStatus($httpStatus)
    {
        $this->httpStatus = $httpStatus;
    }

    public function setSupplier(string $supplier)
    {
        $this->supplier = $supplier;
    }

    public function setPositionErrors(array $positionErrors) {
        $this->positionErrors = $positionErrors;
    }

    public function getPositionErrors() {
        return $this->positionErrors;
    }

    public function setAddressErrors($errors) {
        $this->addressErrors = $errors;
    }

    public function getAddressErrors()
    {
        return $this->addressErrors;
    }

    public function setDropshipInfo($info)
    {
        $this->dropshipInfo = $info;
    }

    public function getDropshipInfo()
    {
        return $this->dropshipInfo;
    }

}