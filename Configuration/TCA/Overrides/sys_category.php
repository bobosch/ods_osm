<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
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
            'allowed' => 'tx_odsosm_marker',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'default' => 0,
        )
    ),
);

ExtensionManagementUtility::addTCAcolumns('sys_category', $tempColumns);
ExtensionManagementUtility::addToAllTCAtypes('sys_category', 'tx_odsosm_marker');
