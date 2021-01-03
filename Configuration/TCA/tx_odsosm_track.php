<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_track',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/icon_tx_odsosm_track.png',
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,title,color,width,file,min_lat,min_lon,max_lat,max_lon'
    ),
    'columns' => array(
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
                        1 => '',
                        'invertStateDisplay' => true
                    ]
                ],
            ]
        ],
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_track.title',
            'config' => array(
                'type' => 'input',
                'size' => 30,
            )
        ),
        'color' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_track.color',
            'config' => array(
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'default' => '#37b7ff',
                'eval' => 'nospace,trim',
                'renderType' => 'colorpicker',
            )
        ),
        'width' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_track.width',
            'config' => array(
                'type' => 'input',
                'size' => 3,
                'max' => 3,
                'default' => 5,
                'range' => array(
                    'lower' => 1,
                    'upper' => 255
                ),
                'eval' => 'int',
            )
        ),
        'file' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_track.file',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'file',
                [
                    'maxitems' => 1,
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                    ],
                    'foreign_match_fields' => [
                        'fieldname' => 'file',
                        'tablenames' => 'tx_odsosm_track',
                        'table_local' => 'sys_file',
                    ],
                    'default' => 0,
                ],
                'gpx,json,kml,wkt'
            )
        ),
        'min_lon' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_track.min_lon',
            'config' => array(
                'type' => 'none',
                'size' => 8,
            )
        ),
        'min_lat' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_track.min_lat',
            'config' => array(
                'type' => 'none',
                'size' => 8,
            )
        ),
        'max_lon' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_track.max_lon',
            'config' => array(
                'type' => 'none',
                'size' => 8,
            )
        ),
        'max_lat' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_track.max_lat',
            'config' => array(
                'type' => 'none',
                'size' => 8,
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden, title, color, width, file')
    ),
    'palettes' => array(
        '1' => array(
            'canNotCollapse' => true,
            'showitem' => 'min_lon, min_lat, max_lon, max_lat'
        )
    )
);
