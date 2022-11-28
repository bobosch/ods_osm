<?php

if (!defined('TYPO3')) die ('Access denied.');

use TYPO3\CMS\Core\Utility\GeneralUtility;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    'ods_osm',
    null,
    '_pi1',
    'list_type',
    1
);

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '
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
    '
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
        '
        options.saveDocNew.tx_odsosm_track=1
    '
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Bobosch\OdsOsm\TceMain::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\Bobosch\OdsOsm\Evaluation\LonLat::class] = '';

    // Add wizard with map for setting geo location
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1616876515] = [
        'nodeName' => 'coordinatepickerWizard',
        'priority' => 30,
        'class' => \Bobosch\OdsOsm\Wizard\CoordinatepickerWizard::class
    ];

    // Add wizard with map for drawing GeoJSON data
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1616968355] = [
        'nodeName' => 'vectordrawWizard',
        'priority' => 30,
        'class' => \Bobosch\OdsOsm\Wizard\VectordrawWizard::class
    ];

    // Register icons
    $icons = [
        'coordinate-picker-wizard' => 'ce_wiz.png',
        'vectordraw-wizard' => 'vector.png',
        'ods_osm' => 'osm.png'
    ];
    $iconRegistry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    foreach ($icons as $identifier => $path) {
        $iconRegistry->registerIcon(
            $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:ods_osm/Resources/Public/Icons/' . $path]
        );
    }

    # add migration wizards
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\Bobosch\OdsOsm\Updates\FileLocationUpdater::class]
        = \Bobosch\OdsOsm\Updates\FileLocationUpdater::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\Bobosch\OdsOsm\Updates\MigrateSettings::class]
        = \Bobosch\OdsOsm\Updates\MigrateSettings::class;

}

call_user_func(
    function () {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('calendarize')) {
            // XCLASS event
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][HDNET\Calendarize\Domain\Model\Event::class] = [
                'className' => \Bobosch\OdsOsm\Domain\Model\Event::class
            ];

            // Register extended domain class
            GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
                ->registerImplementation(
                    \HDNET\Calendarize\Domain\Model\Event::class,
                    \Bobosch\OdsOsm\Domain\Model\Event::class
                );
        }
    }
);
