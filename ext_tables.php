<?php

defined('TYPO3') || die();

/* --------------------------------------------------
	New tables
-------------------------------------------------- */

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'tx_odsosm_marker' );

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'tx_odsosm_track' );
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords( 'tx_odsosm_track' );

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages( 'tx_odsosm_vector' );
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords( 'tx_odsosm_vector' );
