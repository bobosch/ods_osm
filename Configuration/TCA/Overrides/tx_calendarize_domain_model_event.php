<?php

defined('TYPO3') || die();

$tempColumns = array(
    'tx_odsosm_lon' => array( // DECIMAL(9,6)
        'exclude' => 1,
        'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_lon',
        'config' => array(
            'type' => 'input',
            'size' => 11,
            'max' => 11,
            'checkbox' => '0.000000',
            'eval' => 'Bobosch\\OdsOsm\\Evaluation\\LonLat',
            'fieldControl' => [
	            'locationMap' => [
		            'renderType' => 'coordinatepickerWizard'
	            ]
            ],
            'default' => 0.000000,
        )
    ),
    'tx_odsosm_lat' => array( // DECIMAL(8,6)
        'exclude' => 1,
        'label' => 'LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:tx_odsosm_lat',
        'config' => array(
            'type' => 'input',
            'size' => 10,
            'max' => 10,
            'eval' => 'Bobosch\\OdsOsm\\Evaluation\\LonLat',
            'default' => 0.000000,
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tx_calendarize_domain_model_event', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tx_calendarize_domain_model_event', 'tx_odsosm_lon', '', 'after:location');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField('tx_calendarize_domain_model_event', 'tx_odsosm_lon', 'tx_odsosm_lat');
