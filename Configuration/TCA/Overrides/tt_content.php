<?php

if ( ! defined( 'TYPO3_MODE' ) ) {
	die( 'Access denied.' );
}

/* --------------------------------------------------
	Plugin
-------------------------------------------------- */

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array(
		'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1',
		'ods_osm_pi1'
	),
	'list_type',
	'ods_osm'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
	'ods_osm_pi1',
	'FILE:EXT:ods_osm/Configuration/Flexform/flexform_basic.xml'
);

if ( \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded( 'cal' ) ) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
		'ods_osm_pi1',
		'FILE:EXT:ods_osm/Configuration/Flexform/flexform_cal.xml'
	);
}

if ( \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded( 'tt_address' ) ) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
		'ods_osm_pi1',
		'FILE:EXT:ods_osm/Configuration/Flexform/flexform_ttaddress.xml'
	);
}

if ( \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded( 'cal' ) && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded( 'tt_address' ) ) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
		'ods_osm_pi1',
		'FILE:EXT:ods_osm/Configuration/Flexform/flexform_cal_ttaddress.xml'
	);
}

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ods_osm_pi1']     = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ods_osm_pi1'] = 'layout,select_key,pages,recursive';

?>
