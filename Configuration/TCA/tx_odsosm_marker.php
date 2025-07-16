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
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
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
            'config' => [
                'type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'maxitems' => 1,
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                ],
            ]
        ],
        'size_x' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_marker.size_x',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'max' => 4,
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
                'type' => 'number',
                'size' => 4,
                'max' => 4,
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
                'type' => 'number',
                'size' => 5,
                'max' => 5,
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
                'type' => 'number',
                'size' => 5,
                'max' => 5,
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
