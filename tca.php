<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$TCA['tx_odsosm_geocache'] = array (
	'ctrl' => $TCA['tx_odsosm_geocache']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'cache_hit,service_hit,search_city,country,state,city,zip,street,housenumber,lon,lat'
	),
	'feInterface' => $TCA['tx_odsosm_geocache']['feInterface'],
	'columns' => array (
		'cache_hit' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.cache_hit',
			'config' => array (
				'type' => 'input',
				'size' => 10,
				'max' => 10,
				'eval' => '',
			)
		),
		'service_hit' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.service_hit',
			'config' => array (
				'type' => 'input',
				'size' => 10,
				'max' => 10,
				'eval' => '',
			)
		),
		'search_city' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.city',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			)
		),
		'country' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.country',
			'config' => array (
				'type' => 'input',
				'size' => 2,
				'max' => 2,
			)
		),
		'state' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.state',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			)
		),
		'city' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.city',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			)
		),
		'zip' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.zip',
			'config' => array (
				'type' => 'input',
				'size' => 5,
				'max' => 5,
			)
		),
		'street' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.street',
			'config' => array (
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			)
		),
		'housenumber' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.housenumber',
			'config' => array (
				'type' => 'input',
				'size' => 5,
				'max' => 5,
			)
		),
		'lon' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.lon',
			'config' => array (
				'type' => 'input',
				'size' => 11,
				'max' => 11,
				'eval' => 'tx_lonlat',
			)
		),
		'lat' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache.lat',
			'config' => array (
				'type' => 'input',
				'size' => 11,
				'max' => 10,
				'eval' => 'tx_lonlat',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'cache_hit,service_hit,search_city,country,state,city,zip,street,housenumber,lon;;1;;1-1-1')
	),
	'palettes' => array (
		'1' => array(
			'canNotCollapse' => true,
			'showitem' => 'lat'
		)
	)
);

$TCA['tx_odsosm_layer'] = array (
	'ctrl' => $TCA['tx_odsosm_layer']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title,overlay,javascript_include,javascript_leaflet,javascript_openlayers,javascript_openlayers3,static_url,tile_url,max_zoom,subdomains,attribution,homepage'
	),
	'feInterface' => $TCA['tx_odsosm_layer']['feInterface'],
	'columns' => array (
		'hidden' => array (        
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array (
				'type'  => 'check',
				'default' => '0'
			)
		),
		'title' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.title',        
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'overlay' => array (        
			'exclude' => 0,
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.overlay',
			'config' => array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'javascript_include' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.javascript_include',        
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'checkbox' => '',
			)
		),
		'javascript_leaflet' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.javascript_leaflet',        
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'javascript_openlayers' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.javascript_openlayers',        
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'javascript_openlayers3' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.javascript_openlayers3',        
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'static_url' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.static_url',        
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'tile_url' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.tile_url',        
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'max_zoom' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.max_zoom',        
			'config' => array (
				'type' => 'input',
				'size' => '2',
				'eval' => 'num',
			)
		),
		'subdomains' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.subdomains',        
			'config' => array (
				'type' => 'input',
				'size' => '5',
			)
		),
		'attribution' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.attribution',        
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'homepage' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer.homepage',        
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;;;1-1-1, title, overlay, javascript_include, javascript_leaflet, javascript_openlayers, javascript_openlayers3, static_url, tile_url, max_zoom, subdomains, attribution, homepage')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_odsosm_marker'] = array (
	'ctrl' => $TCA['tx_odsosm_marker']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'title,icon,offset_x,offset_y'
	),
	'feInterface' => $TCA['tx_odsosm_marker']['feInterface'],
	'columns' => array (
		'title' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_marker.title',        
			'config' => array (
				'type' => 'input',    
				'size' => '30',
			)
		),
		'icon' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_marker.icon',        
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'gif,png,jpeg,jpg',    
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],    
				'uploadfolder' => 'uploads/tx_odsosm',
				'show_thumbs' => 1,    
				'size' => 1,    
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'size_x' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_marker.size_x',        
			'config' => array (
				'type'     => 'input',
				'size'     => 4,
				'max'      => 4,
				'eval'     => 'int',
				'checkbox' => 0,
				'range'    => array (
					'lower' => 0,
					'upper' => 9999,
				),
				'default' => 0
			)
		),
		'size_y' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_marker.size_y',        
			'config' => array (
				'type'     => 'input',
				'size'     => 4,
				'max'      => 4,
				'eval'     => 'int',
				'checkbox' => 0,
				'range'    => array (
					'lower' => 0,
					'upper' => 9999,
				),
				'default' => 0
			)
		),
		'offset_x' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_marker.offset_x',        
			'config' => array (
				'type'     => 'input',
				'size'     => 5,
				'max'      => 5,
				'eval'     => 'int',
				'checkbox' => 0,
				'range'    => array (
					'lower' => -9999,
					'upper' => 9999,
				),
				'default' => 0
			)
		),
		'offset_y' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_marker.offset_y',        
			'config' => array (
				'type'     => 'input',
				'size'     => 5,
				'max'      => 5,
				'eval'     => 'int',
				'checkbox' => 0,
				'range'    => array (
					'lower' => -9999,
					'upper' => 9999,
				),
				'default' => 0
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'title;;;;1-1-1, icon;;1;;2-2-2')
	),
	'palettes' => array (
		'1' => array(
			'canNotCollapse' => true,
			'showitem' => 'size_x, size_y, offset_x, offset_y'
		)
	)
);

