<?php

namespace Bobosch\OdsOsm\Provider;

use Bobosch\OdsOsm\Div;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\StorageRepository;
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
                zoom: true,
				attributionOptions: /** @type {olx.control.AttributionOptions} */ ({
					collapsible: false
				}),
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


    protected function getMarker($item, $table)
    {
        $jsMarker = '';
        $jsElementVar = $table . '_' . $item['uid'];
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $jsElementVarsForPopup = [];

        // Convert item color hex value to rgba() as Openlayers doesn't have an opacity option.
        if (empty($item['color'])) {
            // set default blue, if nothing is given
            $item['color'] = '#0009ff';
        }
        if (strlen($item['color']) == 7) {
            $hex = array( $item['color'][1] . $item['color'][2], $item['color'][3] . $item['color'][4], $item['color'][5] . $item['color'][6] );
            $rgb = array_map('hexdec', $hex);
            $opacity = '0.2';
            $item['rgba'] = 'rgba('.implode(",", $rgb).','.$opacity.')';
        }

        switch ($table) {
            case 'tx_odsosm_track':
                $fileObjects = $fileRepository->findByRelation('tx_odsosm_track', 'file', $item['uid']);
                if ($fileObjects) {
                    $file = $fileObjects[0];
                } else {
                    break;
                }

                $path = PathUtility::getAbsoluteWebPath(
                    GeneralUtility::getFileAbsFileName(Div::RESOURCE_BASE_PATH . 'JavaScript/Leaflet/')
                );
                // Add tracks to layerswitcher
                $this->layers[1][] = [
                    'title' => $item['title'],
                    'table' => $table,
                    'uid' => $item['uid']
                ];

                switch (strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION))) {
                    case 'kml':
                        // include javascript file for KML support
                        $this->scripts['leaflet-plugins'] = ['src' => $path . 'leaflet-plugins/layer/vector/KML.js'];

                        $jsMarker .= 'var ' . $jsElementVar . ' = new L.KML(';
                        $jsMarker .= '"' . $file->getPublicUrl() . '"';
                        $jsMarker .= ");\n";
                        break;
                    case 'gpx':
                        // include javascript file for GPX support
                        $this->scripts['leaflet-gpx'] = ['src' => $path . 'leaflet-gpx/gpx.js'];
                        $options = array(
                            'clickable' => 'false',
                            'polyline_options' => array(
                                'color' => $item['color'],
                            ),
                            'marker_options' => array(
                                'startIconUrl' => $path . 'leaflet-gpx/pin-icon-start.png',
                                'endIconUrl' => $path . 'leaflet-gpx/pin-icon-end.png',
                                'shadowUrl' => $path . 'leaflet-gpx/pin-shadow.png',
                            ),
                        );
                        $jsMarker .= 'var ' . $jsElementVar . ' = new L.GPX("' . $file->getPublicUrl() . '",';

                        $jsMarker .= json_encode($options) . ");\n";
                        $jsMarker .= $this->config['id'] . '.addLayer(' . $jsElementVar . ');' . "\n";
                        break;
                }
                $jsElementVarsForPopup[] = $jsElementVar;
                break;
            case 'tx_odsosm_vector':
                // define style from given color and width
                $jsMarker .= 'var ' . $jsElementVar . '_style = new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: \''.$item['color'].'\',
                        width: '.($item['width'] ?: 1).'
                    }),
                    fill: new ol.style.Fill({
                        color: \''.$item['rgba'].'\',
                        opacity: 1
                    }),
                });';

                $fileObjects = $fileRepository->findByRelation('tx_odsosm_vector', 'file', $item['uid']);
                if ($fileObjects) {
                    $file = $fileObjects[0];
                    $filename = '/' . $file->getPublicUrl();

                    $jsMarker .= 'var ' . $jsElementVar . '_file = new ol.layer.Vector({
                        source: new ol.source.Vector({
                            projection: \'EPSG:3857\',
                            url: \'' . $filename . '\',
                            format: new ol.format.GeoJSON()
                        }),
                        style: ' . $jsElementVar . '_style
                    });' . "\n";

                    $jsMarker .= "overlaygroup.getLayers().push(" . $jsElementVar . "_file);";

                    // Add vector file to layerswitcher
                    $this->layers[1][] = [
                        'overlay' => '1',
                        'title' => $item['title'] . ' (File)',
                        'table' => $table,
                        'uid' => $jsElementVar . '_file'
                    ];
                    $jsElementVarsForPopup[] = $jsElementVar . '_file';
                }

                // add geojson from data field as well
                if ($item['data']) {
                    $jsMarker .= 'const ' . $jsElementVar . '_geojsonObject = '. $item['data'] . ';';

                    $jsMarker .= 'var ' . $jsElementVar . '_data = new ol.layer.Vector({
                        source: new ol.source.Vector({
                            features: new ol.format.GeoJSON({
                                featureProjection:"EPSG:3857"
                            }).readFeatures(' . $jsElementVar . '_geojsonObject)
                        }),
                        style: ' . $jsElementVar . '_style
                    });';

                    $jsMarker .= "overlaygroup.getLayers().push(" . $jsElementVar . "_data);";

                    // Add vector data to layerswitcher
                    $this->layers[1][] = [
                        'title' => $item['title'],
                        'table' => $table,
                        'uid' => $item['uid'] . '_data'
                    ];
                    $jsElementVarsForPopup[] = $jsElementVar . '_data';
                }

                break;
            default:
                $markerOptions = [];
                if (is_array($item['tx_odsosm_marker'])) {
                    $marker = $item['tx_odsosm_marker'];
                    $iconOptions = array(
                        'iconSize' => array((int)$marker['size_x'], (int)$marker['size_y']),
                        'iconAnchor' => array(-(int)$marker['offset_x'], -(int)$marker['offset_y']),
                        'popupAnchor' => array(0, (int)$marker['offset_y'])
                    );
                    if ($marker['type'] == 'html') {
                        $iconOptions['html'] = $marker['icon'];
                        $markerOptions['icon'] = 'icon: new L.divIcon(' . json_encode($iconOptions) . ')';
                    } else {
                        $icon = $GLOBALS['TSFE']->absRefPrefix . $marker['icon']->getPublicUrl();
                        $iconOptions['iconUrl'] = $icon;
                        $markerOptions['icon'] = 'icon: new L.Icon(' . json_encode($iconOptions) . ')';
                    }
                } else {
                    $icon = $this->path_leaflet . 'images/marker-icon.png';
                }
                $jsMarker .= 'var ' . $jsElementVar . ' = new L.Marker([' . $item['latitude'] . ', ' . $item['longitude'] . '], {' . implode(',', $markerOptions) . "});\n";
                // Add group to layer switch
                if ($item['group_title']) {
                    $this->layers[1][] = [
                        'title' => ($marker['type'] == 'html' ? $marker['icon'] : "<img class='marker-icon' style='max-width: 60px;' src='" . $icon . "' />") . ' ' . $item['group_title'],
                        'gid' => $item['group_uid']
                    ];
                    $this->layers[2][$item['group_uid']][] = $jsElementVar;
                } else {
                    $this->layers[2][$this->config['id'] . '_g'][] = $jsElementVar;
                }

                $jsElementVarsForPopup[] = $jsElementVar;
                break;
        }

        foreach ($jsElementVarsForPopup as $jsElementVar) {
            if ($item['popup']) {
                $jsMarker .= $jsElementVar . '.bindPopup(' . json_encode($item['popup']) . ");\n";
                if ($item['initial_popup']) {
                    $jsMarker .= $jsElementVar . ".openPopup();\n";
                }
            }
        }

        return $jsMarker;
    }

}
