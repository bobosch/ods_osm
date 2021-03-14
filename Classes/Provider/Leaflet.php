<?php

namespace Bobosch\OdsOsm\Provider;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class Leaflet extends BaseProvider
{
    protected $layers = array(
        0 => array(), // Base
        1 => array(), // Overlay
        2 => array(), // Marker
    );
    protected $path_res;
    protected $path_leaflet;

    public function getMapCore($backpath = '')
    {
        $this->path_res = ($backpath ? $backpath : $GLOBALS['TSFE']->absRefPrefix) . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('ods_osm')) . 'Resources/Public/';
        $this->path_leaflet = ($this->config['local_js'] ? $this->path_res . 'leaflet/' : 'https://unpkg.com/leaflet@1.7.1/dist/');
        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile($this->path_leaflet . 'leaflet.css');
        $this->scripts['leaflet'] = ['src' => $this->path_leaflet . 'leaflet.js', 'sri' => 'sha384-RFZC58YeKApoNsIbBxf4z6JJXmh+geBSgkCQXFyh+4tiFSJmJBt+2FbjxW7Ar16M'];
    }

    public function getMapMain()
    {
        $controls = array();
        if ($this->config['show_scalebar']) {
            $controls['scalebar'] = 'new L.control.scale()';
        }

        $vars = '';
        foreach ($controls as $var => $obj) {
            $vars .= "\n\t\t\t" . $this->config['id'] . '.addControl(' . $obj . ");";
        }

        $jsMain =
            $this->config['id'] . "=new L.Map('" . $this->config['id'] . "');
			L.Icon.Default.imagePath='" . $this->path_leaflet . "images/';"
            . $vars;
        if ($this->config['cluster']) {
            $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
            $pageRenderer->addCssFile($this->path_res . 'leaflet-markercluster/MarkerCluster.css');
            $pageRenderer->addCssFile($this->path_res . 'leaflet-markercluster/MarkerCluster.Default.css');
            $this->scripts['leaflet-markercluster'] = ['src' => $this->path_res . 'leaflet-markercluster/leaflet.markercluster.js'];
        }

        return $jsMain;
    }

    protected function getLayer($layer, $i, $backpath = '')
    {
        $var = preg_replace('/[^a-z]/', '', strtolower($layer['title']));
        $this->layers[$layer['overlay']][$layer['title']] = $var;

        if ($layer['javascript_leaflet']) {
            $jsLayer = strtr($layer['javascript_leaflet'], array(
                    '###STATIC_SCRIPT###' => $this->config['static_script'],
                    '###TITLE###' => $layer['title'],
                    '###VISIBLE###' => "'visibility':" . ($layer['visible'] ? 'true' : 'false'),
                )) . ";\n";
        } elseif ($layer['tile_url']) {
            $options = array();
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

        $jsLayer = "\n\t\t\tvar " . $var . ' = ' . $jsLayer;

        // only show one base layer on the map
        if ($i == 0) {
            $jsLayer .= "\n\t\t\t" . $this->config['id'] . '.addLayer(' . $var . ');';
        }

        return $jsLayer;
    }

    protected function getLayerSwitcher()
    {
        $base = array();
        if (is_array($this->layers[0]) && count($this->layers[0]) > 1) {
            foreach ($this->layers[0] as $title => $var) {
                $base[] = '"' . $title . '":' . $var;
            }
        }
        $overlay = array();
        if (is_array($this->layers[1])) {
            foreach ($this->layers[1] as $title => $var) {
                $overlay[] = '\'' . $title . '\':' . $var;
            }
        }

        return 'var layersControl=new L.Control.Layers({' . implode(',', $base) . '},{' . implode(',', $overlay) . '}' . ($this->config['show_layerswitcher'] == 2 ? ',{collapsed:false}' : '') . ');
			' . $this->config['id'] . '.addControl(layersControl);';
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
                $jsMarker .= 'var ' . $group_uid . ' = new L.MarkerClusterGroup({maxClusterRadius:' . $this->config['cluster_radius'] . '});' . "\n";
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

        switch ($table) {
            case 'tx_odsosm_track':
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $fileObjects = $fileRepository->findByRelation('tx_odsosm_track', 'file', $item['uid']);
                if ($fileObjects) {
                    $file = $fileObjects[0];
                } else {
                    break;
                }

                $path = $GLOBALS['TSFE']->absRefPrefix . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('ods_osm')) . 'Resources/Public/';
                // Add tracks to layerswitcher
                $this->layers[1][$item['title']] = $jsElementVar;

                switch (strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION))) {
                    case 'kml':
                        // include javascript file for KML support
                        $this->scripts['leaflet-plugins'] = ['src' => $path . 'leaflet-plugins/layer/vector/KML.js'];

                        $jsMarker .= 'var ' . $jsElementVar . ' = new L.KML(';
                        $jsMarker .= '"/' . $file->getPublicUrl() . '"';
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
                        $jsMarker .= 'var ' . $jsElementVar . ' = new L.GPX("/' . $file->getPublicUrl() . '",';
                        $jsMarker .= json_encode($options) . ");\n";
                        $jsMarker .= $this->config['id'] . '.addLayer(' . $jsElementVar . ');' . "\n";
                        break;
                }
                break;
            case 'tx_odsosm_vector':
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $fileObjects = $fileRepository->findByRelation('tx_odsosm_vector', 'file', $item['uid']);
                if ($fileObjects) {
                    $file = $fileObjects[0];
                    $filename = Environment::getPublicPath() . '/' . $file->getPublicUrl();
                    $jsMarker .= 'var ' . $jsElementVar . '_file = new L.geoJson(' . file_get_contents($filename) . ');' . "\n";
                    $jsMarker .= $this->config['id'] . '.addLayer(' . $jsElementVar . '_file);' . "\n";
                }

                // add geojson from data field as well
                if ($item['data']) {
                    $jsMarker .= 'var ' . $jsElementVar . '_data = new L.geoJson(' . $item['data'] . ');' . "\n";
                    $jsMarker .= $this->config['id'] . '.addLayer(' . $jsElementVar . '_data);' . "\n";
                }

                break;
            default:
                $markerOptions = array();
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
                        $icon = $GLOBALS['TSFE']->absRefPrefix . $marker['icon'];
                        $iconOptions['iconUrl'] = $icon;
                        $markerOptions['icon'] = 'icon: new L.Icon(' . json_encode($iconOptions) . ')';
                    }
                } else {
                    $icon = $GLOBALS['TSFE']->absRefPrefix . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('ods_osm')) . 'Resources/Public/leaflet/images/marker-icon.png';
                }
                $jsMarker .= 'var ' . $jsElementVar . ' = new L.Marker([' . $item['latitude'] . ', ' . $item['longitude'] . '], {' . implode(',', $markerOptions) . "});\n";
                // Add group to layer switch
                if ($item['group_title']) {
                    $this->layers[1][($marker['type'] == 'html' ? $marker['icon'] : '<img src="' . $icon . '" />') . ' ' . $item['group_title']] = $item['group_uid'];
                    $this->layers[2][$item['group_uid']][] = $jsElementVar;
                } else {
                    $this->layers[2][$this->config['id'] . '_g'][] = $jsElementVar;
                }

                break;
        }

        if ($jsElementVar) {
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
