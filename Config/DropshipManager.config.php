<?php

use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Exception\DropshipException;

return [
    'notification_context' => [
        DropshipException::ORDER_POSITIONS_ERROR => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailTitle'         => 'Fehler in den Bestellpositionen',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> kann nicht versandt werden, da einzelne Bestellpositionen fehlerhaft sind.',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: Fehler in den Bestellpositionen',
            'message'           => 'Dropship-Auftrag nicht übertragen: Fehler in den Bestellpositionen. Siehe Log.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_POSITION_ERROR,
        ],
        DropshipException::ORDER_RECIPIENT_ADDRESS_ERROR => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: Fehler in der Lieferadresse',
            'mailTitle'         => 'Fehler in der Lieferadresse',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> kann nicht versandt werden, da die Lieferaddresse Fehler aufweist.',
            'message'           => 'Dropship-Auftrag nicht übertragen. Fehler in der Lieferadresse. Siehe Log.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_ADDRESS_ERROR,
        ],
        DropshipException::MODULE_API_XML_ERROR => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: Ungültige XML-Daten',
            'mailTitle'         => 'Ungültige XML-Daten',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> wurde '
                                    . 'übertragen. Die Antwort des {$supplier} Servers enthält fehlerhafte XML-Daten. '
                                    . ' <strong>Der Status des Dropship-Auftrags ist unbekannt.</strong> '
                                    . 'Bitte kontaktieren Sie {$supplier}.',
            'message'           => 'Dropship-Auftrag übertragen. Status unbekannt. Ungültige XML-Antwort erhalten. Kontaktieren Sie {$supplier}.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_XML_ERROR,
        ],
        DropshipException::MODULE_API_ERROR => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: {$supplier} Schnittstelle nicht erreichbar',
            'mailTitle'         => '{$supplier} Schnittstelle nicht erreichbar',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> konnte nicht '
                                   . 'übertragen werden, da der {$supplier} Server nicht erreichbar ist. Es ist keine Aktion '
                                   . 'erforderlich. Die Übertragung wird wiederholt. Kontaktieren Sie {$supplier}.',
            'message'           => 'Dropship-Auftrag nicht übertragen. {$supplier} Server nicht erreichbar. Automatischer Neuversuch.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_OPEN,
        ],
        DropshipException::MODULE_API_SUPPLIER_ERRORS => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber}: {$supplier} Fehlermeldungen',
            'mailTitle'         => '{$supplier} Fehlermeldungen',
            'mailBody'          => 'der Dropship-Auftrag zur Bestellung <strong>{$orderNumber}</strong> konnte nicht '
                                   . 'übertragen werden. Der {$supplier} Server meldet Fehler.',
            'message'           => 'Dropship-Auftrag konnte nicht übertragen werden. Großhändler meldet Fehler. Siehe Log.',
            'severity'          => DropshipLogger::ERR,
            'status'            => DropshipManager::ORDER_STATUS_SUPPLIER_ERROR,
        ],
        'UNKNOWN_ERROR'         => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Unbekannter Fehler',
            'mailTitle'         => 'Unbekannter Fehler',
            'mailBody'          => 'beim Versand der Bestellung <strong>{$orderNumber}</strong> ist ein bisher nicht '
                                    . 'behandelbarer Fehler ist aufgetreten. Der Status der Bestellung ist unklar. Bitte '
                                    . 'informieren Sie <strong>dringend</strong> die Entwickler des Dropship Moduls.',
            'message'           => 'Bisher nicht behandelter Fehler: Dropship-Status unklar. Informieren Sie den Entwickler.',
            'severity'          => DropshipLogger::CRIT,
            'status'            => DropshipManager::ORDER_STATUS_UNKNOWN_ERROR,
        ],
        'ORDER_SUCCESS' => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber} erfolgreich übertragen',
            'mailTitle'         => 'Bestellung erfolgreich übertragen',
            'mailBody'          => 'die Bestellung mit der Nummer <strong>{$orderNumber}</strong> wurde erfolgreich '
                                    . 'an {$supplier} übertragen. Warte auf Tracking-Daten. ',
            'message'           => 'Dropship-Auftrag wurde erfolgreich an {$supplier} übertragen.',
            'severity'          => DropshipLogger::NOTICE,
            'status'            => DropshipManager::ORDER_STATUS_SENT,
        ],
        'ORDER_CANCELLED' => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Bestellung {$orderNumber} durch {$supplier} storniert',
            'mailTitle'         => 'Bestellung durch {$supplier} storniert',
            'mailBody'          => 'die Bestellung mit der Nummer <strong>{$orderNumber}</strong> wurde von '
                                    . '{$supplier} storniert. Bitte informieren Sie den Kunden.',
            'message'           => 'Dropship-Auftrag wurde von {$supplier} storniert.',
            'severity'          => DropshipLogger::WARN,
            'status'            => DropshipManager::ORDER_STATUS_CANCELLED,
        ],
        'ORDER_TRACKING_DATA' => [
            'mailTemplate'      => 'sMxcDsiDropshipStatus',
            'mailSubject'       => 'Dropship-Status - Für Bestellung {$orderNumber} liegen Trackinginformationen vor',
            'mailTitle'         => 'Trackinginformationen verfügbar',
            'mailBody'          => 'für die Bestellung mit der Nummer <strong>{$orderNumber}</strong> liegen '
                                    . 'Trackinginformationen von {$supplier} vor.',
            'message'           => 'Es liegen Trackinginformationen vor.',
            'severity'          => DropshipLogger::NOTICE,
            'status'            => DropshipManager::ORDER_STATUS_TRACKING_DATA,
        ],
    ],
];
