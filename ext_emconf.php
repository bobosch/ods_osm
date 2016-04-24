<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'OpenStreetMap',
	'description' => 'Add an interactive OpenStreetMap map to your website. Can also show other OpenLayers compatible maps.',
	'author' => 'Robert Heel',
	'author_email' => 'typo3@bobosch.de',
	'category' => 'plugin',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-7.99.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'ods_osm_cal' => '',
			'ods_osm_tt_address' => '',
		),
	),
	'createDirs' => 'uploads/tx_odsosm/map',
	'state' => 'stable',
	'uploadfolder' => 1,
	'version' => '2.1.0',
);
?>