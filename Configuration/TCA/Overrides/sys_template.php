<?php

defined('TYPO3') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'ods_osm',
    'Configuration/TypoScript/',
    'Template OpenStreetMap'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'ods_osm',
    'Configuration/TypoScript/Calendarize/',
    'Template OpenStreetMap for Calendarize'
);