$TCA['tx_odsosm_track'] = array (
	'ctrl' => $TCA['tx_odsosm_track']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,visible,title,color,width,file,min_lat,min_lon,max_lat,max_lon'
	),
	'feInterface' => $TCA['tx_odsosm_track']['feInterface'],
	'columns' => array (
		'hidden' => array (        
			'exclude' => 0,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => 0
			)
		),
		'visible' => array (        
			'exclude' => 0,
			'label'   => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.visible',
			'config'  => array (
				'type'    => 'check',
				'default' => 1
			)
		),
		'title' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.title',        
			'config' => array (
				'type' => 'input',    
				'size' => 30,
			)
		),
		'color' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.color',        
			'config' => array (
				'type' => 'input',    
				'size' => 10,
				'max' => 10,
				'default' => '#37b7ff',
				'eval' => 'nospace,trim',
				'wizards' => array (
					'colorpick' => array (
						'type' => 'colorbox',
						'title' => 'LLL:EXT:lang/locallang_wizards.xml:colorpicker_title',
						'script' => 'wizard_colorpicker.php',
						'dim' => '20x20',
						'tableStyle' => 'border: solid 1px black; margin-left: 10px;',
						'JSopenParams' => 'height=550,width=370,status=0,menubar=0,scrollbars=1',
						'exampleImg' => 'gfx/wizard_colorpickerex.jpg',
					)
				)
			)
		),
		'width' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.width',        
			'config' => array (
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
		'file' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.file',        
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'gpx,json,kml,wkt',    
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],    
				'uploadfolder' => 'uploads/tx_odsosm',
				'size' => 1,    
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'min_lon' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.min_lon',        
			'config' => array (
				'type' => 'none',
				'size' => 8,
			)
		),
		'min_lat' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.min_lat',        
			'config' => array (
				'type' => 'none',
				'size' => 8,
			)
		),
		'max_lon' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.max_lon',        
			'config' => array (
				'type' => 'none',
				'size' => 8,
			)
		),
		'max_lat' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.max_lat',        
			'config' => array (
				'type' => 'none',
				'size' => 8,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden,visible;;;;1-1-1, title;;;;2-2-2, color, width, file;;1;;3-3-3')
	),
	'palettes' => array (
		'1' => array(
			'canNotCollapse' => true,
			'showitem' => 'min_lon, min_lat, max_lon, max_lat'
		)
	)
);

$TCA['tx_odsosm_vector'] = array (
	'ctrl' => $TCA['tx_odsosm_vector']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title,data,min_lat,min_lon,max_lat,max_lon'
	),
	'feInterface' => $TCA['tx_odsosm_vector']['feInterface'],
	'columns' => array (
		'hidden' => array (        
			'exclude' => 0,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => 0
			)
		),
		'title' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_vector.title',        
			'config' => array (
				'type' => 'input',    
				'size' => 30,
			)
		),
		'data' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_vector.data',        
			'config' => array (
				'type' => 'input',    
				'size' => 30,
				'max' => 10000,
				'wizards' => Array(
					'_PADDING' => 2,
					'0' => Array(
						'type' => 'popup',
						'title' => 'Search coordinates',
						'script' => 'EXT:ods_osm/wizard/index.php?mode=vector',
						'icon' => 'EXT:ods_osm/wizard/vector.png',
						'JSopenParams' => ',width=600,height=400,status=0,menubar=0,scrollbars=0',
					)
				),
			)
		),
		'min_lon' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.min_lon',        
			'config' => array (
				'type' => 'none',
				'size' => 8,
			)
		),
		'min_lat' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.min_lat',        
			'config' => array (
				'type' => 'none',
				'size' => 8,
			)
		),
		'max_lon' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.max_lon',        
			'config' => array (
				'type' => 'none',
				'size' => 8,
			)
		),
		'max_lat' => array (        
			'exclude' => 0,        
			'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track.max_lat',        
			'config' => array (
				'type' => 'none',
				'size' => 8,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;;;1-1-1, title;;;;2-2-2, data;;1;;3-3-3')
	),
	'palettes' => array (
		'1' => array(
			'canNotCollapse' => true,
			'showitem' => 'min_lon, min_lat, max_lon, max_lat'
		)
	)
);
?>