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
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class Openlayers extends BaseProvider
{
    public function getMapCore($backPath = ''): void
    {
        $path = ($backPath ?: PathUtility::getAbsoluteWebPath(
            GeneralUtility::getFileAbsFileName(Div::RESOURCE_BASE_PATH . 'OpenLayers/')
        )
        );
        $pathOl = ($this->config['local_js'] ? $path : 'https://cdn.jsdelivr.net/npm/ol@v8.1.0/');
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile($pathOl . 'ol.css');
        $this->scripts['OpenLayers'] = [
            'src' => $pathOl . 'dist/ol.js',
            'sri' => 'sha512-7BxMviUlJVAJOF4l717SzPknm3Y5nLAm3PPtRdrWlCu4GLaW+RhBxYuOJ1MkVNAcPu+lRWn4gtWx0PAxvTzD0g=='
        ];

        // Do we need the layerswitcher? If so, some extra plugin is required.
        if ($this->config['show_layerswitcher']) {
            $pathContrib = ($this->config['local_js'] ? $path . 'Contrib/ol-layerswitcher/' : 'https://unpkg.com/ol-layerswitcher@4.1.1/dist/');
            $pathCustom = $path . 'Custom/';

            $pageRenderer->addCssFile($pathContrib . 'ol-layerswitcher.css');
            $pageRenderer->addCssFile($pathCustom . 'ol-layerswitcher.css');

            $this->scripts['OpenLayersSwitch'] = [
                'src' => $pathContrib . 'ol-layerswitcher.js',
                'sri' => 'sha512-HhCrrWOoQb5HSpRe1fsk9ugZQEOokbJsLioPuUhfXlr5ccRTZVg3UpnfRsTJzrdKLejmx7uvY62n2fp5qLdYQg=='
            ];
        }
    }

    public function getMapMain(): string
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
            title: '" . LocalizationUtility::translate('base_layer', 'OdsOsm') . "',
            layers: [
                new ol.layer.Tile({
                    type: 'base',
                    source: new ol.source.OSM(),
                })
            ]
        });

        overlaygroup = new ol.layer.Group({
            name: 'overlaygroup',
            title: '" . LocalizationUtility::translate('overlays', 'OdsOsm') . "',
            layers: []
        });

        const styleCache = {};
        clusters = new ol.layer.Vector({
            name: 'clusters',
            title: '" . LocalizationUtility::translate('openlayers.clusterLayer', 'OdsOsm') . "',
            source: new ol.source.Cluster({
                distance: " . (int)$this->config['cluster_radius']  . ",
                minDistance: 10,
                source: new ol.source.Vector({
                    name: 'source',
                    features: [],
                })
            }),
            style: function (feature) {
                const size = feature.get('features').length;
                if (size > 1) {
                    style = new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 20,
                            stroke: new ol.style.Stroke({
                                color: '#fff',
                            }),
                            fill: new ol.style.Fill({
                                color: '#3399CC',
                            }),
                        }),
                        text: new ol.style.Text({
                            text: size.toString(),
                            fill: new ol.style.Fill({
                                color: '#fff',
                            }),
                        }),
                    });
                } else {
                    style = feature.get('features')[0].values_.style;
                }
                return style;
            },
        });

        layers = [
            baselayergroup,
            overlaygroup
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
    public function getMapCenter($lat, $lon, $zoom): string
    {
        return '
			view.setCenter(ol.proj.transform([' . $lon . ', ' . $lat . '], \'EPSG:4326\', \'EPSG:3857\'));
			view.setZoom(' . $zoom . ');
		';
    }

    protected function getLayer($layer, $i, $backPath = ''): string
    {
        if (empty($layer['subdomains'])) {
            $layer['subdomains'] = 'abc';
        }

        $layer['subdomains'] = substr((string) $layer['subdomains'], 0, 1) . '-' . substr((string) $layer['subdomains'], -1, 1);
        $layer['tile_url'] = strtr($this->getTileUrl($layer), ['{s}' => '{' . $layer['subdomains'] . '}']);

        if ($layer['overlay'] == 1) {
            return $this->config['id'] . "_" . $i . "_overlayLayer =
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
        }

        return $this->config['id'] . "_" . $i . "_baselayergroup =
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

    /**
     * Get the layer switcher
     *
     * @return string The JavaScript to add the layerswitcher
     */
    protected function getLayerSwitcher(): string
    {
        return '
         var layerSwitcher = new ol.control.LayerSwitcher({
            activationMode: \'' . ($this->config['layerswitcher_activationMode'] == '1' ? 'click' : 'mouseover') . '\',
            startActive: ' . ($this->config['show_layerswitcher'] == '2' ? 'true' : 'false') . ',
            tipLabel: \'' . LocalizationUtility::translate('openlayers.showLayerList', 'OdsOsm') . '\',
            collapseTipLabel: \'' . LocalizationUtility::translate('openlayers.hideLayerList', 'OdsOsm') . '\',
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
    protected function getFullScreen(): string
    {
        return '
         var fullScreen = new ol.control.FullScreen();
          ' . $this->config['id'] . '.addControl(fullScreen);
        ';
    }

    protected function getMarkers(array $markers): string
    {
        $jsMarker = parent::getMarkers($markers);

        // open popup? If yes, with click or hover?
        $eventMethod = match ($this->config['show_popups']) {
            '1' => 'singleclick',
            '2' => 'pointermove',
            default => false,
        };

        if ($eventMethod !== false) {
            $jsMarker .= "
            " . $this->config['id'] . ".on('" . $eventMethod . "', function (event) {
                    var feature = " . $this->config['id'] . ".forEachFeatureAtPixel(event.pixel, function (feat, layer) {
                        return feat;
                    });
                    var layer = " . $this->config['id'] . ".forEachFeatureAtPixel(event.pixel, function (feat, layer) {
                        return layer;
                    });

                    if (feature === undefined) {
                        return;
                    }
                    if (feature.get('features') === undefined) {
                        var coordinate = event.coordinate;
                        if (feature.get('desc') !== undefined) {
                            content.innerHTML = feature.get('desc');
                        } else {
                            // this might be some geoJSON data with properties set

                            var osm_popup = layer.get('popup');
                            var props = feature.values_,
                            ll = Object.keys(props),
                            attribute, value = '';

                            var osm_filter = layer.get('properties').split(',').map(item=>item.trim());

                            osm_filter.forEach((osm_prop) => {
                                if (typeof feature.get(osm_prop) !== 'undefined') {
                                    value += '<dt>' + osm_prop + '</dt> <dd>' + feature.get(osm_prop) + '</dd>';
                                }
                            });
                            content.innerHTML = osm_popup + '<dl>' + value + '</dl>';
                        }
                        popup.setPosition(coordinate);
                    } else if (feature.get('features').length === 1) {
                        var singleFeature = feature.get('features')[0];
                        if (feature && singleFeature.get('type') == 'Point') {
                            var coordinate = event.coordinate;

                            content.innerHTML = singleFeature.get('desc');
                            popup.setPosition(coordinate);
                        }
                    } else if (feature && feature.get('type') == 'Point') {

                        var coordinate = event.coordinate;

                        content.innerHTML = feature.get('desc');
                        popup.setPosition(coordinate);
                    } else {
                        if (feature.get('features').length > 0) {
                            const clusterMembers = feature.get('features');
                            if (clusterMembers.length > 1) {
                                // Calculate the extent of the cluster members.
                                const extent = new ol.extent.createEmpty();
                                clusterMembers.forEach((feature) =>
                                    ol.extent.extend(extent, feature.getGeometry().getExtent())
                                );
                                const view = " . $this->config['id'] . ".getView();
                                const resolution = " . $this->config['id'] . ".getView().getResolution();
                                if (
                                    view.getZoom() === view.getMaxZoom() ||
                                    (ol.extent.getWidth(extent) < resolution && ol.extent.getHeight(extent) < resolution)
                                ) {
                                    // Show an expanded view of the cluster members.
                                    clickFeature = features[0];
                                    clickResolution = resolution;
                                    clusterCircles.setStyle(clusterCircleStyle);
                                } else {
                                    // Zoom to the extent of the cluster members.
                                    view.fit(extent, {duration: 600, padding: [100, 100, 100, 100]});
                                }
                            }
                        }
                        popup.setPosition(undefined);
                    }
                });
            ";
        }

        // grouped marker layer
        foreach ($this->layers[2] as $group_uid => $group) {

            $jsMarker .= $group['layer'];
            $jsMarkerFeatureBatch = [];
            foreach ($group['jsMarkerFeatures'] as $id => $jsMarkerFeature) {
                $jsMarker .= 'var ' . $group_uid . $id . ' = ' . $jsMarkerFeature;
                $jsMarkerFeatureBatch[] = $group_uid . $id;
            }

            if ($this->config['cluster']) {
                $jsMarker .= 'clusters.getSource().getSource().addFeatures([' . implode(',', $jsMarkerFeatureBatch) . ']);' . "\n";
            } else {
                $jsMarker .= $group_uid . '.getSource().addFeatures([' . implode(',', $jsMarkerFeatureBatch) . ']);' . "\n";
                $jsMarker .= 'overlaygroup.getLayers().push(' . $group_uid . ');' . "\n";
            }
        }

        if ($this->config['cluster']) {
            // add cluster layer in overlaygroup
            $jsMarker .= 'overlaygroup.getLayers().push(clusters);' . "\n";
        }

        return $jsMarker;
    }

    protected function getMarker(array $item, string $table): string
    {
        $jsMarker = '';
        $jsElementVar = $table . '_' . $item['uid'];
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

        // Convert item color hex value to rgba() as Openlayers doesn't have an opacity option.
        if (empty($item['color'])) {
            // set default blue, if nothing is given
            $item['color'] = '#0009ff';
        }

        if (strlen((string) $item['color']) == 7) {
            $hex = [ $item['color'][1] . $item['color'][2], $item['color'][3] . $item['color'][4], $item['color'][5] . $item['color'][6] ];
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
                                url: \'' .  $file->getPublicUrl() . '\',
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
                                url: \'' .  $file->getPublicUrl() . '\',
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
                    $filename =  $file->getPublicUrl();

                    $properties = [
                        'popup' => $item['popup'] ?? '',
                        'properties' => $item['properties'],
                    ];

                    $jsMarker .= 'var ' . $jsElementVar . '_file_properties = ' . json_encode($properties) . ';';
                    $jsMarker .= 'var ' . $jsElementVar . '_file = new ol.layer.Vector({
                        title: \'' .$item['title'] . ' ('. LocalizationUtility::translate('file', 'OdsOsm') .')\',
                        source: new ol.source.Vector({
                            projection: \'EPSG:3857\',
                            url: \'' . $filename . '\',
                            format: new ol.format.GeoJSON()
                        }),
                        style: ' . $jsElementVar . '_style,
                        properties: ' . $jsElementVar . '_file_properties,
                    });' . "\n";

                    $jsMarker .= $jsElementVar . "_file.getSource().setProperties(" . $jsElementVar . "_file_properties);";
                    $jsMarker .= "overlaygroup.getLayers().push(" . $jsElementVar . "_file);";
                }

                // add geojson from data field as well
                if ($item['data']) {
                    $properties = [
                        'popup' => $item['popup'] ? $item['popup'] . '<br />' : '',
                        'properties' => $item['properties'],
                    ];

                    $jsMarker .= 'const ' . $jsElementVar . '_geojsonObject = '. $item['data'] . ';';

                    $jsMarker .= 'var ' . $jsElementVar . '_data_properties = ' . json_encode($properties) . ';';
                    $jsMarker .= 'var ' . $jsElementVar . '_data = new ol.layer.Vector({
                        title: \'' .$item['title'] . '\',
                        source: new ol.source.Vector({
                            features: new ol.format.GeoJSON({
                                featureProjection:"EPSG:3857"
                            }).readFeatures(' . $jsElementVar . '_geojsonObject)
                        }),
                        style: ' . $jsElementVar . '_style
                    });';

                    $jsMarker .= $jsElementVar . "_data.setProperties(" . $jsElementVar . "_data_properties);";
                    $jsMarker .= "overlaygroup.getLayers().push(" . $jsElementVar . "_data);";
                }

                break;
            default:
                $markerOptions = [];
                $icon = null;
                if ($item['tx_odsosm_marker'] ?? false) {
                    $marker = $item['tx_odsosm_marker'];
                    if ($marker['type'] == 'html') {
                        $markerOptions['icon'] = 'icon: new L.divIcon(' . json_encode($marker['icon']) . ')';
                    } else {
                        $icon =  $marker['icon']->getPublicUrl();
                        $markerOptions['icon'] = 'icon: new L.Icon(' . json_encode($icon) . ')';
                    }
                } else {
                    $icon = \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:ods_osm/Resources/Public/Icons/marker-icon.png');
                    $marker['size_x'] = 25;
                    $marker['size_y'] = 41;
                }

                $markerStyle = "const " . $jsElementVar . "_style = new ol.style.Style({
                    image: new ol.style.Icon({
                        anchor: [0.5, 46],
                        anchorXUnits: 'fraction',
                        anchorYUnits: 'pixels',
                        src: '" . $icon ."',
                        width: " . (int)$marker['size_x'] . ",
                        height: " . (int)$marker['size_y'] . "
                    }),
                });";

                // It's a group of markers
                if ($item['group_title'] ?? false) {
                    $jsMarker .= $markerStyle;

                    $group_title = ($marker['type'] == 'html' ? $icon : "<img class='marker-icon' src='" . $icon . "' />") . ' ' . $item['group_title'];
                    $jsMarkerGroup = "
                    var " . $item['group_uid'] . " = new ol.layer.Vector({
                        title: \"" . $group_title . "\",
                        source: new ol.source.Vector({
                            features: []
                        }),
                        style: " . $jsElementVar . "_style
                    });";

                    $this->layers[2][$item['group_uid']]['layer'] = $jsMarkerGroup;

                    $popupJsCode = "
                    function (layer) {
                        var osm_popup = '" . ($item['popup'] ?? '') . "';

                        var feature = layer.feature,
                        props = feature.properties,
                        ll = Object.keys(props),
                        attribute, value = '';

                        for (attribute in props) {
                            value += '<strong>' + attribute + '</strong>: ' + props[attribute] + '<br />';
                        }
                        return osm_popup + value;
                    }
                ";

                    $jsMarkerFeature = "
                    new ol.Feature({
                        geometry: new ol.geom.Point(ol.proj.fromLonLat([" . $item['longitude'] . ", " . $item['latitude'] . "])),
                        type: 'Point',
                        desc: " . json_encode($item['popup']) . ",
                        style: " . $jsElementVar . "_style
                    });";

                    $this->layers[2][$item['group_uid']]['jsMarkerFeatures'][] = $jsMarkerFeature;
                } else {
                    $jsMarker .= $markerStyle;
                    $jsMarker .= "var " . $jsElementVar . " = new ol.layer.Vector({
                        title: '<img src=\"" .$icon . "\" class=\"marker-icon\" /> " . Openlayers::escapeEntities($item['group_title'] ?? $item['name']) . "',
                        source: new ol.source.Vector({
                            features: [
                                 new ol.Feature({
                                    geometry: new ol.geom.Point(ol.proj.fromLonLat([" . $item['longitude'] . ", " . $item['latitude'] . "])),
                                    type: 'Point',
                                    desc: " . json_encode($item['popup']) . "
                                })
                                ]
                            }),
                            style: " . $jsElementVar . "_style
                        });";

                    $jsMarker .= 'overlaygroup.getLayers().push(' . $jsElementVar . ');' . "\n";
                }

                break;
            }

        return $jsMarker;
    }
    
    /**
     * Replaces ' and " charactes with HTML entities.
     *
     * @param string $text
     *              Text to escape.
     * @return string
     *              Text with HTML entities for ' and " characters.
     */
    private static function escapeEntities(string $text) : string
    {
        $escaped = str_replace("'", "&apos;", $text);
        $escaped = str_replace('"', "&quot;", $escaped);
        return $escaped;
    }
}
