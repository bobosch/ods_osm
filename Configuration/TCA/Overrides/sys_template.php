<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addStaticFile(
    'ods_osm',
    'Configuration/TypoScript/',
    'Template OpenStreetMap'
);

ExtensionManagementUtility::addStaticFile(
    'ods_osm',
    'Configuration/TypoScript/Calendarize/',
    'Template OpenStreetMap for Calendarize'
);
