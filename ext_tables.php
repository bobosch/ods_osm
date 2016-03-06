<?php
if (!defined('TYPO3_MODE')) die('Access denied.');

/* --------------------------------------------------
	Extend existing tables
-------------------------------------------------- */
$GLOBALS['TCA']['tt_address']['columns']['longitude']['config']['wizards'] = array(
	'coordinatepicker' => array(
		'type' => 'popup',
		'title' => 'LLL:EXT:ods_osm/locallang_db.xml:coordinatepicker.search_coordinates',
		'icon' => 'EXT:ods_osm/Resources/Public/Icons/osm.png',
		'module' => array(
			'name' => 'wizard_coordinatepicker',
		),
		'JSopenParams' => 'height=600,width=800,status=0,menubar=0,scrollbars=0',
	)
);

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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups',$tempColumns,1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups','tx_odsosm_marker;;;;1-1-1');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_category',$tempColumns,1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_category','tx_odsosm_marker;;;;1-1-1');

/* --------------------------------------------------
	New tables
-------------------------------------------------- */

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_odsosm_marker');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_odsosm_track');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_odsosm_track');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_odsosm_vector');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_odsosm_vector');

/* --------------------------------------------------
	Plugin
-------------------------------------------------- */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array(
		'LLL:EXT:ods_osm/locallang_db.xml:tt_content.list_type_pi1',
		$_EXTKEY . '_pi1',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'ext_icon.gif'
	),
	'list_type'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY . '/pi1/flexform.xml');
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1'] ='pi_flexform';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';

if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_odsosm_pi1_wizicon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'pi1/class.tx_odsosm_pi1_wizicon.php';

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_func',
		'tx_odsosm_geocodeWizard',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'func_wizards/class.tx_odsosm_geocodeWizard.php',
		'LLL:EXT:ods_osm/locallang.xml:wiz_geocode'
	);
}
?>