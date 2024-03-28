<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_vector',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/icon_tx_odsosm_vector.png',
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
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_vector.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ]
        ],
        'data' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_vector.data',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 60,
                'max' => 20000,
                'fieldControl' => [
                    'vectordrawWizard' => [
                        'renderType' => 'vectordrawWizard'
                    ]
                ]
            ]
        ],
        'color' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_track.color',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'default' => '#3388ff',
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
                'default' => 3,
                'range' => [
                    'lower' => 0,
                    'upper' => 255
                ],
                'eval' => 'int',
            ]
        ],
        'file' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_vector.file',
            'config' => ExtensionManagementUtility::getFileFieldTCAConfig(
                'file',
                [
                    'maxitems' => 1,
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db:tx_odsosm_vector.file.add'
                    ],
                    'foreign_match_fields' => [
                        'fieldname' => 'file',
                        'tablenames' => 'tx_odsosm_vector',
                    ],
                    'default' => 0,
                ],
                'geojson,json'
            )
        ],
        'properties' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_vector.properties',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 60,
                'max' => 2000,
            ]
        ],
        'properties_from_file' => [
            'exclude' => true,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_vector.properties_from_file',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
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
            'showitem' => 'hidden, title, data, color, width, file, properties, properties_from_file,
            --palette--;;lonlatinfo'
        ]
    ],
    'palettes' => [
        'lonlatinfo' => [
            'showitem' => 'min_lon, min_lat, max_lon, max_lat'
        ]
    ]
];
