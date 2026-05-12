<?php

defined('TYPO3') || die();

use Bobosch\OdsOsm\Backend\FormDataProvider\FlexFormManipulation;
use Bobosch\OdsOsm\Evaluation\LonLat;
use Bobosch\OdsOsm\TceMain;
use Bobosch\OdsOsm\Wizard\CoordinatepickerWizard;
use Bobosch\OdsOsm\Wizard\VectordrawWizard;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess;

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
