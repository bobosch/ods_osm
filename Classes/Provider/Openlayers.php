<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Alexander Bigga <alexander@bigga.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
        $pathOl = ($this->config['local_js'] ? $path : 'https://cdn.jsdelivr.net/npm/ol@v7.1.0/');
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile($pathOl . 'ol.css');
        $this->scripts['OpenLayers'] = [
            'src' => $pathOl . 'dist/ol.js',
            'sri' => 'sha512-zcRdjTuLRJPIXiyXpHwGxbw5/meqPWTVO8Bko9XL6qmwSaPiFe9R1/xBmba4RjWzFzT8e+dNqIWDCa6gdEgajw=='
        ];

        // Do we need the layerswitcher? If so, some extra plugin is required.
        if ($this->config['show_layerswitcher']) {
            $pathContrib = ($this->config['local_js'] ? $path . 'Contrib/ol-layerswitcher/' : 'https://unpkg.com/ol-layerswitcher@4.0.0/dist/');
            $pathCustom = $path . 'Custom/';

            $pageRenderer->addCssFile($pathContrib . 'ol-layerswitcher.css');
            $pageRenderer->addCssFile($pathCustom . 'ol-layerswitcher.css');

            $this->scripts['OpenLayersSwitch'] = [
                'src' => $pathContrib . 'ol-layerswitcher.js',
                'sri' => 'sha512-vTZfK/QA+2mdjJU/AYvJJqZipymv81D7WuEF4n6gr9udJnfPtLmXnUBfGsRaWbSj2ERSSBzPRvVL340ePCIESQ=='
            ];
        }
    }

    public function getMapMain()
    {
        $controls = [
            'new ol.control.Attribution()',
            'new ol.control.Zoom()',
            'new ol.control.Rotate()'
        ];
        if ($this->config['mouse_position']) {
            $controls[] = "new ol.control.MousePosition({
                coordinateFormat: ol.coordinate.createStringXY(2),
                projection: 'EPSG:4326',
                className: 'ods-osm-mouse-position',
                target: document.getElementById('mouse-position-" . $this->config['id'] . "')
            })";
        }
        if ($this->config['show_scalebar']) {
            $controls[] = "new ol.control.ScaleLine()";
        }

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
            controls:[" . implode(',', $controls) . "],
			layers: layers,
			view: view
        });

        // Popup showing the position the user clicked
        var container = document.getElementById('popup');
        var closer = document.getElementById('popup-closer');
        var content = document.getElementById('popup-content');
        var popup = new ol.Overlay({
            element: container,
            autoPan: true,
            autoPanAnimation: {
                duration: 100
            }
        });
        " . $this->config['id'] . ".addOverlay(popup);
        closer.onclick = function () {
            popup.setPosition(undefined);
            content.innerHTML = '';
            closer.blur();
            return false;
        };

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

    /**
     * Get the fullscreen button
     *
     * @return string The JavaScript to add the fullscreen button
     */
    protected function getFullScreen()
    {
        return '
         var fullScreen = new ol.control.FullScreen();
          ' . $this->config['id'] . '.addControl(fullScreen);
        ';
    }

    protected function getMarkers($markers)
    {
        $jsMarker = parent::getMarkers($markers);


        // open popup? If yes, with click or hover?
        switch ($this->config['show_popups']) {
            case 1:
                $eventMethod = 'singleclick';
                break;
            case 2:
                $eventMethod = 'pointermove';
                break;
            default:
                $eventMethod = false;
        }

        if ($eventMethod !== false) {
            $jsMarker .= "
            " . $this->config['id'] . ".on('" . $eventMethod . "', function (event) {
                    var feature = " . $this->config['id'] . ".forEachFeatureAtPixel(event.pixel, function (feat, layer) {
                        return feat;
                    });

                    if (feature && feature.get('type') == 'Point') {
                        var coordinate = event.coordinate;    // default projection is EPSG:3857 you may want to use ol.proj.transform

                        content.innerHTML = feature.get('desc');
                        popup.setPosition(coordinate);
                    }
                    else {
                        popup.setPosition(undefined);
                    }
                });
            ";
        }

        return $jsMarker;
    }

    protected function getMarker($item, $table)
    {
        $jsMarker = '';
        $jsElementVar = $table . '_' . $item['uid'];
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

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

                // Add tracks to layerswitcher
                $this->layers[1][] = [
                    'title' => $item['title'],
                    'table' => $table,
                    'uid' => $item['uid']
                ];

            // define style from given color and width
            $jsMarker .= 'var ' . $jsElementVar . '_style = new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: \''.$item['color'].'\',
                    width: '.($item['width'] ?: 1).'
                }),
                fill: new ol.style.Fill({
                    color: \''.$item['rgba'].'\'
                }),
            });';

            switch (strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION))) {
                    case 'kml':
                        $jsMarker .= 'var ' . $jsElementVar . '_gpx = new ol.layer.Vector({
                            title: \'' .$item['title'] . '\',
                            source: new ol.source.Vector({
                                projection: \'EPSG:3857\',
                                url: \'' . $this->getAbsRefPrefix() . $file->getPublicUrl() . '\',
                                format: new ol.format.KML()
                            }),
                            style: ' . $jsElementVar . '_style
                        });' . "\n";

                        $jsMarker .= "overlaygroup.getLayers().push(" . $jsElementVar . "_gpx);";
                        break;
                    case 'gpx':
                        $jsMarker .= 'var ' . $jsElementVar . '_gpx = new ol.layer.Vector({
                            title: \'' .$item['title'] . '\',
                            source: new ol.source.Vector({
                                projection: \'EPSG:3857\',
                                url: \'' . $this->getAbsRefPrefix() . $file->getPublicUrl() . '\',
                                format: new ol.format.GPX()
                            }),
                            style: ' . $jsElementVar . '_style
                        });' . "\n";

                        $jsMarker .= "overlaygroup.getLayers().push(" . $jsElementVar . "_gpx);";
                        break;
                }
                break;
            case 'tx_odsosm_vector':
                $fileObjects = $fileRepository->findByRelation('tx_odsosm_vector', 'file', $item['uid']);

                // define style from given color and width
                $jsMarker .= 'var ' . $jsElementVar . '_style = new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: \''.$item['color'].'\',
                        width: '.($item['width'] ?: 1).'
                    }),
                    fill: new ol.style.Fill({
                        color: \''.$item['rgba'].'\'
                    }),
                });' . "\n";

                if ($fileObjects) {
                    $file = $fileObjects[0];
                    $filename = $this->getAbsRefPrefix() . $file->getPublicUrl();


                    $jsMarker .= 'var ' . $jsElementVar . '_file = new ol.layer.Vector({
                        title: \'' .$item['title'] . ' ('. LocalizationUtility::translate('file', 'ods_osm') .')\',
                        source: new ol.source.Vector({
                            projection: \'EPSG:3857\',
                            url: \'' . $filename . '\',
                            format: new ol.format.GeoJSON()
                        }),
                        style: ' . $jsElementVar . '_style
                    });' . "\n";

                    $jsMarker .= "overlaygroup.getLayers().push(" . $jsElementVar . "_file);";
                }

                // add geojson from data field as well
                if ($item['data']) {
                    $jsMarker .= 'const ' . $jsElementVar . '_geojsonObject = '. $item['data'] . ';';

                    $jsMarker .= 'var ' . $jsElementVar . '_data = new ol.layer.Vector({
                        title: \'' .$item['title'] . '\',
                        source: new ol.source.Vector({
                            features: new ol.format.GeoJSON({
                                featureProjection:"EPSG:3857"
                            }).readFeatures(' . $jsElementVar . '_geojsonObject)
                        }),
                        style: ' . $jsElementVar . '_style
                    });';

                    $jsMarker .= "overlaygroup.getLayers().push(" . $jsElementVar . "_data);";
                }

                break;
            default:
                $markerOptions = [];
                if ($item['tx_odsosm_marker'] ?? false) {
                    $marker = $item['tx_odsosm_marker'];
                    if ($marker['type'] == 'html') {
                        $markerOptions['icon'] = 'icon: new L.divIcon(' . json_encode($marker['icon']) . ')';
                    } else {
                        $icon = $this->getAbsRefPrefix() . $marker['icon']->getPublicUrl();
                        $markerOptions['icon'] = 'icon: new L.Icon(' . json_encode($icon) . ')';
                    }
                } else {
                    $icon = '/typo3conf/ext/ods_osm/Resources/Public/Icons/marker-icon.png';
                }

                if (!empty($icon)) {
                    $jsMarker .= "
                    const " . $jsElementVar . "_style = new ol.style.Style({
                        image: new ol.style.Icon({
                          anchor: [0.5, 46],
                          anchorXUnits: 'fraction',
                          anchorYUnits: 'pixels',
                          src: '" . $icon ."',
                          width: " . (int)$marker['size_x'] . ",
                          height: " . (int)$marker['size_y'] . "
                        }),
                    });
                    ";
                }

                $jsMarker .= "var " . $jsElementVar . " = new ol.layer.Vector({
                    title: '<img src=\"" .$icon . "\" class=\"marker-icon\" /> " . ($item['group_title'] ?? $item['name']) . "',
                    source: new ol.source.Vector({
                        features: [
                            new ol.Feature({
                                geometry: new ol.geom.Point(ol.proj.fromLonLat([" . $item['longitude'] . ", " . $item['latitude'] . "])),
                                type: 'Point',
                                desc: " . json_encode($item['popup']) . ",
                            })
                        ]
                    }),
                    style: " . $jsElementVar . "_style
                });
                ";

                $jsMarker .= "overlaygroup.getLayers().push(" . $jsElementVar . ");\n";

                $jsMarker .= "

                        var containery = document.getElementById('popup');

                ";
                break;
        }

        return $jsMarker;
    }

}
