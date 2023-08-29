<?php

use Bobosch\OdsOsm\Evaluation\LonLat;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache',
        'label' => 'zip',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY zip',
        'delete' => 'deleted',
        'rootLevel' => 1,
        'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/icon_tx_odsosm_geocache.png',
    ],
    'columns' => [
        'cache_hit' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.cache_hit',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'eval' => '',
            ]
        ],
        'service_hit' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.service_hit',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'eval' => '',
            ]
        ],
        'search_city' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.city',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ]
        ],
        'country' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.country',
            'config' => [
                'type' => 'input',
                'size' => 2,
                'max' => 2,
            ]
        ],
        'state' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.state',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ]
        ],
        'city' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.city',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ]
        ],
        'zip' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.zip',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
            ]
        ],
        'street' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.street',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ]
        ],
        'housenumber' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.housenumber',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
            ]
        ],
        'lon' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.lon',
            'config' => [
                'type' => 'input',
                'size' => 11,
                'max' => 11,
                'eval' => LonLat::class,
            ]
        ],
        'lat' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_geocache.lat',
            'config' => [
                'type' => 'input',
                'size' => 11,
                'max' => 10,
                'eval' => LonLat::class,
            ]
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'cache_hit,service_hit,search_city,country,state,city,zip,street,housenumber,lon,
            --palette--;;latinfo'
        ]
    ],
    'palettes' => [
        'latinfo' => [
            'showitem' => 'lat'
        ]
    ]
];
