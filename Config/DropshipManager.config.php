<?php

use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Exception\DropshipException;

return [
    'notification_context' => [
        DropshipException::ORDER_POSITIONS_ERROR => [
            // can only occur on orderSend
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'Dropship-Status - {$supplier} Bestellung {$orderNumber}: Fehler in den Bestellpositionen',
                'mailTitle'         => 'Fehler in den Bestellpositionen',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> kann nicht versandt werden, da einzelne Bestellpositionen fehlerhaft sind.',
                'message'           => 'Dropship-Auftrag nicht übertragen: Fehler in den Bestellpositionen. Siehe Log.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::ORDER_STATUS_POSITION_ERROR,
            ]
        ],
        DropshipException::ORDER_RECIPIENT_ADDRESS_ERROR => [
            // can only occur on orderSend
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'Dropship-Status - {$supplier} Bestellung {$orderNumber}: Fehler in der Lieferadresse',
                'mailTitle'         => 'Fehler in der Lieferadresse',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> kann nicht versandt werden, da die Lieferaddresse Fehler aufweist.',
                'message'           => 'Dropship-Auftrag nicht übertragen. Fehler in der Lieferadresse. Siehe Log.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::ORDER_STATUS_ADDRESS_ERROR,
            ]
        ],
        DropshipException::MODULE_API_XML_ERROR => [
            'sendOrder' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'Dropship-Status - {$supplier} Bestellung {$orderNumber}: Ungültige XML-Daten',
                'mailTitle'         => 'Ungültige XML-Daten',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</st rong> wurde '
                                        . 'übertragen. Die Antwort des {$supplier} Servers enthält fehlerhafte XML-Daten. '
                                        . ' <strong>Der Status des Dropship-Auftrags ist unbekannt.</strong> '
                                        . 'Bitte kontaktieren Sie {$supplier}.',
                'message'           => 'Dropship-Auftrag übertragen. Status unbekannt. Ungültige XML-Antwort erhalten. Kontaktieren Sie {$supplier}.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::ORDER_STATUS_XML_ERROR,
            ],
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Tracking Daten von {$supplier} konnten nicht abgerufen werden',
                'mailTitle'         => 'Ungültige XML-Daten',
                'mailBody'          => 'der Abruf von Trackinginformationen bei {$supplier} ist fehlgeschlagen. '
                                        . 'Die Antwort des Servers enthält fehlerhafte XML-Daten. '
                                        . 'Bitte kontaktieren Sie {$supplier}.',
                'message'           => 'Abruf von Trackingdaten fehlgeschlagen. {$supplier} Server lieferte ungültige XML-Daten.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::ORDER_STATUS_SENT,  // try again
            ],
            'updateStock' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Aktualisierung des Lagerbestands von {$supplier} fehlgeschlagen',
                'mailTitle'         => 'Ungültige XML-Daten',
                'mailBody'          => 'die Aktualisierung des Lagerbestands von {$supplier} ist fehlgeschlagen. '
                                       . 'Die Antwort des Servers enthält fehlerhafte XML-Daten. '
                                       . 'Bitte kontaktieren Sie {$supplier}.',
                'message'           => 'Aktualisierung des Lagerbestands fehlgeschlagen: {$supplier} Server liefert ungültige XML-Daten. ',
                'severity'          => DropshipLogger::ERR,
            ],
            'updatePrices' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Preisaktualisierung von {$supplier} fehlgeschlagen',
                'mailTitle'         => 'Ungültige XML-Daten',
                'mailBody'          => 'die Aktualisierung der Preise von {$supplier} ist fehlgeschlagen. '
                                        . 'Die Antwort des Servers enthält fehlerhafte XML-Daten. '
                                        . 'Bitte kontaktieren Sie {$supplier}.',
                'message'           => 'Preisaktualisierung fehlgeschlagen: {$supplier} Server liefert ungültige XML-Daten. ',
                'severity'          => DropshipLogger::ERR,
            ]

        ],
        DropshipException::MODULE_API_ERROR => [
            'orderSend' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: {$supplier} Schnittstelle nicht erreichbar',
                'mailTitle'         => '{$supplier} Server nicht erreichbar',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> konnte nicht '
                                       . 'übertragen werden, da der {$supplier} Server nicht erreichbar ist. Es ist keine Aktion '
                                       . 'erforderlich. Die Übertragung wird wiederholt. Kontaktieren Sie {$supplier}.',
                'message'           => 'Dropship-Auftrag nicht übertragen. {$supplier} Server nicht erreichbar. Automatischer Neuversuch.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::ORDER_STATUS_OPEN,
            ],
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Tracking Daten von {$supplier} konnten nicht abgerufen werden',
                'mailTitle'         => '{$supplier} Server nicht erreichbar',
                'mailBody'          => 'der Abruf von Trackinginformationen bei {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server ist nicht erreichbar.',
                'message'           => 'Abruf von Trackingdaten fehlgeschlagen. {$supplier} Server ist nicht erreichbar.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::ORDER_STATUS_SENT,
            ],
            'updateStock' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Aktualisierung des Lagerbestands von {$supplier} fehlgeschlagen',
                'mailTitle'         => '{$supplier} Server ist nicht erreichbar',
                'mailBody'          => 'die Aktualisierung des Lagerbestands von {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server ist nicht erreichbar. ',
                'message'           => 'Aktualisierung des Lagerbestands fehlgeschlagen: {$supplier} Server nicht erreichbar. ',
                'severity'          => DropshipLogger::ERR,
            ],
            'updatePrices' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Preisaktualisierung von {$supplier} fehlgeschlagen',
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
                'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: {$supplier} Fehlermeldungen',
                'mailTitle'         => '{$supplier} Fehlermeldungen',
                'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> konnte nicht '
                                       . 'übertragen werden. Der {$supplier} Server meldet Fehler.',
                'message'           => 'Dropship-Auftrag konnte nicht übertragen werden. Großhändler meldet Fehler. Siehe Log.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::ORDER_STATUS_SUPPLIER_ERROR,
            ],
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Tracking Daten von {$supplier} konnten nicht abgerufen werden',
                'mailTitle'         => '{$supplier} Fehlermeldungen',
                'mailBody'          => 'der Abruf von Trackinginformationen bei {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server liefert Fehlermeldungen.',
                'message'           => 'Abruf von Trackingdaten fehlgeschlagen. {$supplier} meldet Fehler.',
                'severity'          => DropshipLogger::ERR,
                'status'            => DropshipManager::ORDER_STATUS_SENT,
            ],
            'updateStock' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Aktualisierung des Lagerbestands von {$supplier} fehlgeschlagen',
                'mailTitle'         => '{$supplier} Fehlermeldungen',
                'mailBody'          => 'die Aktualisierung des Lagerbestands von {$supplier} ist fehlgeschlagen. '
                                        . 'Der Server liefert Fehlermeldungen. ',
                'message'           => 'Aktualisierung des Lagerbestands fehlgeschlagen: {$supplier} meldet Fehler. ',
                'severity'          => DropshipLogger::ERR,
            ],
            'updatePrices' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Preisaktualisierung von {$supplier} fehlgeschlagen',
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
                'mailSubject'       => 'Dropship-Status -  Fehler beim Versand der Bestellung {$orderNumber} an {$supplier}',
                'mailTitle'         => 'Unbekannter Fehler',
                'mailBody'          => 'beim Versand der Bestellung <strong>{$orderNumber}</strong> an '
                                        . '<strong>{$supplier}</strong> ist ein bisher nicht '
                                        . 'behandelbarer Fehler aufgetreten. Der Status der Bestellung ist unklar. Bitte '
                                        . 'informieren Sie <strong>dringend</strong> die Entwickler des Dropship Moduls.',
                'message'           => 'Bisher nicht behandelter Fehler: Dropship-Status unklar. Informieren Sie den Entwickler.',
                'severity'          => DropshipLogger::CRIT,
                'status'            => DropshipManager::ORDER_STATUS_UNKNOWN_ERROR,
            ],
            'updateTrackingData'    => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Abruf von Tracking Daten von {$supplier} fehlgeschlagen',
                'mailTitle'         => 'Unbekannter Fehler',
                'mailBody'          => 'beim Abruf von Trackingdaten für Bestellung <strong>{$orderNumber}</strong> '
                                        . 'ist ein bisher nicht behandelbarer Fehler ist aufgetreten. Bitte informieren '
                                        . 'Sie <strong>dringend</strong> die Entwickler des Dropship Moduls.',
                'message'           => 'Bisher nicht behandelter Fehler: Abruf von Trackingdaten fehlgeschlagen. '
                                        . 'Bitte informieren Sie den Entwickler.',
                'severity'          => DropshipLogger::CRIT,
                'status'            => DropshipManager::ORDER_STATUS_SENT,
            ],
            'updateStock' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - Aktualisierung des Lagerbestands von {$supplier} fehlgeschlagen',
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
                'mailSubject'       => 'maxence Dropship - {$supplier} Preisaktualisierung fehlgeschlagen',
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
                'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber} erfolgreich an {$supplier} übertragen',
                'mailTitle'         => 'Bestellung erfolgreich übertragen',
                'mailBody'          => 'die Bestellung mit der Nummer <strong>{$orderNumber}</strong> wurde erfolgreich '
                                        . 'an {$supplier} übertragen. Warte auf Tracking-Daten. ',
                'message'           => 'Dropship-Auftrag wurde erfolgreich an {$supplier} übertragen.',
                'severity'          => DropshipLogger::NOTICE,
                'status'            => DropshipManager::ORDER_STATUS_SENT,
            ],
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'maxence Dropship - {$supplier} Es liegen Tracking Daten zur Bestellung {$orderNumber} vor',
                'mailTitle'         => 'Tracking Informationen verfügbar',
                'mailBody'          => 'zur Bestellung mit der Nummer <strong>{$orderNumber}</strong> liegen Tracking '
                                        . 'Informationen von {$supplier} vor.',
                'message'           => 'Tracking Informationen erhalten.',
                'severity'          => DropshipLogger::NOTICE,
                'status'            => DropshipManager::ORDER_STATUS_TRACKING_DATA,
            ]
        ],
        'ORDER_CANCELLED' => [
            // can only occur on updateTrackingData
            'updateTrackingData' => [
                'mailTemplate'      => 'sMxcDsiDropshipStatus',
                'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber} durch {$supplier} storniert',
                'mailTitle'         => 'Bestellung durch {$supplier} storniert',
                'mailBody'          => 'die Bestellung mit der Nummer <strong>{$orderNumber}</strong> wurde von '
                                        . '{$supplier} storniert. Bitte informieren Sie den Kunden.',
                'message'           => 'Dropship-Auftrag wurde von {$supplier} storniert.',
                'severity'          => DropshipLogger::WARN,
                'status'            => DropshipManager::ORDER_STATUS_CANCELLED,
            ]
        ],
    ],
];
