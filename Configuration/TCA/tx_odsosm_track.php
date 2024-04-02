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
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'default' => '#37b7ff',
                'eval' => 'nospace,trim',
                'renderType' => 'colorpicker',
            ]
        ],
        'width' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.width',
            'config' => [
                'type' => 'input',
                'size' => 3,
                'max' => 3,
                'default' => 5,
                'range' => [
                    'lower' => 1,
                    'upper' => 255
                ],
                'eval' => 'int',
            ]
        ],
        'file' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.file',
            'config' => ExtensionManagementUtility::getFileFieldTCAConfig(
                'file',
                [
                    'maxitems' => 1,
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db:tx_odsosm_track.file.add'
                    ],
                    'foreign_match_fields' => [
                        'fieldname' => 'file',
                        'tablenames' => 'tx_odsosm_track',
                    ],
                    'default' => 0,
                ],
                'gpx,kml'
            )
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
