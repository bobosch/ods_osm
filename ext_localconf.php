<?php
defined('TYPO3') || die();

use Bobosch\OdsOsm\Backend\FormDataProvider\FlexFormManipulation;
use Bobosch\OdsOsm\Evaluation\LonLat;
use Bobosch\OdsOsm\TceMain;
use Bobosch\OdsOsm\Updates\FileLocationUpdater;
use Bobosch\OdsOsm\Updates\MigrateSettings;
use Bobosch\OdsOsm\Wizard\CoordinatepickerWizard;
use Bobosch\OdsOsm\Wizard\VectordrawWizard;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

ExtensionManagementUtility::addPItoST43(
    'ods_osm',
    '',
    '_pi1',
    'list_type',
    1
);

ExtensionManagementUtility::addPageTSConfig('
mod.wizards.newContentElement.wizardItems.plugins.elements.odsosm {
    iconIdentifier = ods_osm
    title = LLL:EXT:ods_osm/Resources/Private/Language/locallang.xlf:pi1_title
    description = LLL:EXT:ods_osm/Resources/Private/Language/locallang.xlf:pi1_plus_wiz_description
    tt_content_defValues {
        CType = list
        list_type = ods_osm_pi1
    }
}

mod.wizards.newContentElement.wizardItems.plugins.show := addToList(odsosm)
');

ExtensionManagementUtility::addUserTSConfig('
    options.saveDocNew.tx_odsosm_track=1
');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = TceMain::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][LonLat::class] = '';

// Modify flexform fields since core 8.5 via formEngine: Inject a data provider between TcaFlexPrepare and TcaFlexProcess
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][FlexFormManipulation::class] = [
    'depends' => [
        TcaFlexPrepare::class,
    ],
    'before' => [
        TcaFlexProcess::class,
    ],
];

// Add wizard with map for setting geo location
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1616876515] = [
    'nodeName' => 'coordinatepickerWizard',
    'priority' => 30,
    'class' => CoordinatepickerWizard::class
];

// Add wizard with map for drawing GeoJSON data
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1616968355] = [
    'nodeName' => 'vectordrawWizard',
    'priority' => 30,
    'class' => VectordrawWizard::class
];

// Register icons
$icons = [
    'coordinate-picker-wizard' => 'ce_wiz.png',
    'vectordraw-wizard' => 'vector.png',
    'ods_osm' => 'osm.png'
];
$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
foreach ($icons as $identifier => $path) {
    $iconRegistry->registerIcon(
        $identifier,
        BitmapIconProvider::class,
        ['source' => 'EXT:ods_osm/Resources/Public/Icons/' . $path]
    );
}

# add migration wizards
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['odsOsmFileLocationUpdater'] = FileLocationUpdater::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['odsOsmMigrateSettings'] = MigrateSettings::class;
