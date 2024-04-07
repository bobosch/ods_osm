<?php

namespace Bobosch\OdsOsm\Provider;

use Bobosch\OdsOsm\Div;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class Leaflet extends BaseProvider
{
    protected $path_res;
    protected $path_leaflet;

    public function getMapCore($backpath = ''): void
    {
        $this->path_res = ($backpath ? $backpath :
            PathUtility::getAbsoluteWebPath(
                GeneralUtility::getFileAbsFileName(Div::RESOURCE_BASE_PATH . 'JavaScript/Leaflet/')
            )
        );
        $this->path_leaflet = ($this->config['local_js'] ? $this->path_res . 'Core/' : 'https://unpkg.com/leaflet@1.9.4/dist/');
        $this->pageRenderer->addCssFile($this->path_leaflet . 'leaflet.css');
        $this->scripts['leaflet'] = [
            'src' => $this->path_leaflet . 'leaflet.js',
            'sri' => 'sha384-cxOPjt7s7Iz04uaHJceBmS+qpjv2JkIHNVcuOrM+YHwZOmJGBXI00mdUXEq65HTH'
        ];
    }

    public function getMapMain()
    {
        $controls = [];
        if ($this->config['show_scalebar']) {
            $controls['scalebar'] = 'new L.control.scale()';
        }

        $vars = '';
        foreach ($controls as $var => $obj) {
            $vars .= "\n\t\t\t" . $this->config['id'] . '.addControl(' . $obj . ");";
        }

        $jsMain =
            $this->config['id'] . "=new L.Map('" . $this->config['id'] . "',
                {scrollWheelZoom: " .((!isset($this->config['enable_scrollwheelzoom']) || $this->config['enable_scrollwheelzoom'] == '1') ? 'true' : 'false'). ",
                dragging: " .((!isset($this->config['enable_dragging']) || $this->config['enable_dragging'] == '1') ? 'true' : 'false'). "});
			L.Icon.Default.imagePath='" . $this->path_leaflet . "images/';"
            . $vars;
        if ($this->config['cluster']) {
            $this->pageRenderer->addCssFile($this->path_res . 'leaflet-markercluster/MarkerCluster.css');
            $this->pageRenderer->addCssFile($this->path_res . 'leaflet-markercluster/MarkerCluster.Default.css');
            $this->scripts['leaflet-markercluster'] = [
                'src' => $this->path_res . 'leaflet-markercluster/leaflet.markercluster.js'
            ];
        }

        return $jsMain;
    }

    protected function getLayer($layer, $i, $backpath = '')
    {
        if ($layer['tile_url']) {
            $options = [];
            if ($layer['min_zoom']) {
                $options['minZoom'] = $layer['min_zoom'];
            }
            if ($layer['max_zoom']) {
                $options['maxZoom'] = $layer['max_zoom'];
            }
            if ($layer['subdomains']) {
                $options['subdomains'] = $layer['subdomains'];
            }
            if ($layer['attribution']) {
                $options['attribution'] = $layer['attribution'];
            }

            $jsLayer = 'new L.TileLayer(\'' . $this->getTileUrl($layer) . '\',' . json_encode($options) . ');';
        }

        $jsLayer = "\n\t\t\tvar layer_" . $layer['uid'] . ' = ' . $jsLayer;

        // only show first base layer on the map
        if (($layer['overlay'] == 1 && $layer['visible']) || ($i == 0 && $layer['overlay'] == 0)) {
            $jsLayer .= "\n\t\t\t" . $this->config['id'] . '.addLayer(layer_' . $layer['uid'] . ');';
        }

        return $jsLayer;
    }

    protected function getLayerSwitcher()
    {
        $base = [];
        if (is_array($this->layers[0] ?? null) && count($this->layers[0]) > 1) {
            foreach ($this->layers[0] as $layer) {
                $base[] = '"' . $layer['title'] . '":' . ($layer['table'] ?? 'layer') . '_' . $layer['uid'];
            }
        }
        $overlay = [];
        if (is_array($this->layers[1] ?? null)) {
            foreach ($this->layers[1] as $layer) {
                if (!empty($layer['gid'])) {
                    $overlay[] = '"' . $layer['title'] . '":' . $layer['gid'];
                } else {
                    $overlay[] = '"' . $layer['title'] . '":' . ($layer['table'] ?? 'layer')  . '_' . $layer['uid'];
                }
            }
        }

        if (empty($base) && empty($overlay)) {
            return '';
        }
        return 'var layersControl=new L.Control.Layers({' . implode(',', $base) . '},{' . implode(',', $overlay) . '}' . ($this->config['show_layerswitcher'] == 2 ? ',{collapsed:false}' : '') . ');
			' . $this->config['id'] . '.addControl(layersControl);';
    }

    /**
     * Get the fullscreen button
     *
     * @return string The JavaScript to add the fullscreen button
     */
    public function getFullScreen()
    {
        // load leaflet.fullscreen plugin
        $this->scripts['leaflet-fullscreen'] = [
            'src' => $this->path_res . 'leaflet-fullscreen/Control.FullScreen.js',
            'sri' => 'sha384-TqFtkYBnYsrP2JCfIv/oLQxS9L6xpaIV9xnaI2UGMK25cJsTtQXZIU6WGQ7daT0Z'
        ];
        $this->pageRenderer->addCssFile($this->path_res . 'leaflet-fullscreen/Control.FullScreen.css');

        return "L.control.fullscreen({
            position: 'topleft',
            title: 'Full Screen',
            titleCancel: 'Exit fullscreen mode',
            forceSeparateButton: true,
            forcePseudoFullscreen: true, // force use of pseudo full screen even if full screen API is available, default false
            fullscreenElement: false // Dom element to render in full screen, false by default, fallback to map._container
          }).addTo(" . $this->config['id'] . ");";
    }

    public function getMapCenter($lat, $lon, $zoom)
    {
        $return = 'var center = new L.LatLng(' . json_encode($lat) . ',' . json_encode($lon) . ');' . $this->config['id'] . '.setView(center,' . $zoom . ');';
        if ($this->config['position']) {
            $return .= $this->config['id'] . '.locate();' . $this->config['id'] . '.on("locationfound",function(e){var radius=e.accuracy/2;L.circle(e.latlng,radius).addTo(' . $this->config['id'] . ');});';
        }

        return $return;
    }

    protected function getMarkers($markers)
    {
        $jsMarker = parent::getMarkers($markers);

        foreach ($this->layers[2] as $group_uid => $group) {
            if ($this->config['cluster']) {
                $jsMarker .= 'var ' . $group_uid . ' = new L.MarkerClusterGroup({maxClusterRadius:' . (int)$this->config['cluster_radius'] . '});' . "\n";
                foreach ($group as $jsElementVar) {
                    $jsMarker .= $group_uid . '.addLayer(' . $jsElementVar . ');' . "\n";
                }
            } else {
                $jsMarker .= 'var ' . $group_uid . ' = L.layerGroup([' . implode(',', $group) . ']);' . "\n";
            }
            $jsMarker .= $this->config['id'] . '.addLayer(' . $group_uid . ');' . "\n";
        }

        return $jsMarker;
    }

    protected function getMarker($item, $table)
    {
        $jsMarker = '';
        $jsElementVar = $table . '_' . $item['uid'];
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $jsElementVarsForPopup = [];

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

                switch (strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION))) {
                    case 'kml':
                        // include javascript file for KML support
                        $this->scripts['leaflet-plugins'] = [
                            'src' => $this->path_res . 'leaflet-plugins/layer/vector/KML.js'
                        ];

                        $jsMarker .= 'var ' . $jsElementVar . ' = new L.KML(';
                        $jsMarker .= '"' . $file->getPublicUrl() . '"';
                        $jsMarker .= ");\n";
                        $jsMarker .= $this->config['id'] . '.addLayer(' . $jsElementVar . ');' . "\n";
                        break;
                    case 'gpx':
                        // include javascript file for GPX support
                        $this->scripts['leaflet-gpx'] = [
                            'src' => $this->path_res . 'leaflet-gpx/gpx.js'
                        ];
                        $options = [
                            'clickable' => 'false',
                            'polyline_options' => [
                                'color' => $item['color'],
                                'weight' => $item['width'] ?: 1,
                            ],
                            'marker_options' => [
                                'startIconUrl' => $this->path_res . 'leaflet-gpx/pin-icon-start.png',
                                'endIconUrl' => $this->path_res . 'leaflet-gpx/pin-icon-end.png',
                                'shadowUrl' => $this->path_res . 'leaflet-gpx/pin-shadow.png',
                            ],
                        ];
                        $jsMarker .= 'var ' . $jsElementVar . ' = new L.GPX("' . $file->getPublicUrl() . '",';

                        $jsMarker .= json_encode($options) . ");\n";
                        $jsMarker .= $this->config['id'] . '.addLayer(' . $jsElementVar . ');' . "\n";
                        break;
                }
                $jsElementVarsForPopup[] = $jsElementVar;
                break;
            case 'tx_odsosm_vector':
                // add styles from record if both are set - color and width
                if (!empty($item['color']) && !empty($item['width'])) {
                    $jsMarker .= 'var myStyle = {
                        "color": "'.$item['color'].'",
                        "weight": '.$item['width'].',
                        "opacity": 1
                    };';
                } else {
                    $jsMarker .= 'var myStyle = {};';
                }

                $fileObjects = $fileRepository->findByRelation('tx_odsosm_vector', 'file', $item['uid']);
                if ($fileObjects) {
                    $file = $fileObjects[0];
                    $filename = Environment::getPublicPath() . '/' . $file->getPublicUrl();
                    $jsMarker .= 'var ' . $jsElementVar . '_file = new L.geoJson(' . file_get_contents($filename) . ',
                    {
                        style: myStyle
                    });' . "\n";
                    $jsMarker .= $this->config['id'] . '.addLayer(' . $jsElementVar . '_file);' . "\n";

                    // Add vector file to layerswitcher
                    $this->layers[1][] = [
                        'title' => $item['title'] . ' ('. LocalizationUtility::translate('file', 'OdsOsm') .')',
                        'table' => $table,
                        'uid' => $item['uid'] . '_file'
                    ];
                    $jsElementVarsForPopup[] = $jsElementVar . '_file';
                }

                // add geojson from data field as well
                if ($item['data']) {
                    $jsMarker .= 'var ' . $jsElementVar . '_data = new L.geoJson(' . $item['data'] . ',
                    {
                        style: myStyle
                    });' . "\n";
                    $jsMarker .= $this->config['id'] . '.addLayer(' . $jsElementVar . '_data);' . "\n";

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
                if ($item['tx_odsosm_marker'] ?? false) {
                    $marker = $item['tx_odsosm_marker'];
                    $iconOptions = [
                        'iconSize' => [(int)$marker['size_x'], (int)$marker['size_y']],
                        'iconAnchor' => [-(int)$marker['offset_x'], -(int)$marker['offset_y']],
                        'popupAnchor' => [0, (int)$marker['offset_y']]
                    ];
                    if ($marker['type'] == 'html') {
                        $iconOptions['html'] = $marker['icon'];
                        $markerOptions['icon'] = 'icon: new L.divIcon(' . json_encode($iconOptions) . ')';
                    } else {
                        $icon =  $marker['icon']->getPublicUrl();
                        $iconOptions['iconUrl'] = $icon;
                        $markerOptions['icon'] = 'icon: new L.Icon(' . json_encode($iconOptions) . ')';
                    }
                } else {
                    $marker = [ 'type' => 'image' ];
                    $icon = $this->path_leaflet . 'images/marker-icon.png';
                }
                $jsMarker .= 'var ' . $jsElementVar . ' = new L.Marker([' . $item['latitude'] . ', ' . $item['longitude'] . '], {' . implode(',', $markerOptions) . "});\n";
                // Add group to layer switch
                if ($item['group_title'] ?? false) {
                    $this->layers[1][] = [
                        'title' => ($marker['type'] == 'html' ? $marker['icon'] : "<img class='marker-icon' src='" . $icon . "' />") . ' ' . $item['group_title'],
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
            // is there a properties attribute from geoJSON? If so, we will show the given properties
            $popupJsCode = '';
            if ($item['properties'] ?? null) {
                $geojsonProperties = json_encode(explode(', ', $item['properties']));
                $popupJsCode = "
                    function (layer) {
                        var osm_popup = '" . ($item['popup'] ?? '') . "';

                        var feature = layer.feature,
                        props = feature.properties,
                        ll = Object.keys(props),
                        attribute, value = '';

                        var osm_filter = " . $geojsonProperties . ";

                        osm_filter.forEach((osm_prop) => {
                            if (typeof props[osm_prop] !== 'undefined') {
                                value += '<dt>' + osm_prop + '</dt> <dd>' + props[osm_prop] + '</dd>';
                            }
                        });
                        return osm_popup + '<dl>' + value + '</dl>';
                    }
                ";
            } elseif ($item['popup'] ?? null) {
                $popupJsCode = json_encode($item['popup'] ?? '');
            }
            if ($this->config['show_popups'] == 1) {
                $jsMarker .= $jsElementVar . '.bindPopup(' . $popupJsCode . '); ' . "\n";
                if ($item['initial_popup'] ?? null) {
                    $jsMarker .= $jsElementVar . ".openPopup();\n";
                }
            } elseif ($this->config['show_popups'] == 2) {
                $jsMarker .= $jsElementVar . '.bindTooltip(' . $popupJsCode . ");\n";
            }
        }

        return $jsMarker;
    }
}
