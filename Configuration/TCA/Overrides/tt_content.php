<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/* --------------------------------------------------
	Plugin
-------------------------------------------------- */

ExtensionManagementUtility::addPlugin(
	[
		'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi1',
		'ods_osm_pi1'
	],
	'list_type',
	'ods_osm'
);

ExtensionManagementUtility::addPiFlexFormValue(
	'ods_osm_pi1',
	'FILE:EXT:ods_osm/Configuration/Flexform/flexform_basic.xml'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ods_osm_pi1']     = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ods_osm_pi1'] = 'layout,select_key,pages,recursive';
