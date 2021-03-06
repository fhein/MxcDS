<?php

use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Exception\DropshipException;

return [

    'orderCost' => [
        // Paypal, Paypal+
        'SwagPaymentPayPalUnified' => [
            'provider' => 'Paypal',
            'base' => 0.35,
            'percentage' => 2.49
        ],
        // Klarna Rechnung
        'bestit_klarna_payments_pay_later' => [
            'provider' => 'Klarna',
            'base' => 0.35,
            'percentage' => 2.99
        ],
        // Klarna Ratenkauf
        'bestit_klarna_payments_slice_it' => [
            'provider' => 'Klarna',
            'base' => 0.00,
            'percentage' => 2.99
        ],
        // Klarna Lastschrift
        'bestit_klarna_payments_direct_debit' => [
            'provider' => 'Klarna',
            'base' => 0.20,
            'percentage' => 1.35
        ],
        // Klarna Sofortüberweisung
        'bestit_klarna_payments_direct_bank_transfer' => [
            'provider' => 'Klarna',
            'base' => 0.20,
            'percentage' => 1.35
        ],
        // Vorkasse
        'prepayment' => [
            'provider' => 'Vorkasse',
            'base' => 0.0,
            'percentage' => 0.0,
            'DHL' => 5.86
        ]
    ],

    'notification_address' => [
        'from' => [
            'name' => 'vapee.de Dropship',
            'email' => 'info@vapee.de',
        ],
        'to'   => [
//            'support@vapee.de',
            'info@vapee.de'
        ]
    ],
    'notification_context' => [
        DropshipException::ORDER_POSITIONS_ERROR => [
            // can only occur on orderSend
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Bestellung {$orderNumber}: Fehler in den Bestellpositionen',
                'mailTitle'         => 'Fehler in den Bestellpositionen',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> kann nicht versandt werden, da einzelne Bestellpositionen fehlerhaft sind.',
                'message'           => 'Dropship-Auftrag nicht übertragen: Fehler in den Bestellpositionen. Siehe Log.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::DROPSHIP_STATUS_POSITION_ERROR,
                'recoverable'       => false,
            ]
        ],
        DropshipException::ORDER_RECIPIENT_ADDRESS_ERROR => [
            // can only occur on orderSend
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Bestellung {$orderNumber}: Fehler in der Lieferadresse',
                'mailTitle'         => 'Fehler in der Lieferadresse',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> kann nicht versandt werden, da die Lieferaddresse Fehler aufweist.',
                'message'           => 'Dropship-Auftrag nicht übertragen. Fehler in der Lieferadresse. Siehe Log.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::DROPSHIP_STATUS_ADDRESS_ERROR,
                'recoverable'       => false,
            ]
        ],
        DropshipException::MODULE_API_XML_ERROR => [
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Bestellung {$orderNumber}: Ungültige XML-Daten',
                'mailTitle'         => 'Ungültige XML-Daten',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</st rong> wurde '
                                        . 'übertragen. Die Antwort des {$supplier} Servers enthält fehlerhafte XML-Daten. '
                                        . ' <strong>Der Status des Dropship-Auftrags ist unbekannt.</strong> '
                                        . 'Bitte kontaktieren Sie {$supplier}.',
                'message'           => 'Dropship-Auftrag übertragen. Status unbekannt. Ungültige XML-Antwort erhalten. Kontaktieren Sie {$supplier}.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::DROPSHIP_STATUS_XML_ERROR,
                'recoverable'       => false,
            ],
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Tracking Daten konnten nicht abgerufen werden',
                'mailTitle'         => 'Ungültige XML-Daten',
                'mailBody'          => 'der Abruf von Trackinginformationen bei {$supplier} ist fehlgeschlagen. '
                                        . 'Die Antwort des Servers enthält fehlerhafte XML-Daten. '
                                        . 'Bitte kontaktieren Sie {$supplier}.',
                'message'           => 'Abruf von Trackingdaten fehlgeschlagen. {$supplier} Server lieferte ungültige XML-Daten.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::DROPSHIP_STATUS_XML_ERROR,
                'recoverable'       => true,
            ],
            'updateStock' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Aktualisierung des Lagerbestands fehlgeschlagen',
                'mailTitle'         => 'Ungültige XML-Daten',
                'mailBody'          => 'die Aktualisierung des Lagerbestands von {$supplier} ist fehlgeschlagen. '
                                       . 'Die Antwort des Servers enthält fehlerhafte XML-Daten. '
                                       . 'Bitte kontaktieren Sie {$supplier}.',
                'message'           => 'Aktualisierung des Lagerbestands fehlgeschlagen: {$supplier} Server liefert ungültige XML-Daten. ',
                'severity'          => DropshipLogger::ERR,
            ],
            'updatePrices' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Preisaktualisierung fehlgeschlagen',
                'mailTitle'         => 'Ungültige XML-Daten',
                'mailBody'          => 'die Aktualisierung der Preise von {$supplier} ist fehlgeschlagen. '
                                        . 'Die Antwort des Servers enthält fehlerhafte XML-Daten. '
                                        . 'Bitte kontaktieren Sie {$supplier}.',
                'message'           => 'Preisaktualisierung fehlgeschlagen: {$supplier} Server liefert ungültige XML-Daten. ',
                'severity'          => DropshipLogger::ERR,
            ]

        ],
        DropshipException::MODULE_API_ERROR => [
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} -  Bestellung {$orderNumber}: Server nicht erreichbar',
                'mailTitle'         => '{$supplier} Server nicht erreichbar',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> konnte nicht '
                                       . 'übertragen werden, da der {$supplier} Server nicht erreichbar ist. Es ist keine Aktion '
                                       . 'erforderlich. Die Übertragung wird wiederholt. Kontaktieren Sie {$supplier}.',
                'message'           => 'Dropship-Auftrag nicht übertragen. {$supplier} Server nicht erreichbar. Automatischer Neuversuch.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::DROPSHIP_STATUS_API_ERROR,
                'recoverable'       => true,
            ],
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Tracking Daten konnten nicht abgerufen werden',
                'mailTitle'         => '{$supplier} Server nicht erreichbar',
                'mailBody'          => 'der Abruf von Trackinginformationen bei {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server ist nicht erreichbar.',
                'message'           => 'Abruf von Trackingdaten fehlgeschlagen. {$supplier} Server ist nicht erreichbar.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::DROPSHIP_STATUS_API_ERROR,
                'recoverable'       => true,
            ],
            'updateStock' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Aktualisierung des Lagerbestands fehlgeschlagen',
                'mailTitle'         => '{$supplier} Server ist nicht erreichbar',
                'mailBody'          => 'die Aktualisierung des Lagerbestands von {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server ist nicht erreichbar. ',
                'message'           => 'Aktualisierung des Lagerbestands fehlgeschlagen: {$supplier} Server nicht erreichbar. ',
                'severity'          => DropshipLogger::ERR,
            ],
            'updatePrices' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Preisaktualisierung fehlgeschlagen',
                'mailTitle'         => '{$supplier} Server ist nicht erreichbar',
                'mailBody'          => 'die Aktualisierung der Preise von {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server ist nicht erreichbar. ',
                'message'           => 'Preisaktualisierung fehlgeschlagen: {$supplier} Server nicht erreichbar. ',
                'severity'          => DropshipLogger::ERR,
            ]
        ],
        DropshipException::MODULE_API_SUPPLIER_ERRORS => [
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Bestellung {$orderNumber}: API meldet Fehler',
                'mailTitle'         => '{$supplier} Fehlermeldungen',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> konnte nicht '
                                       . 'übertragen werden. Der {$supplier} Server meldet Fehler.',
                'message'           => 'Dropship-Auftrag konnte nicht übertragen werden. Großhändler meldet Fehler. Siehe Log.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::DROPSHIP_STATUS_SUPPLIER_ERROR,
                'recoverable'       => false,
            ],
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Tracking Daten konnten nicht abgerufen werden',
                'mailTitle'         => '{$supplier} Fehlermeldungen',
                'mailBody'          => 'der Abruf von Trackinginformationen bei {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server liefert Fehlermeldungen.',
                'message'           => 'Abruf von Trackingdaten fehlgeschlagen. {$supplier} meldet Fehler.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::DROPSHIP_STATUS_SUPPLIER_ERROR,
                'recoverable'       => true,
            ],
            'updateStock' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Aktualisierung des Lagerbestands fehlgeschlagen',
                'mailTitle'         => '{$supplier} Fehlermeldungen',
                'mailBody'          => 'die Aktualisierung des Lagerbestands von {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server liefert Fehlermeldungen. ',
                'message'           => 'Aktualisierung des Lagerbestands fehlgeschlagen: {$supplier} meldet Fehler. ',
                'severity'          => DropshipLogger::ERR,
            ],
            'updatePrices' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Preisaktualisierung fehlgeschlagen',
                'mailTitle'         => '{$supplier} Fehlermeldungen',
                'mailBody'          => 'die Aktualisierung der Preise von {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server liefert Fehlermeldungen. ',
                'message'           => 'Preisaktualisierung fehlgeschlagen: {$supplier} meldet Fehler. ',
                'severity'          => DropshipLogger::ERR,
            ]
        ],
        'UNKNOWN_ERROR'         => [
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Unbehandelter Fehler beim Versand der Bestellung {$orderNumber}',
                'mailTitle'         => 'Unbekannter Fehler',
                'mailBody'          => 'beim Versand der Bestellung <strong>{$orderNumber}</strong> an '
                                        . '<strong>{$supplier}</strong> ist ein bisher nicht '
                                        . 'behandelbarer Fehler aufgetreten. Der Status der Bestellung ist unklar. Bitte '
                                        . 'informieren Sie <strong>dringend</strong> die Entwickler des Dropship Moduls.',
                'message'           => 'Bisher nicht behandelter Fehler: Dropship-Status unklar. Informieren Sie den Entwickler.',
                'severity'          => DropshipLogger::CRIT,
                'status'            => DropshipManager::DROPSHIP_STATUS_UNKNOWN_ERROR,
                'recoverable'       => false,
            ],
            'updateTrackingData'    => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Abruf von Tracking Daten fehlgeschlagen',
                'mailTitle'         => 'Unbekannter Fehler',
                'mailBody'          => 'beim Abruf von Trackingdaten für Bestellung <strong>{$orderNumber}</strong> '
                                        . 'ist ein bisher nicht behandelbarer Fehler ist aufgetreten. Bitte informieren '
                                        . 'Sie <strong>dringend</strong> die Entwickler des Dropship Moduls.',
                'message'           => 'Bisher nicht behandelter Fehler: Abruf von Trackingdaten fehlgeschlagen. '
                                        . 'Bitte informieren Sie den Entwickler.',
                'severity'          => DropshipLogger::CRIT,
                'status'            => DropshipManager::DROPSHIP_STATUS_UNKNOWN_ERROR,
                'recoverable'       => true,
            ],
            'updateStock' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Aktualisierung des Lagerbestands fehlgeschlagen',
                'mailTitle'         => 'Unbekannter Fehler',
                'mailBody'          => 'bei der Aktualisierung des Lagerbestands von {$supplier} ist ein bisher nicht behandelbarer '
                                        . 'Fehler aufgetreten. Bitte informieren Sie <strong>dringend</strong> '
                                        .'die Entwickler des Dropship Moduls.',
                'message'           => 'Bisher nicht behandelter Fehler: Aktualisierung des Lagerbestands fehlgeschlagen. '
                                        . 'Bitte informieren Sie den Entwickler.',
                'severity'          => DropshipLogger::CRIT,
            ],
            'updatePrices' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Preisaktualisierung fehlgeschlagen',
                'mailTitle'         => 'Unbekannter Fehler',
                'mailBody'          => 'bei der Aktualisierung der Preise von {$supplier} ist ein bisher nicht behandelbarer '
                                        . 'Fehler aufgetreten. Bitte informieren Sie <strong>dringend</strong> '
                                        .'die Entwickler des Dropship Moduls.',
                'message'           => 'Preisaktualisierung fehlgeschlagen: Bisher nicht behandelter Fehler aufgetreten. '
                                        . 'Bitte informieren Sie den Entwickler.',
                'severity'          => DropshipLogger::CRIT,
            ]
        ],
        'STATUS_SUCCESS' => [
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Bestellung {$orderNumber} erfolgreich übertragen',
                'mailTitle'         => 'Bestellung erfolgreich übertragen',
                'mailBody'          => 'die Bestellung mit der Nummer <strong>{$orderNumber}</strong> wurde erfolgreich '
                                        . 'an {$supplier} übertragen. Warte auf Tracking-Daten. ',
                'message'           => 'Dropship-Auftrag wurde erfolgreich an {$supplier} übertragen.',
                'severity'          => DropshipLogger::NOTICE,
                'status'            => DropshipManager::DROPSHIP_STATUS_SENT,
                'recoverable'       => false,
            ],
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => '{$supplier} - Es liegen Tracking Daten zur Bestellung {$orderNumber} vor',
                'mailTitle'         => 'Tracking Informationen verfügbar',
                'mailBody'          => 'zur Bestellung mit der Nummer <strong>{$orderNumber}</strong> liegen Tracking '
                                        . 'Informationen von {$supplier} vor.',
                'message'           => 'Tracking Informationen erhalten.',
                'severity'          => DropshipLogger::NOTICE,
                'status'            => DropshipManager::DROPSHIP_STATUS_CLOSED,
                'recoverable'       => false,
            ]
        ],
        'ORDER_CANCELLED' => [
            // can only occur on updateTrackingData
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'Bestellung {$orderNumber} von {$supplier} storniert',
                'mailTitle'         => 'Bestellung durch {$supplier} storniert',
                'mailBody'          => 'die Bestellung mit der Nummer <strong>{$orderNumber}</strong> wurde von '
                                        . '{$supplier} storniert. Bitte informieren Sie den Kunden.',
                'message'           => 'Dropship-Auftrag wurde von {$supplier} storniert.',
                'severity'          => DropshipLogger::WARN,
                'status'            => DropshipManager::DROPSHIP_STATUS_CANCELLED,
                'recoverable'       => false,
            ]
        ],
    ],
];
