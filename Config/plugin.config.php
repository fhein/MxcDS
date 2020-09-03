<?php

namespace MxcDropship;

use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Models\DropshipLogEntry;
use MxcDropship\Models\DropshipModule;

return [
    'doctrine' => [
        'models' => [
            DropshipModule::class,
            DropshipLogEntry::class,
        ]
    ],
    'services' => [
        'magicals' => [
            DropshipManager::class,
            DropshipLogger::class,
        ]
    ]
];
