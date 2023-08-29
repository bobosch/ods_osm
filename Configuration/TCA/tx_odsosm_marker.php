<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
return [
    'ctrl' => [
        'title' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_marker',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/icon_tx_odsosm_marker.png',
    ],
    'columns' => [
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_marker.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ]
        ],
        'icon' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_marker.icon',
            'config' => ExtensionManagementUtility::getFileFieldTCAConfig(
                'icon',
                [
                    'maxitems' => 1,
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                    ],
                    'foreign_match_fields' => [
                        'fieldname' => 'icon',
                        'tablenames' => 'tx_odsosm_marker',
                    ],
                    'default' => 0,
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ],
        'size_x' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_marker.size_x',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'max' => 4,
                'eval' => 'int',
                'checkbox' => 0,
                'range' => [
                    'lower' => 0,
                    'upper' => 9999,
                ],
                'default' => 0
            ]
        ],
        'size_y' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_marker.size_y',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'max' => 4,
                'eval' => 'int',
                'checkbox' => 0,
                'range' => [
                    'lower' => 0,
                    'upper' => 9999,
                ],
                'default' => 0
            ]
        ],
        'offset_x' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_marker.offset_x',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'eval' => 'int',
                'checkbox' => 0,
                'range' => [
                    'lower' => -9999,
                    'upper' => 9999,
                ],
                'default' => 0
            ]
        ],
        'offset_y' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_marker.offset_y',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 5,
                'eval' => 'int',
                'checkbox' => 0,
                'range' => [
                    'lower' => -9999,
                    'upper' => 9999,
                ],
                'default' => 0
            ]
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, icon,
            --palette--;;sizeinfo'
        ]
    ],
    'palettes' => [
        'sizeinfo' => [
            'showitem' => 'size_x, size_y, offset_x, offset_y'
        ]
    ]
];
