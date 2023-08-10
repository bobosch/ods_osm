<?php
declare(strict_types = 1);

namespace Bobosch\OdsOsm\Wizard;

/**
 * This file is part of the "ods_osm" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Bobosch\OdsOsm\Div;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Adds a wizard for drawing vectors on a map
 */
class VectordrawWizard extends AbstractNode
{
    /**
     * @return array
     */
    public function render(): array
    {
        $row = $this->data['databaseRow'];
        $paramArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();
        $extConfig = Div::getConfig();

        $nameDataField = $paramArray['itemFormElName'];

        // calculate center point or use Kopenhagen as fallback
        if (!empty((float)$row['max_lon']) && !empty((float)$row['min_lon'])) {
            $lon = ($row['max_lon'] + $row['min_lon']) / 2;
        } else {
            $lon = $extConfig['default_lon'];
        }

        if (!empty((float)$row['max_lat']) && !empty((float)$row['min_lat'])) {
            $lat = ($row['max_lat'] + $row['min_lat']) / 2;
        } else {
            $lat = $extConfig['default_lat'];
        }

        $resultArray['iconIdentifier'] = 'vectordraw-wizard';
        $resultArray['title'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:vectordrawWizard');
        $resultArray['linkAttributes']['class'] = 'vectordrawWizard ';
        $resultArray['linkAttributes']['data-label-title'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:vectordrawWizard.title');
        $resultArray['linkAttributes']['data-label-close'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:vectordrawWizard.close');
        $resultArray['linkAttributes']['data-label-import'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:vectordrawWizard.import');
        $resultArray['linkAttributes']['data-minlat'] = empty((float)$row['min_lat']) ? null : $row['min_lat'];
        $resultArray['linkAttributes']['data-maxlat'] = empty((float)$row['max_lat']) ? null : $row['max_lat'];
        $resultArray['linkAttributes']['data-minlon'] = empty((float)$row['min_lon']) ? null : $row['min_lon'];
        $resultArray['linkAttributes']['data-maxlon'] = empty((float)$row['max_lon']) ? null : $row['max_lon'];
        $resultArray['linkAttributes']['data-lat'] = $lat;
        $resultArray['linkAttributes']['data-lon'] = $lon;
        $resultArray['linkAttributes']['data-fieldName'] = htmlspecialchars($nameDataField);
        $resultArray['linkAttributes']['data-fieldValue'] = $row['data'];
        $resultArray['linkAttributes']['data-tiles'] = htmlspecialchars('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
        $resultArray['linkAttributes']['data-copy'] = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
        $resultArray['stylesheetFiles'][] = 'EXT:ods_osm/Resources/Public/JavaScript/Leaflet/leaflet-draw/leaflet.draw.css';
        $resultArray['stylesheetFiles'][] = 'EXT:ods_osm/Resources/Public/JavaScript/Leaflet/Core/leaflet.css';
        $resultArray['stylesheetFiles'][] = 'EXT:ods_osm/Resources/Public/Css/Backend/drawvectorWizard.css';

        $versionInformation = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        if ($versionInformation > 11) {
            $id = StringUtility::getUniqueId('t3js-formengine-fieldcontrol-');
            $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
                'TYPO3/CMS/OdsOsm/Leaflet/Core/leaflet'
            )->instance($id);
            $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
                'TYPO3/CMS/OdsOsm/Backend/Vectordraw'
            )->instance($id);
        } else {
            $resultArray['requireJsModules'][] = 'TYPO3/CMS/OdsOsm/Leaflet/Core/leaflet';
            $resultArray['requireJsModules'][] = 'TYPO3/CMS/OdsOsm/Backend/Vectordraw';
        }

        return $resultArray;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
