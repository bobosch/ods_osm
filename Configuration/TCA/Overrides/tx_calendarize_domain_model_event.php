<?php
defined('TYPO3') || die();

use Bobosch\OdsOsm\Evaluation\LonLat;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (ExtensionManagementUtility::isLoaded('calendarize')) {
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

    ExtensionManagementUtility::addTCAcolumns('tx_calendarize_domain_model_event', $tempColumns);
    ExtensionManagementUtility::addToAllTCAtypes('tx_calendarize_domain_model_event', 'tx_odsosm_lon', '', 'after:location');
    ExtensionManagementUtility::addFieldsToAllPalettesOfField('tx_calendarize_domain_model_event', 'tx_odsosm_lon', 'tx_odsosm_lat');
}
