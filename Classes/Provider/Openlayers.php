<?php

namespace Bobosch\OdsOsm\Provider;

use Bobosch\OdsOsm\Div;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class Openlayers extends BaseProvider
{
    protected $layers = [
        0 => [], // Base
        1 => [], // Overlay
        2 => [], // Marker
    ];

    public function getMapCore($backpath = '')
    {
        $path = ($backpath ? $backpath :
            PathUtility::getAbsoluteWebPath(
                GeneralUtility::getFileAbsFileName(Div::RESOURCE_BASE_PATH . 'OpenLayers/')
            )
        );
        $pathOl = ($this->config['local_js'] ? $path : 'https://cdn.jsdelivr.net/gh/openlayers/openlayers.github.io@master/en/v6.15.1/');
        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile($pathOl . 'css/ol.css');
        $this->scripts['OpenLayers'] = [
            'src' => $pathOl . 'build/ol.js',
            'sri' => 'sha512-9jwbYv3+RCZJsgvMaf9IqM2a2xhFvwDdY+5MUu0JNl6yL00jn98+nsPHt6QzPFVyU+EIUgNk6rc72TPZNf3yag=='
        ];

        // Do we need the layerswitcher? If so, some extra plugin is required.
        if ($this->config['show_layerswitcher']) {
            $pathContrib = ($this->config['local_js'] ? $path . 'Contrib/ol-layerswitcher/' : 'https://unpkg.com/ol-layerswitcher@3.8.3/dist/');
            $pathCustom = $path . 'Custom/';

            $pageRenderer->addCssFile($pathContrib . 'ol-layerswitcher.css');
            $pageRenderer->addCssFile($pathCustom . 'ol-layerswitcher.css');

            $this->scripts['OpenLayersSwitch'] = [
                'src' => $pathContrib . 'ol-layerswitcher.js',
                'sri' => 'sha512-+cZhYSrGlO4JafMR5fEFkF+6pXr9fwMaicniLZRH76RtnJXc/+WkFpZu/9Av0rg2xDVr84M15XMA6tet1VaMrg=='
            ];
        }
    }

    public function getMapMain()
    {
        return "
		view = new ol.View({
			center: [0, 0],
			zoom: 1
		});

        baselayergroup = new ol.layer.Group({
            name: 'baselayergroup',
            title: '" . LocalizationUtility::translate('base_layer', 'ods_osm') . "',
            layers: [
                new ol.layer.Tile({
                    type: 'base',
                    source: new ol.source.OSM(),
                })
            ]
        });

        overlaygroup = new ol.layer.Group({
            name: 'overlaygroup',
            title: '" . LocalizationUtility::translate('overlays', 'ods_osm') . "',
            layers: []
        });

        layers = [
            baselayergroup,
            overlaygroup,
        ];

		var " . $this->config['id'] . " = new ol.Map({
			target: '" . $this->config['id'] . "',
			controls: ol.control.defaults({
				attributionOptions: /** @type {olx.control.AttributionOptions} */ ({
					collapsible: false
				})
			}),
			layers: layers,
			view: view
        });
        ";
    }

    /**
     * The center and zoom level of the map
     *
     * @param float $lat: latitude
     * @param float $lon: longitude
     * @param int $zoom: zoom level
     *
     * @return string The JavaScript to set the center and zoom level
     */
    public function getMapCenter($lat, $lon, $zoom)
    {
        return '
			view.setCenter(ol.proj.transform([' . $lon . ', ' . $lat . '], \'EPSG:4326\', \'EPSG:3857\'));
			view.setZoom(' . $zoom . ');
		';
    }

    protected function getLayer($layer, $i, $backpath = '')
    {
        if (empty($layer['subdomains'])) {
            $layer['subdomains'] = 'abc';
        }
        $layer['subdomains'] = substr($layer['subdomains'], 0, 1) . '-' . substr($layer['subdomains'], -1, 1);
        $layer['tile_url'] = strtr($this->getTileUrl($layer), array('{s}' => '{' . $layer['subdomains'] . '}'));

        if ($layer['overlay'] == 1) {

            $jsLayer = $this->config['id'] . "_" . $i . "_overlayLayer =
                    new ol.layer.Tile({
                        visible: " . ($layer['visible'] == true ? 'true' : 'false') . ",
                        opacity: 0.99,
                        title: '" . $layer['title'] . "',
                        source: new ol.source.OSM({
                            url: '" . $layer['tile_url'] . "',
                            attributions: [
                                '" . $layer['attribution']. "'
                            ]
                        })
                    });
                overlaygroup.getLayers().push(" . $this->config['id'] . "_" . $i . "_overlayLayer);
            ";
        } else {
            $jsLayer = $this->config['id'] . "_" . $i . "_baselayergroup =
                    new ol.layer.Tile({
                        type: 'base',
                        combine: 'true',
                        visible: " . ($i == 0 ? 'true' : 'false') . ",
                        title: '" . $layer['title'] . "',
                        source: new ol.source.OSM({
                            url: '" . $layer['tile_url'] . "',
                            attributions: [
                                '" . $layer['attribution']. "'
                            ]
                        })
                    });
                baselayergroup.getLayers().push(" . $this->config['id'] . "_" . $i . "_baselayergroup);
        ";
        }

        return $jsLayer;
    }

    /**
     * Get the layer switcher
     *
     * @return string The JavaScript to add the layerswitcher
     */
    protected function getLayerSwitcher()
    {
        return '
         var layerSwitcher = new ol.control.LayerSwitcher({
            activationMode: \'' . ($this->config['layerswitcher_activationMode'] == '1' ? 'click' : 'mouseover') . '\',
            startActive: ' . ($this->config['show_layerswitcher'] == '2' ? 'true' : 'false') . ',
            tipLabel: \'' . LocalizationUtility::translate('openlayers.showLayerList', 'ods_osm') . '\',
            collapseTipLabel: \'' . LocalizationUtility::translate('openlayers.hideLayerList', 'ods_osm') . '\',
            groupSelectStyle: \'children\',
            reverse: false
          });
          ' . $this->config['id'] . '.addControl(layerSwitcher);
        ';
    }
}
