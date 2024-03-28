<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'rootLevel' => 1,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/icon_tx_odsosm_layer.png',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true
                    ]
                ],
            ]
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'overlay' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.overlay',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'javascript_include' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.javascript_include',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'checkbox' => '',
            ]
        ],
        'javascript_openlayers' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.javascript_openlayers',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'static_url' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.static_url',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'tile_url' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.tile_url',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'tile_https' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.tile_https',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ]
        ],
        'min_zoom' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.min_zoom',
            'config' => [
                'type' => 'input',
                'size' => '2',
                'eval' => 'num',
            ]
        ],
        'max_zoom' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.max_zoom',
            'config' => [
                'type' => 'input',
                'size' => '2',
                'eval' => 'num',
            ]
        ],
        'subdomains' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.subdomains',
            'config' => [
                'type' => 'input',
                'size' => '5',
            ]
        ],
        'attribution' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.attribution',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'homepage' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.homepage',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, title, overlay, javascript_include, javascript_openlayers, static_url, tile_url, tile_https, min_zoom, max_zoom, subdomains, attribution, homepage']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
