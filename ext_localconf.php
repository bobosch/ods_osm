<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_odsosm_pi1.php', '_pi1', 'list_type', 1);

t3lib_extMgm::addUserTSConfig('
    options.saveDocNew.tx_odsosm_track=1
');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]='EXT:ods_osm/class.tx_odsosm_tcemain.php:tx_odsosm_tcemain';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_lonlat'] = 'EXT:ods_osm/class.tx_lonlat.php';
?>