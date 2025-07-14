<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/icon_tx_odsosm_track.png',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
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
                        'label' => '',
                        'invertStateDisplay' => true
                    ]
                ],
            ]
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ]
        ],
        'color' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.color',
            'config' => [
                'type' => 'color',
                'size' => 10,
                'default' => '#37b7ff',
            ]
        ],
        'width' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.width',
            'config' => [
                'type' => 'number',
                'size' => 3,
                'max' => 3,
                'default' => 5,
                'range' => [
                    'lower' => 1,
                    'upper' => 255
                ],
            ]
        ],
        'file' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.file',
            'config' => [
                'type' => 'file',
                'allowed' => 'gpx,kml',
                'maxitems' => 1,
                'default' => 0,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db:tx_odsosm_track.file.add'
                ],
            ]
        ],
        'min_lon' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.min_lon',
            'config' => [
                'type' => 'none',
                'size' => 8,
            ]
        ],
        'min_lat' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.min_lat',
            'config' => [
                'type' => 'none',
                'size' => 8,
            ]
        ],
        'max_lon' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.max_lon',
            'config' => [
                'type' => 'none',
                'size' => 8,
            ]
        ],
        'max_lat' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.max_lat',
            'config' => [
                'type' => 'none',
                'size' => 8,
            ]
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'hidden, title, color, width, file,
                            --palette--;;lonlatinfo'
        ]
    ],
    'palettes' => [
        'lonlatinfo' => [
            'showitem' => 'min_lon, min_lat, max_lon, max_lat'
        ]
    ]
];
