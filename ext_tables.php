<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

/* --------------------------------------------------
	Extend existing tables
-------------------------------------------------- */
$tempColumns = array (
	'tx_odsosm_lon' => array ( // DECIMAL(9,6)
		'exclude' => 1,
		'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_lon',
		'config' => array (
			'type' => 'input',
			'size' => 11,
			'max' => 11,
			'checkbox' => '0.000000',
			'default' => '0.000000',
			'eval' => 'tx_lonlat',
			'wizards' => Array(
				'_PADDING' => 2,
				'0' => Array(
					'type' => 'popup',
					'title' => 'Search coordinates',
					'script' => 'EXT:ods_osm/wizard/index.php',
					'icon' => 'EXT:ods_osm/wizard/osm.png',
					'JSopenParams' => ',width=600,height=400,status=0,menubar=0,scrollbars=0',
				)
			),
		)
	),
	'tx_odsosm_lat' => array ( // DECIMAL(8,6)
		'exclude' => 1,
		'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_lat',
		'config' => array (
			'type' => 'input',
			'size' => 10,
			'max' => 10,
			'eval' => 'tx_lonlat',
		)
	),
);

t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users',$tempColumns,1);
if(t3lib_div::compat_version('4.3')){
	t3lib_extMgm::addToAllTCAtypes('fe_users','tx_odsosm_lon','','after:country');
	t3lib_extMgm::addFieldsToAllPalettesOfField('fe_users','tx_odsosm_lon','tx_odsosm_lat');
}else{
	t3lib_extMgm::addToAllTCAtypes('fe_users','tx_odsosm_lon, tx_odsosm_lat','','after:country');
}

t3lib_div::loadTCA('tt_address');
t3lib_extMgm::addTCAcolumns('tt_address',$tempColumns,1);
if(t3lib_div::compat_version('4.3')){
	t3lib_extMgm::addToAllTCAtypes('tt_address','tx_odsosm_lon','','after:city');
	t3lib_extMgm::addFieldsToAllPalettesOfField('tt_address','tx_odsosm_lon','tx_odsosm_lat');
}else{
	t3lib_extMgm::addToAllTCAtypes('tt_address','tx_odsosm_lon, tx_odsosm_lat','','after:city');
}


$tempColumns = array (
	'tx_odsosm_marker' => array (        
		'exclude' => 1,        
		'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tt_address_group.tx_odsosm_marker',        
		'config' => array (
			'type' => 'group',    
			'internal_type' => 'db',    
			'allowed' => 'tx_odsosm_marker',    
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
);

t3lib_div::loadTCA('fe_groups');
t3lib_extMgm::addTCAcolumns('fe_groups',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_groups','tx_odsosm_marker;;;;1-1-1');

t3lib_div::loadTCA('tt_address_group');
t3lib_extMgm::addTCAcolumns('tt_address_group',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tt_address_group','tx_odsosm_marker;;;;1-1-1');

/* --------------------------------------------------
	New tables
-------------------------------------------------- */
$TCA['tx_odsosm_geocache'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_geocache',
		'label'     => 'zip',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY zip',
		'delete' => 'deleted',
		'rootLevel' => 1,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_odsosm_geocache.png',
	),
);

$TCA['tx_odsosm_layer'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_layer',        
		'label'     => 'title',    
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'rootLevel' => 1,
		'enablecolumns' => array (        
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_odsosm_layer.png',
	),
);

t3lib_extMgm::allowTableOnStandardPages('tx_odsosm_marker');
$TCA['tx_odsosm_marker'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_marker',        
		'label'     => 'title',    
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',    
		'delete' => 'deleted',    
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_odsosm_marker.png',
	),
);

t3lib_extMgm::allowTableOnStandardPages('tx_odsosm_track');
t3lib_extMgm::addToInsertRecords('tx_odsosm_track');
$TCA['tx_odsosm_track'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_track',        
		'label'     => 'title',    
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',    
		'delete' => 'deleted',    
		'enablecolumns' => array (        
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_odsosm_track.png',
	),
);

t3lib_extMgm::allowTableOnStandardPages('tx_odsosm_vector');
t3lib_extMgm::addToInsertRecords('tx_odsosm_vector');
$TCA['tx_odsosm_vector'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_vector',        
		'label'     => 'title',    
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',    
		'delete' => 'deleted',    
		'enablecolumns' => array (        
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_odsosm_vector.png',
	),
);

/* --------------------------------------------------
	Plugin
-------------------------------------------------- */
t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addPlugin(array(
	'LLL:EXT:ods_osm/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
),'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY . '/pi1/flexform.xml');
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1'] ='pi_flexform';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';

if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_odsosm_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_odsosm_pi1_wizicon.php';
}
?>