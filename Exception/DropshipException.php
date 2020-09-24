<?php

namespace MxcDropship\Exception;

use MxcCommons\Plugin\Plugin;
use RuntimeException;
use Throwable;

class DropshipException extends RuntimeException
{
    // module registration and validation
    const UNREGISTERED_MODULE = 100;
    const DUPLICATE_MODULE_ID = 101;
    const INVALID_CONFIG = 102;
    const INVALID_MODULE = 103;
    const MISSING_MODULE_SERVICE = 104;

    const MODULE_CLASS_EXIST = 1;
    const MODULE_CLASS_IDENTITY = 2;
    const MODULE_CLASS_INSTALLED = 3;
    const MODULE_CLASS_SERVICES = 4;

    // module API exception
    const MODULE_API_FAILURE = 200;
    const MODULE_API_XML_ERROR = 201;
    const MODULE_API_SUPPLIER_ERRORS = 202;

    // order send exceptions

    const ORDER_POSITIONS_ERROR = 300;
    const ORDER_RECIPIENT_ADDRESS_ERROR = 301;

    // these will not be thrown

    const XML_INVALID = 2001;
    const XML_JSON_DECODE_FAILED = 2002;
    const XML_JSON_ENCODE_FAILED = 2003;

    const PRODUCT_NOT_AVAILABLE = 2101;
    const PRODUCT_UNKNOWN = 2102;
    const PRODUCT_NUMBER_MISSING = 2103;
    const PRODUCT_OUT_OF_STOCK = 2104;
    const POSITION_EXCEEDS_STOCK = 2105;

    const RECIPIENT_COMPANY_TOO_LONG = 2201;
    const RECIPIENT_COMPANY2_TOO_LONG = 2202;
    const RECIPIENT_FIRST_NAME_TOO_SHORT = 2203;
    const RECIPIENT_LAST_NAME_TOO_SHORT = 2204;
    const RECIPIENT_NAME_TOO_LONG = 2205;
    const RECIPIENT_STREET_ADDRESS_TOO_SHORT = 2206;
    const RECIPIENT_STREET_ADDRESS_TOO_LONG = 2207;
    const RECIPIENT_ZIP_TOO_SHORT = 2208;
    const RECIPIENT_CITY_TOO_SHORT = 2209;
    const RECIPIENT_INVALID_COUNTRY_CODE = 2210;

    const API_HTTP_STATUS_ERROR = 2301;
    const API_NO_RESONSE = 2302;

    protected static $xmlErrorMessages = [
        self::XML_INVALID            => 'Die vom Server gelieferten XML-Daten sind ungültig.',
        self::XML_JSON_DECODE_FAILED => 'Die Dekodierung der XML Daten ist fehlgeschlagen (json_decode).',
        self::XML_JSON_ENCODE_FAILED => 'Die Dekodierung der XML Daten ist fehlgeschlagen (json_encode).',
    ];

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

    protected static $apiErrorMessages = [
        self::API_HTTP_STATUS_ERROR => 'Request liefert HTTP-Fehlerstatus %u.',
        self::API_NO_RESONSE        => 'Der Server antwortet nicht.',
    ];

    protected $supplier;
    protected $httpStatus;
    protected $apiErrors;
    protected $xmlErrors;
    protected $supplierErrors;
    protected $positionErrors;
    protected $addressErrors;

    // initializers regarding module registration and validation

    public static function fromMissingModuleService(string $service)
    {
        $code = self::MISSING_MODULE_SERVICE;
        $msg = sprintf('Dropship module does not provide required module service %s.', $service);
        return new self($msg, $code);
    }

    public static function fromUnregisteredModule(string $id)
    {
        $code = self::UNREGISTERED_MODULE;
        $msg = sprintf('Dropship module %s is not registered.', $id);
        return new self($msg, $code);
    }

    public static function fromDuplicateModule(string $id)
    {
        $code = self::DUPLICATE_MODULE_ID;
        $msg = sprintf('Duplicate dropship module %s.', $id);
        return new self($msg, $code);
    }

    public static function fromInvalidConfig(string $what, $item)
    {
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

    public static function fromClientException(string $supplier, Throwable $t)
    {
        $msg = 'API Client (library) error.';
        $code = self::MODULE_API_FAILURE;
        $e = new DropshipException($msg, $code);
        $e->setApiError(['code' => $t->getCode(), 'message' => $t->getMessage()]);
        return $e;
    }

    public static function fromXmlError(string $supplier, int $error)
    {
        $msg = 'Invalid XML date received.';
        $code = self::MODULE_API_XML_ERROR;
        $e = new DropshipException($msg, $code);
        $e->setXmlError(['code' => $error, 'message' => self::$xmlErrorMessages[$error]]);
        return $e;
    }

    public static function fromSupplierErrors(string $supplier, array $errors)
    {
        $code = self::MODULE_API_SUPPLIER_ERRORS;
        $msg = 'Supplier API error codes available.';
        $e = new DropshipException($msg, $code);
        $e->setSupplierErrors($errors);
        $e->setSupplier($supplier);
        return $e;
    }

    public static function fromHttpStatus(string $supplier, int $status)
    {
        $code = self::MODULE_API_FAILURE;
        $msg = sprintf('InnoCigs API failure: <br\>HTTP Status: %u', $status);
        $e = new DropshipException($msg, $code);
        $code = self::API_HTTP_STATUS_ERROR;
        $msg = sprintf(self::$apiErrorMessages[$code], $status);
        $e->setApiError(['code' => $code, 'message' => $msg]);
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
                'code'    => $error,
                'message' => self::$addressErrorMessages[$error],
            ];
        }
        $e->setAddressErrors($errors);
        $e->setSupplier($supplier);
        return $e;
    }

    public function setSupplierErrors(array $errors)
    {
        // a single error was returned
        if (isset($errors['ERROR']['CODE'])) {
            $error = $errors['ERROR'];
            $this->supplierErrors[] = [
                'code'    => $error['CODE'],
                'message' => $error['MESSAGE'],
            ];
            return;
        }
        // multiple errors were returned
        foreach ($errors['ERROR'] as $error) {
            $this->supplierErrors[] = [
                'code'    => $error['CODE'],
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

    public function setPositionErrors(array $positionErrors)
    {
        $this->positionErrors = $positionErrors;
    }

    public function getPositionErrors()
    {
        return $this->positionErrors;
    }

    public function setAddressErrors($errors)
    {
        $this->addressErrors = $errors;
    }

    public function getAddressErrors()
    {
        return $this->addressErrors;
    }

    public function setXmlError(array $error)
    {
        $this->xmlErrors = [$error];
    }

    public function getXmlErrors()
    {
        return $this->xmlErrors;
    }

    public function setApiError(array $error)
    {
        $this->apiErrors = [$error];
    }

    public function getApiErrors()
    {
        return $this->apiErrors;
    }

}