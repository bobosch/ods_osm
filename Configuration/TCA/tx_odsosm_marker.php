<?php
return array (
	'ctrl' => array (
		'title' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_marker',        
		'label' => 'title',    
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',    
		'delete' => 'deleted',    
		'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/icon_tx_odsosm_marker.png',
	),
	'interface' => array (
		'showRecordFieldList' => 'title,icon,offset_x,offset_y'
	),
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
?>