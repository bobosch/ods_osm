<?php

namespace Bobosch\OdsOsm\Wizard;

/*
* This file is part of the TYPO3 CMS project.
*
* It is free software; you can redistribute it and/or modify it under
* the terms of the GNU General Public License, either version 2
* of the License, or any later version.
*
* For the full copyright and license information, please read the
* LICENSE.txt file that was distributed with this source code.
*
* The TYPO3 project - inspiring people to share!
*/

use Bobosch\OdsOsm\Div;
use Bobosch\OdsOsm\Provider\Openlayers;
use Doctrine\DBAL\FetchMode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for colorpicker wizard
 */
class Coordinatepicker extends \TYPO3\CMS\Backend\Controller\Wizard\AbstractWizardController
{
    /**
     * Wizard parameters, coming from FormEngine linking to the wizard.
     *
     * @var array
     */
    public $P;

    /**
     * Serialized functions for changing the field...
     * Necessary to call when the value is transferred to the FormEngine since the form might
     * need to do internal processing. Otherwise the value is simply not be saved.
     *
     * @var string
     */
    public $fieldChangeFunc;

    /**
     * @var string
     */
    protected $fieldChangeFuncHash;

    /**
     * Form name (from opener script)
     *
     * @var string
     */
    public $fieldName;

    /**
     * Field name (from opener script)
     *
     * @var string
     */
    public $formName;

    /**
     * ID of element in opener script for which to set color.
     *
     * @var string
     */
    public $md5ID;

    /**
     * Internal: If FALSE, a frameset is rendered, if TRUE the content of the picker script.
     *
     * @var int
     */
    public $showPicker;

    /**
     * Document template object
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * @var string
     */
    public $content;

    /** @var ConnectionPool */
    var $connectionPool = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:ods_osm/Resources/Private/Language/locallang_db.xml');
        $GLOBALS['SOBE'] = $this;

        $this->init();
    }

    /**
     * Initialises the Class
     *
     * @return void
     */
    protected function init()
    {
        // Setting GET vars (used in frameset script):
        $this->P = GeneralUtility::_GP('P');
        // Initialize document object:
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        // Start page:
        $this->content .= $this->doc->startPage($this->getLanguageService()->getLL('coordinatepicker.search_coordinates'));
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request)
    {
        $this->main();

        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);

        // create response object
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response->getBody()->write($this->content);
        return $response;
    }

    /**
     * Main Method, rendering either colorpicker or frameset depending on ->showPicker
     *
     * @return void
     */
    public function main()
    {
        $config = Div::getConfig();
        $config['id'] = 'map';
        $config['layer'] = 1;
        $config['mouse_navigation'] = true;
        $config['show_pan_zoom'] = 1;

        $field = Div::getTableConfig($this->P['table']);

        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $this->connectionPool->getConnectionForTable($this->P['table']);

        switch ($this->P['table']) {
            case 'tt_content':
                $res = $connection->executeQuery(
                    'SELECT ' .
                    'ExtractValue(pi_flexform,\'/T3FlexForms[1]/data[1]/sheet[@index="sDEF"]/language[@index="lDEF"]/field[@index="' . $field['lon'] . '"]/value[@index="vDEF"]\') AS lon, ' .
                    'ExtractValue(pi_flexform,\'/T3FlexForms[1]/data[1]/sheet[@index="sDEF"]/language[@index="lDEF"]/field[@index="' . $field['lat'] . '"]/value[@index="vDEF"]\') AS lat ' .
                    'FROM ' . $this->P['table'] . ' ' .
                    'WHERE uid = ' . intval($this->P['uid'])
                );
                $row = $res->fetch(FetchMode::ASSOCIATIVE);
                $js = 'function setBEcoordinates(lon,lat) {
					' . $this->getJSsetField($this->P, 'lon') . '
					' . $this->getJSsetField($this->P, 'lat', array($field['lon'] => $field['lat'])) . '
					close();
				}';
                break;
            case 'tx_odsosm_vector':
                $res = $connection->executeQuery(
                    'SELECT ' .
                    '(max_lon+min_lon)/2 AS lon, ' .
                    '(max_lat+min_lat)/2 AS lat ' .
                    'FROM ' . $this->P['table'] . ' ' .
                    'WHERE uid = ' . intval($this->P['uid'])
                );
                $row = $res->fetch(FetchMode::ASSOCIATIVE);
                $js = 'function setBEfield(data) {
					' . $this->getJSsetField($this->P, 'data') . '
					close();
				}';
                break;
            default:
                $res = $connection->select(
                    [
                        $field['lon'] . ' AS lon',
                        $field['lat'] . ' AS lat'
                    ],
                    $this->P['table'],
                    ['uid' => intval($this->P['uid'])]
                );
                $row = $res->fetch(FetchMode::ASSOCIATIVE);
                $js = 'function setBEcoordinates(lon,lat) {
					' . $this->getJSsetField($this->P, 'lon') . '
					' . $this->getJSsetField($this->P, 'lat', array($field['lon'] => $field['lat'])) . '
					close();
				}';
                break;
        }

        $row['zoom'] = 15;

        if (floatval($row['lon']) == 0) {
            $row['lon'] = $config['default_lon'];
            $row['lat'] = $config['default_lat'];
            $row['zoom'] = $config['default_zoom'];
        }

        // Library
        $library = GeneralUtility::makeInstance(Openlayers::class);
        $library->init($config);
        $library->doc = $this->doc;
        $library->P = $this->P;

        // Layer
        $connection = $this->connectionPool->getConnectionForTable('tx_odsosm_layer');
        $layers = $connection->executeQuery('SELECT * FROM tx_odsosm_layer WHERE uid IN (' . $config['layer'] . ')');

        $this->doc->JScode .= '
			<script type="text/javascript">
				' . $library->getMapBE($layers, $this->P['params']['mode'], $row['lat'], $row['lon'], $row['zoom'], $this->doc) . '
				' . $js . '
			</script>
		';

        $this->content .= '<div style="position:absolute;width:100%;height:100%;" id="map"></div><script type="text/javascript">map();</script>';
    }

    protected function getJSsetField($P, $valueString, $replace = array())
    {
        return '
		var f = window.opener.document.querySelector(\'[data-formengine-input-name="' . strtr($P['itemName'], $replace) . '"]\');
		f.value=' . $valueString . ';
		if(typeof f.onchange === "function") {
		    f.onchange();
        }
		window.opener.' . strtr($P['fieldChangeFunc']['TBE_EDITOR_fieldChanged'], $replace);
    }


    /**
     * Determines whether submitted field change functions are valid
     * and are coming from the system and not from an external abuse.
     *
     * @return bool Whether the submitted field change functions are valid
     */
    protected function areFieldChangeFunctionsValid()
    {
        return $this->fieldChangeFunc && $this->fieldChangeFuncHash && $this->fieldChangeFuncHash === GeneralUtility::hmac($this->fieldChangeFunc);
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(TYPO3\CMS\Core\Page\PageRenderer::class);
    }
}
