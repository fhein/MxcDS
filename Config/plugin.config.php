<?php

namespace MxcDropship;

use MxcDropship\Dropship\DropshipManager;
use MxcDropship\Dropship\DropshipLogger;
use MxcDropship\Models\DropshipLogEntry;
use MxcDropship\Models\DropshipModule;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;

return [
    'doctrine' => [
        'models'     => [
            DropshipModule::class,
            DropshipLogEntry::class,
        ],
        'attributes' => [
            's_order_attributes'         => [
                'mxcbc_dsi_active'     => ['type' => TypeMapping::TYPE_BOOLEAN],
                'mxcbc_dsi_cronstatus' => ['type' => TypeMapping::TYPE_INTEGER],
                'mxcbc_dsi_status'     => ['type' => TypeMapping::TYPE_INTEGER],
            ],
            's_order_details_attributes' => [
                'mxcbc_dsi_supplier_id'   => ['type' => TypeMapping::TYPE_INTEGER],
                'mxcbc_dsi_supplier'      => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_dropship_id'   => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_order_id'      => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_infos'         => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_instock'       => ['type' => TypeMapping::TYPE_INTEGER],
                'mxcbc_dsi_purchaseprice' => ['type' => TypeMapping::TYPE_FLOAT],
                'mxcbc_dsi_date'          => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_status'        => ['type' => TypeMapping::TYPE_INTEGER],
                'mxcbc_dsi_message'       => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_carrier'       => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_dsi_tracking_id'   => ['type' => TypeMapping::TYPE_STRING],
            ],
            's_articles_attributes'      => [
                'mxcbc_product_type' => ['type' => TypeMapping::TYPE_STRING],
                'mxcbc_product_meta' => ['type' => TypeMapping::TYPE_INTEGER],

                // Aus welcher Quelle wird bei Bestellung geliefert?
                //      - aus eigenem Lager                                     -> 1
                //      - Dropship und eigenes Lager, eigenes Lager bevorzugen  -> 2
                //      - Dropship und eigenes Lager, Dropship bevorzugen       -> 3
                //      - nur Dropship                                          -> 4
                'mxcbc_dsi_mode'     => [
                    'type'         => TypeMapping::TYPE_INTEGER,
                    'defaultValue' => DropshipManager::MODE_OWNSTOCK_ONLY,
                ],
                'mxcbc_dsi_supplier_id' => ['type' => TypeMapping::TYPE_INTEGER],
            ],
        ],
    ],
    'services' => [
        'magicals' => [
            DropshipManager::class,
            DropshipLogger::class,
        ],
    ],
];
