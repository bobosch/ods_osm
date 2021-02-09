<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache',
        'label' => 'zip',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => '',
        'default_sortby' => 'ORDER BY zip',
        'delete' => 'deleted',
        'rootLevel' => 1,
        'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/icon_tx_odsosm_geocache.png',
    ),
    'interface' => array(
        'showRecordFieldList' => 'cache_hit,service_hit,search_city,country,state,city,zip,street,housenumber,lon,lat'
    ),
    'columns' => array(
        'cache_hit' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.cache_hit',
            'config' => array(
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'eval' => '',
            )
        ),
        'service_hit' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.service_hit',
            'config' => array(
                'type' => 'input',
                'size' => 10,
                'max' => 10,
                'eval' => '',
            )
        ),
        'search_city' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.city',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            )
        ),
        'country' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.country',
            'config' => array(
                'type' => 'input',
                'size' => 2,
                'max' => 2,
            )
        ),
        'state' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.state',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            )
        ),
        'city' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.city',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            )
        ),
        'zip' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.zip',
            'config' => array(
                'type' => 'input',
                'size' => 5,
                'max' => 5,
            )
        ),
        'street' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.street',
            'config' => array(
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            )
        ),
        'housenumber' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.housenumber',
            'config' => array(
                'type' => 'input',
                'size' => 5,
                'max' => 5,
            )
        ),
        'lon' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.lon',
            'config' => array(
                'type' => 'input',
                'size' => 11,
                'max' => 11,
                'eval' => 'Bobosch\\OdsOsm\\Evaluation\\LonLat',
            )
        ),
        'lat' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tx_odsosm_geocache.lat',
            'config' => array(
                'type' => 'input',
                'size' => 11,
                'max' => 10,
                'eval' => 'Bobosch\\OdsOsm\\Evaluation\\LonLat',
            )
        ),
    ),
    'types' => [
        '0' => [
            'showitem' => 'cache_hit,service_hit,search_city,country,state,city,zip,street,housenumber,lon
            --palette--;;latinfo'
        ]
    ],
    'palettes' => [
        'latinfo' => [
            'showitem' => 'lat'
        ]
    ]
);
