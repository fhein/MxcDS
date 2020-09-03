<?php

namespace MxcDropship\Exception;

use MxcCommons\Plugin\Plugin;
use RuntimeException;

class DropshipManagerException extends RuntimeException
{
    const INVALID_MODULE_ID = 1;
    const DUPLICATE_MODULE_ID = 2;
    const INVALID_CONFIG = 3;
    const INVALID_MODULE = 4;
    const MISSING_MODULE_SERVICE = 5;

    const MODULE_CLASS_EXIST        = 1;
    const MODULE_CLASS_IDENTITY     = 2;
    const MODULE_CLASS_INSTALLED    = 3;
    const MODULE_CLASS_SERVICES     = 4;

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
}