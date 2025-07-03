<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/* --------------------------------------------------
	Plugin
-------------------------------------------------- */

ExtensionManagementUtility::addPlugin(
	[
		'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tt_content.CType.ods_osm_pi1',
		'ods_osm_pi1'
	],
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
	'ods_osm'
);

ExtensionManagementUtility::addPiFlexFormValue(
	'*',
	'FILE:EXT:ods_osm/Configuration/Flexform/flexform_basic.xml',
    'ods_osm_pi1'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', '--div--;Configuration,pi_flexform,', 'ods_osm_pi1', 'after:subheader');
