<?php

namespace Bobosch\OdsOsm\Provider;

use Bobosch\OdsOsm\Div;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Resource\FileRepository;

class Openlayers extends BaseProvider
{
    protected $group_titles = array();
    public $P;

    public function getMapCore($backpath = '')
    {
        $path = ($backpath ? $backpath : Div::RESOURCE_BASE_PATH);
        if ($this->config['local_js']) {
            $this->scripts['Openlayers'] = ['src' => $path . 'OpenLayers/OpenLayers.js'];
        } else {
            $this->scripts['Openlayers'] = ['src' => 'https://cdnjs.cloudflare.com/ajax/libs/openlayers/2.13.1/OpenLayers.js', 'sri' => 'sha384-1sdAnHpdcrkXIg3U6pRKfecnhahCO+SvNlpRoBSQ9qxY1LwSesu+L25qR9ceYg9V'];
        }
        $this->scripts['OpenlayersOds'] = ['src' => $path . 'tx_odsosm_openlayers.js'];
    }

    public function getMapMain()
    {
        $controls = array('oAttribution' => 'new OpenLayers.Control.Attribution()');
        if ($this->config['show_layerswitcher']) {
            $controls['oLayerSwitcher'] = "new OpenLayers.Control.LayerSwitcher({" . ($this->config['layerswitcher.']['div'] ? "'div':OpenLayers.Util.getElement('" . $this->config['id'] . "_layerswitcher')" : "") . $this->config['layerswitcher.']['options'] . "})";
        }
        if ($this->config['mouse_navigation']) {
            $controls['oNavigation'] = "new OpenLayers.Control.Navigation()";
        }
        if ($this->config['show_pan_zoom']) {
            $controls['oPanZoom'] = "new OpenLayers.Control.PanZoom" . ($this->config['show_pan_zoom'] == 1 ? 'Bar' : '') . "()";
        }
        if ($this->config['show_scalebar']) {
            $controls['oScalebar'] = "new OpenLayers.Control.ScaleLine()";
        }

        $vars = '';
        foreach ($controls as $var => $obj) {
            $vars .= $var . '=' . $obj . ";\n";
        }

        return (
            $vars .
            $this->config['id'] . "=new OpenLayers.Map('" . $this->config['id'] . "',{
				controls:[" . implode(',', array_keys($controls)) . "],
				numZoomLevels:19,
				projection:new OpenLayers.Projection('EPSG:900913'),
				displayProjection:new OpenLayers.Projection('EPSG:4326')
			});\n" .
            ($this->config['show_layerswitcher'] == 2 ? "oLayerSwitcher.maximizeControl();\n" : "")
        );
    }

    protected function getLayerSwitcher()
    {
        if ($this->config['layerswitcher.']['div']) {
            $content .= '<div id="' . $this->config['id'] . '_layerswitcher" class="olControlLayerSwitcher"></div>';
        }
    }

    protected function getLayer($layer, $i, $backpath = '')
    {
        if ($layer['javascript_include']) {
            $javascript_include = strtr($layer['javascript_include'], array(
                '###STATIC_SCRIPT###' => $this->config['static_script'],
            ));
            $parts = parse_url($javascript_include);
            $filename = basename($parts['path']);
            if ($parts['scheme']) {
                $script = $javascript_include;
            } else {
                $script = $GLOBALS['TSFE']->absRefPrefix . $backpath . $javascript_include;
            }
            // Include javascript only once if different layers use the same javascript
            $this->scripts[$filename] = [ 'src' => $script];
        }
        if ($layer['javascript_openlayers']) {
            $jsMainLayer = $this->config['id'] . ".addLayer(" . strtr($layer['javascript_openlayers'], array(
                    '###STATIC_SCRIPT###' => $this->config['static_script'],
                    '###TITLE###' => $layer['title'],
                    '###VISIBLE###' => "'visibility':" . ($layer['visible'] ? 'true' : 'false'),
                )) . ");\n";
        } elseif ($layer['tile_url']) {
            // url
            $layer['tile_url'] = strtr($this->getTileUrl($layer), array('{x}' => '${x}', '{y}' => '${y}', '{z}' => '${z}'));
            if (strpos($layer['tile_url'], '{s}')) {
                if ($layer['subdomains']) {
                    $subdomains = $layer['subdomains'];
                } else {
                    $subdomains = 'abc';
                }
                $url = array();
                for ($i = 0; $i < strlen($subdomains); $i++) {
                    $url[] = strtr($layer['tile_url'], array('{s}' => substr($subdomains, $i, 1)));
                }
            } else {
                $url = $layer['tile_url'];
            }

            // options
            $options = array();
            if ($layer['attribution']) {
                $options['attribution'] = $layer['attribution'];
            }
            if ($layer['max_zoom']) {
                $options['numZoomLevels'] = $layer['max_zoom'];
            }
            if ($layer['overlay']) {
                $options['isBaseLayer'] = false;
                $options['transparent'] = true;
            }
            $options['tileOptions']['crossOriginKeyword'] = null;
            $options['visibility'] = $layer['visible'] ? true : false;

            $params = array(
                '"' . $layer['title'] . '"',
                json_encode($url),
                json_encode($options, JSON_NUMERIC_CHECK)
            );
            $jsMainLayer = $this->config['id'] . '.addLayer(new OpenLayers.Layer.OSM(' . implode(',', $params) . '));' . "\n";
        }
        if (!$layer['overlay'] && $layer['visible']) {
            $jsMainLayer .= $this->config['id'] . '.setBaseLayer(' . $this->config['id'] . '.layers[' . $i . "]);\n";
        }

        return $jsMainLayer;
    }

    protected function getMarker($item, $table)
    {
        $jsMarker = '';
        switch ($table) {
            case 'tx_odsosm_track':
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $fileObjects = $fileRepository->findByRelation('tx_odsosm_track', 'file', $item['uid']);
                if ($fileObjects) {
                    $file = $fileObjects[0];
                } else {
                    break;
                }
                $jsMarker .= "mapGpx(" . $this->config['id'] . ",'" .  $file->getPublicUrl() . "','" . $item['title'] . "','" . $item['color'] . "'," . $item['width'] . ");\n";
                break;
            case 'tx_odsosm_vector':
                $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                $fileObjects = $fileRepository->findByRelation('tx_odsosm_vector', 'file', $item['uid']);
                if ($fileObjects) {
                    $file = $fileObjects[0];
                    $filename = Environment::getPublicPath() . '/' . $file->getPublicUrl();
                    $jsMarker .= "mapVector(" . $this->config['id'] . ",'" . $item['title'] . " (File)'," . file_get_contents($filename) . ");\n";
                }
                if ($item['data']) {
                    $jsMarker .= "mapVector(" . $this->config['id'] . ",'" . $item['title'] . "'," . $item['data'] . ");\n";
                }
                break;
            default:
                if (is_array($item['tx_odsosm_marker'])) {
                    $marker = $item['tx_odsosm_marker'];
                } else {
                    $marker = array(
                        'icon' => PathUtility::getAbsoluteWebPath(
                            GeneralUtility::getFileAbsFileName(Div::RESOURCE_BASE_PATH . 'OpenLayers/img/marker.png')
                        ),
                        'type' => 'image',
                        'size_x' => 21,
                        'size_y' => 25,
                        'offset_x' => -11,
                        'offset_y' => -25
                    );
                }

                // Add group to layer switch
                if (!in_array($item['group_title'], $this->group_titles)) {
                    $this->group_titles[$item['group_uid']] = $item['group_title'];
                    $jsMarker .= 'var layerMarkers_' . $item['group_uid'] . '=new OpenLayers.Layer.Markers(' . json_encode(($marker['type'] == 'html' ? $marker['icon'] : '<img src="' . $marker['icon'] . '" />') . ' ' . $item['group_title']) . ");\n";
                    $jsMarker .= $this->config['id'] . '.addLayer(layerMarkers_' . $item['group_uid'] . ');';
                }
                $jsMarker .= 'mapMarker(' . $this->config['id'] . ',' . 'layerMarkers_' . $item['group_uid'] . ',' . json_encode(array(
                        'longitude' => $item['longitude'],
                        'latitude' => $item['latitude'],
                        'icon' => $marker['icon'],
                        'type' => $marker['type'],
                        'size_x' => $marker['size_x'],
                        'size_y' => $marker['size_y'],
                        'offset_x' => $marker['offset_x'],
                        'offset_y' => $marker['offset_y'],
                        'popup' => $item['popup'],
                        'show_popups' => intval($this->config['show_popups']),
                        'initial_popup' => intval($item['initial_popup']),
                    )) . ");\n";
                break;
        }

        return $jsMarker;
    }

}
