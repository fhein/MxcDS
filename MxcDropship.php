<?php

namespace MxcDropship;

use MxcCommons\Plugin\Plugin;
use MxcCommons\Plugin\Service\ServicesFactory;
use MxcDropship\Dropship\DropshipManager;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class MxcDropship extends Plugin {

    protected $activateClearCache = ActivateContext::CACHE_LIST_ALL;
    protected $uninstallClearCache = UninstallContext::CACHE_LIST_ALL;

    public const PLUGIN_DIR = __DIR__;

    private static $services;

    protected static $panels = [
        DropshipManager::ORDER_STATUS_OPEN => [
            'background' => 'LightSteelBlue',
            'text'       => 'Black',
            'message'    => 'Neue Bestellung für Übertragung an den Großhändler vorgemerkt.'
        ],
        DropshipManager::ORDER_STATUS_POSITION_ERROR => [
            'background' => 'OrangeRed',
            'text'       => 'White',
            'message'    => 'Dropship-Auftrag fehlgeschlagen. Fehler in den Bestellpositionen.'
        ],
        DropshipManager::ORDER_STATUS_ADDRESS_ERROR => [
            'background' => 'OrangeRed',
            'text'       => 'White',
            'message'    => 'Dropship-Auftrag fehlgeschlagen. Fehler in der Lieferadresse.'
        ],
        DropshipManager::ORDER_STATUS_SUPPLIER_ERROR => [
            'background' => 'OrangeRed',
            'text'       => 'White',
            'message'    => 'Dropship-Auftrag fehlgeschlagen. Großhändler meldet Fehler.'
        ],
        DropshipManager::ORDER_STATUS_XML_ERROR => [
            'background' => 'OrangeRed',
            'text'       => 'White',
            'message'    => 'Ungültige XML-Daten empfangen. Bestellstatus unklar.'
        ],
        DropshipManager::ORDER_STATUS_API_ERROR => [
            'background' => 'OrangeRed',
            'text'       => 'White',
            'message'    => 'Dropship-Server nicht erreichbar. Neuer Versuch geplant.'
        ],
        DropshipManager::ORDER_STATUS_SENT => [
            'background' => 'CornflowerBlue',
            'text'       => 'White',
            'message'    => 'Bestellung erfolgreich versandt. Warte auf Tracking-Daten.'
        ],
        DropshipManager::ORDER_STATUS_TRACKING_DATA => [
            'background' => 'BurlyWood',
            'text'       => 'Black',
            'message'    => 'Tracking-Daten empfangen.'
        ],
        DropshipManager::ORDER_STATUS_CLOSED => [
            'background' => 'LimeGreen',
            'text'       => 'White',
            'message'    => 'Dropship-Auftrag erfolgreich abgeschlossen.'
        ],
        'NO_DROPSHIP_MODULE' => [
            'background' => 'AliceBlue',
            'text'       => 'Black',
            'message'    => 'Es sind keine Dropship Module installiert.'
        ],
        'OWNSTOCK_ONLY' => [
            'background' => 'MidnightBlue',
            'text'       => 'White',
            'message'    => 'Die Bestellung enthält keine Dropship-Artikel.'
        ]
    ];

    public static function getPanelConfig()
    {
        return self::$panels;
    }

    public static function getServices()
    {
        if (self::$services !== null) return self::$services;
        $factory = new ServicesFactory();
        self::$services = $factory->getServices(self::PLUGIN_DIR);
        return self::$services;
    }
}

