<?php

namespace Bobosch\OdsOsm\Traits;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait SettingsTrait
{
    protected function getSettings(): array
    {
        try {
            return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ods_osm');
        } catch (\Exception) {
            return [];
        }
    }
}
