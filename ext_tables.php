<?php

if ( ! defined( 'TYPO3_MODE' ) ) {
	die( 'Access denied.' );
}

/* --------------------------------------------------
	New tables
-------------------------------------------------- */

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'tx_odsosm_marker' );

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'tx_odsosm_track' );
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords( 'tx_odsosm_track' );

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'tx_odsosm_vector' );
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords( 'tx_odsosm_vector' );

/* --------------------------------------------------
	Backend module
-------------------------------------------------- */

/**
 * Register icons
 */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance( \TYPO3\CMS\Core\Imaging\IconRegistry::class );
$iconRegistry->registerIcon(
	'ods_osm',
	\TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
	[ 'source' => 'EXT:ods_osm/Resources/Public/Icons/osm.png' ]
);
$iconRegistry->registerIcon(
	'ods_osm_wizard',
	\TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
	[ 'source' => 'EXT:ods_osm/Resources/Public/Icons/ce_wiz.png' ]
);