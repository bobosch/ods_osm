<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ods_osm".
 *
 * Auto generated 17-12-2013 19:54
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'OpenStreetMap',
	'description' => 'Add an interactive OpenStreetMap map to your website. Can also show other OpenLayers compatible maps.',
	'category' => 'plugin',
	'author' => 'Robert Heel',
	'author_email' => 'typo3@bobosch.de',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => 'uploads/tx_odsosm/map',
	'modify_tables' => 'fe_groups,fe_users,tt_address,tt_address_group',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.11.7',
	'constraints' => array(
		'depends' => array(
			'tt_address' => '',
			'typo3' => '4.5.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

?>