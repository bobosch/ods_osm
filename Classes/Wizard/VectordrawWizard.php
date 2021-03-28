<?php
declare(strict_types = 1);

namespace Bobosch\OdsOsm\Wizard;

/**
 * This file is part of the "ods_osm" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Localization\LanguageService;

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

        $nameLongitude = $paramArray['itemFormElName'];

        if (strpos($nameLongitude, '[pi_flexform]') > 0) {
            // it's a call inside a flexform
            $lon = $row["pi_flexform"]["data"]["sDEF"]["lDEF"]["lon"]["vDEF"] != '' ? htmlspecialchars($row["pi_flexform"]["data"]["sDEF"]["lDEF"]["lon"]["vDEF"]) : '';
            $lat = $row["pi_flexform"]["data"]["sDEF"]["lDEF"]["lat"]["vDEF"] != '' ? htmlspecialchars($row["pi_flexform"]["data"]["sDEF"]["lDEF"]["lat"]["vDEF"]) : '';
        } else {
            $lat = $row['tx_odsosm_lat'] != '' ? htmlspecialchars($row['tx_odsosm_lat']) : '';
            $lon = $row['tx_odsosm_lon'] != '' ? htmlspecialchars($row['tx_odsosm_lon']) : '';
        }

        $nameLatitude = str_replace('lon', 'lat', $nameLongitude);
        $nameLatitudeActive = str_replace('data', 'control[active]', $nameLatitude);
        $geoCodeUrl = '';
        $gLat = '55.6760968';
        $gLon = '12.5683371';

        $resultArray['iconIdentifier'] = 'coordinate-picker-wizard';
        $resultArray['title'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:coordinatepicker.search_coordinates');
        $resultArray['linkAttributes']['class'] = 'vectordrawWizard ';
        $resultArray['linkAttributes']['data-label-title'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:coordinatepicker.search_coordinates');
        $resultArray['linkAttributes']['data-label-close'] = $this->getLanguageService()->sL('LLL:EXT:tt_address/Resources/Private/Language/locallang_db.xlf:tt_address.locationMapWizard.close');
        $resultArray['linkAttributes']['data-label-import'] = $this->getLanguageService()->sL('LLL:EXT:tt_address/Resources/Private/Language/locallang_db.xlf:tt_address.locationMapWizard.import');
        $resultArray['linkAttributes']['data-lat'] = $lat;
        $resultArray['linkAttributes']['data-lon'] = $lon;
        $resultArray['linkAttributes']['data-glat'] = $gLat;
        $resultArray['linkAttributes']['data-glon'] = $gLon;
        $resultArray['linkAttributes']['data-geocodeurl'] = $geoCodeUrl;
        $resultArray['linkAttributes']['data-geocodeurlshort'] = $geoCodeUrlShort;
        $resultArray['linkAttributes']['data-namelat'] = htmlspecialchars($nameLatitude);
        $resultArray['linkAttributes']['data-namelon'] = htmlspecialchars($nameLongitude);
        $resultArray['linkAttributes']['data-namelat-active'] = htmlspecialchars($nameLatitudeActive);
        $resultArray['linkAttributes']['data-tiles'] = htmlspecialchars('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
        $resultArray['linkAttributes']['data-copy'] = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
        $resultArray['stylesheetFiles'][] = 'EXT:ods_osm/Resources/Public/JavaScript/Leaflet/leaflet-draw/leaflet.draw.css';
        $resultArray['stylesheetFiles'][] = 'EXT:ods_osm/Resources/Public/JavaScript/Leaflet/Core/leaflet.css';
        $resultArray['stylesheetFiles'][] = 'EXT:ods_osm/Resources/Public/Css/Backend/drawvectorWizard.css';
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/OdsOsm/Leaflet/Core/leaflet';
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/OdsOsm/Backend/Vectordraw';

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
