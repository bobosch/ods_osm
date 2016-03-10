<?php
if (!defined('TYPO3_MODE')) die('Access denied.');

$tempColumns = array (
	'tx_odsosm_lon' => array ( // DECIMAL(9,6)
		'exclude' => 1,
		'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_lon',
		'config' => array (
			'type' => 'input',
			'size' => 11,
			'max' => 11,
			'checkbox' => '0.000000',
			'default' => '0.000000',
			'eval' => 'tx_lonlat',
			'wizards' => array(
				'coordinatepicker' => array(
					'type' => 'popup',
					'title' => 'LLL:EXT:ods_osm/locallang_db.xml:coordinatepicker.search_coordinates',
					'icon' => 'EXT:ods_osm/Resources/Public/Icons/osm.png',
					'module' => array(
							'name' => 'wizard_coordinatepicker',
					),
					'params' => array(
						'mode' => 'point',
					),
					'JSopenParams' => 'height=600,width=800,status=0,menubar=0,scrollbars=0',
				)
			)
		)
	),
	'tx_odsosm_lat' => array ( // DECIMAL(8,6)
		'exclude' => 1,
		'label' => 'LLL:EXT:ods_osm/locallang_db.xml:tx_odsosm_lat',
		'config' => array (
			'type' => 'input',
			'size' => 10,
			'max' => 10,
			'eval' => 'tx_lonlat',
		)
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users',$tempColumns,1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','tx_odsosm_lon','','after:country');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField('fe_users','tx_odsosm_lon','tx_odsosm_lat');
?>