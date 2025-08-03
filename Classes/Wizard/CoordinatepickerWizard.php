<?php

declare(strict_types=1);

namespace Bobosch\OdsOsm\Wizard;

/**
 * This file is part of the "ods_osm" Extension for TYPO3 CMS.
 * It's based on LocationMapWizard of "tt_address".
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Bobosch\OdsOsm\Traits\SettingsTrait;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds a wizard for location selection via map
 */
class CoordinatepickerWizard extends AbstractNode
{
    use SettingsTrait;

    /**
     * @return array<string, mixed>
     */
    public function render(): array
    {
        $row = $this->data['databaseRow'];
        $paramArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();
        $extConfig = $this->getSettings();

        $nameLongitude = $paramArray['itemFormElName'];

        if (strpos((string) $nameLongitude, '[pi_flexform]') > 0) {
            // it's a call inside a flexform
            $lon = $row['pi_flexform']['data']['sDEF']['lDEF']['lon']['vDEF'] != '' ? htmlspecialchars((string) $row['pi_flexform']['data']['sDEF']['lDEF']['lon']['vDEF']) : '';
            $lat = $row['pi_flexform']['data']['sDEF']['lDEF']['lat']['vDEF'] != '' ? htmlspecialchars((string) $row['pi_flexform']['data']['sDEF']['lDEF']['lat']['vDEF']) : '';
        } else {
            $lat = $row['tx_odsosm_lat'] != '' ? htmlspecialchars((string) $row['tx_odsosm_lat']) : '';
            $lon = $row['tx_odsosm_lon'] != '' ? htmlspecialchars((string) $row['tx_odsosm_lon']) : '';
        }

        $nameLatitude = (string)str_replace('lon', 'lat', $nameLongitude);
        $nameLatitudeActive = str_replace('data', 'control[active]', $nameLatitude);
        $geoCodeUrl = '';
        $geoCodeUrlShort = '';

        if (empty((float)$lat) || empty((float)$lon)) {
            // remove all after first slash in address (top, floor ...)
            $address = preg_replace('/^([^\/]*).*$/', '$1', $row['address'] ?? '') . ' ';
            $address .= $row['city'] ?? '';
            // if we have at least some address part (saves geocoding calls)
            if (trim($address) !== '' && trim($address) !== '0') {
                // base url
                $geoCodeUrlBase = 'https://nominatim.openstreetmap.org/search?q=';
                $geoCodeUrlAddress = $address;
                $geoCodeUrlCityOnly = ($row['city'] ?? '');
                // urlparams for nominatim which are fixed.
                $geoCodeUrlQuery = '&format=json&addressdetails=1&limit=1&polygon_svg=1';
                // replace newlines with spaces; remove multiple spaces
                $geoCodeUrl = trim((string) preg_replace('/\s\s+/', ' ', $geoCodeUrlBase . $geoCodeUrlAddress . $geoCodeUrlQuery));
                $geoCodeUrlShort = trim((string) preg_replace('/\s\s+/', ' ', $geoCodeUrlBase . $geoCodeUrlCityOnly . $geoCodeUrlQuery));
            }
        }

        $resultArray['iconIdentifier'] = 'coordinate-picker-wizard';
        $resultArray['title'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:coordinatepickerWizard');
        $resultArray['linkAttributes']['class'] = 'coordinatepickerWizard ';
        $resultArray['linkAttributes']['data-label-title'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:coordinatepickerWizard.title');
        $resultArray['linkAttributes']['data-label-close'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:coordinatepickerWizard.close');
        $resultArray['linkAttributes']['data-label-import'] = $this->getLanguageService()->sL('LLL:EXT:ods_osm/Resources/Private/Language/locallang_db.xlf:coordinatepickerWizard.import');
        $resultArray['linkAttributes']['data-lat'] = $lat;
        $resultArray['linkAttributes']['data-lon'] = $lon;
        $resultArray['linkAttributes']['data-glat'] = $extConfig['default_lat'];
        $resultArray['linkAttributes']['data-glon'] = $extConfig['default_lon'];
        $resultArray['linkAttributes']['data-zoom'] = $extConfig['default_zoom'];
        $resultArray['linkAttributes']['data-geocodeurl'] = $geoCodeUrl;
        $resultArray['linkAttributes']['data-geocodeurlshort'] = $geoCodeUrlShort;
        $resultArray['linkAttributes']['data-namelat'] = htmlspecialchars($nameLatitude);
        $resultArray['linkAttributes']['data-namelon'] = htmlspecialchars((string) $nameLongitude);
        $resultArray['linkAttributes']['data-namelat-active'] = htmlspecialchars($nameLatitudeActive);
        $resultArray['linkAttributes']['data-tiles'] = htmlspecialchars('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
        $resultArray['linkAttributes']['data-copy'] = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
        $resultArray['stylesheetFiles'][] = 'EXT:ods_osm/Resources/Public/JavaScript/Leaflet/Core/leaflet.css';
        $resultArray['stylesheetFiles'][] = 'EXT:ods_osm/Resources/Public/Css/Backend/leafletBackend.css';

        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->loadJavaScriptModule('@bobosch/ods-osm/esm/leaflet-backend.js');

        return $resultArray;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
