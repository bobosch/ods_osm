<?php

defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/* --------------------------------------------------
	Plugin
-------------------------------------------------- */

ExtensionManagementUtility::addPlugin(
	array(
		'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi1',
		'ods_osm_pi1'
	),
	'list_type',
	'ods_osm'
);

ExtensionManagementUtility::addPiFlexFormValue(
	'ods_osm_pi1',
	'FILE:EXT:ods_osm/Configuration/Flexform/flexform_basic.xml'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ods_osm_pi1']     = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ods_osm_pi1'] = 'layout,select_key,pages,recursive';

// Avoid PHP 8.1 errors due to not available index if extensions are not installed
if (! ExtensionManagementUtility::isLoaded('tt_address')) {
	$GLOBALS['TCA']['tt_address'] = [
		'ctrl' => [
			'title' => '',
			'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/osm.png'
		]
	];
}
if (! ExtensionManagementUtility::isLoaded('calendarize')) {
	$GLOBALS['TCA']['tx_calendarize_domain_model_event'] = [
		'ctrl' => [
			'title' => '',
			'iconfile' => 'EXT:ods_osm/Resources/Public/Icons/osm.png'
		]
	];
}
