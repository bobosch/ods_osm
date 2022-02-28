<?php

defined('TYPO3') || die();

/* --------------------------------------------------
	Extend existing tables
-------------------------------------------------- */

$tempColumns = array(
    'tx_odsosm_marker' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tt_address_group.tx_odsosm_marker',
        'config' => array(
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'tx_odsosm_marker',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'default' => 0,
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups', 'tx_odsosm_marker');
