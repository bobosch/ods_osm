<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'rootLevel' => 1,
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/icon_tx_odsosm_layer.png',
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
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.title',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'overlay' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.overlay',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'javascript_include' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.javascript_include',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'checkbox' => '',
            )
        ),
        'javascript_openlayers' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.javascript_openlayers',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'static_url' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.static_url',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'tile_url' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.tile_url',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'tile_https' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.tile_https',
            'config' => array(
                'type' => 'check',
                'default' => '0',
            )
        ),
        'min_zoom' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.min_zoom',
            'config' => array(
                'type' => 'input',
                'size' => '2',
                'eval' => 'num',
            )
        ),
        'max_zoom' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.max_zoom',
            'config' => array(
                'type' => 'input',
                'size' => '2',
                'eval' => 'num',
            )
        ),
        'subdomains' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.subdomains',
            'config' => array(
                'type' => 'input',
                'size' => '5',
            )
        ),
        'attribution' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.attribution',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'homepage' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_layer.homepage',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden, title, overlay, javascript_include, javascript_openlayers, static_url, tile_url, tile_https, min_zoom, max_zoom, subdomains, attribution, homepage')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
