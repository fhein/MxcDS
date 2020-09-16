<?php

namespace MxcDropship\Exception;

use MxcCommons\Plugin\Plugin;
use RuntimeException;

class DropshipException extends RuntimeException
{
    // module registration and validation
    const INVALID_MODULE_ID             = 100;
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

    protected $supplierId;
    protected $httpStatus;
    protected $supplierErrors;

    // initializers regarding module registration and validation

    public static function fromMissingModuleService(string $service) {
        $code = self::MISSING_MODULE_SERVICE;
        $msg = sprintf('Dropship module does not provide required module service %s.', $service);
        return new self($msg, $code);
    }

    public static function fromInvalidModuleId(int $id) {
        $code = self::INVALID_MODULE_ID;
        $msg = sprintf('Dropship module with id %u is not registered.', $id);
        return new self($msg, $code);
    }

    public static function fromDuplicateModuleId(int $id) {
        $code = self::DUPLICATE_MODULE_ID;
        $msg = sprintf('Duplicate dropship module id %u.', $id);
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

    public static function fromInvalidXML(int $supplierId) {
        $msg = 'InnoCigs API: <br/>Invalid XML data received.';
        $code = self::MODULE_API_INVALID_XML_DATA;

        return new DropshipException($msg, $code);
    }

    public static function fromJsonEncode(int $supplierId) {
        $msg = 'InnoCigs API: <br/>Failed to encode XML data to JSON.';
        $code = self::MODULE_API_JSON_ENCODE;
        return new DropshipException($msg, $code);
    }

    public static function fromJsonDecode(int $supplierId) {
        $msg = 'InnoCigs API: <br/>Failed to decode JSON data.';
        $code = self::MODULE_API_JSON_DECODE;
        return new DropshipException($msg, $code);
    }

    public static function fromSupplierErrors(int $supplierId, array $errors) {
        $code = self::MODULE_API_SUPPLIER_ERRORS;
        $msg = 'Supplier API error codes available.';
        $e =  new DropshipException($msg, $code);
        $e->setSupplierErrors($errors);
        return $e;
    }

    public static function fromHttpStatus(int $supplierId, int $status) {
        $code = $status;
        $msg = sprintf('InnoCigs API: <br\>HTTP Status: %u', $status);
        $e = new DropshipException($msg, $code);
        $e->setHttpStatus($status);
    }

    public function setSupplierErrors(array $errors)
    {
        // a single error was returned
        if (isset($errors['ERROR']['CODE'])) {
            $this->supplierErrors[] = $errors['ERROR'];
            return;
        }
        // multiple errors were returned
        foreach ($errors['ERROR'] as $error)
        {
            $this->supplierErrors[] = $error;
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

    public function setHttpStatus($httpStatus): void
    {
        $this->httpStatus = $httpStatus;
    }

}