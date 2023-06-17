<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Bobosch\OdsOsm\Evaluation\LonLat;

$tempColumns = [
    'tx_odsosm_lon' => [ // DECIMAL(9,6)
        'exclude' => 1,
        'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_lon',
        'config' => [
            'type' => 'input',
            'size' => 11,
            'max' => 11,
            'checkbox' => '0.000000',
            'eval' => LonLat::class,
            'fieldControl' => [
	            'locationMap' => [
		            'renderType' => 'coordinatepickerWizard'
	            ]
            ],
            'default' => 0.000000,
        ]
    ],
    'tx_odsosm_lat' => [ // DECIMAL(8,6)
        'exclude' => 1,
        'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_lat',
        'config' => [
            'type' => 'input',
            'size' => 10,
            'max' => 10,
            'eval' => LonLat::class,
            'default' => 0.000000,
        ]
    ],
];

ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns);
ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'tx_odsosm_lon', '', 'after:country');
ExtensionManagementUtility::addFieldsToAllPalettesOfField('fe_users', 'tx_odsosm_lon', 'tx_odsosm_lat');
