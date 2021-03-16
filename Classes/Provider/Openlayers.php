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
        $path = ($backpath ? $backpath : $GLOBALS['TSFE']->absRefPrefix) . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('ods_osm')) . 'Resources/Public/';
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
                $jsMarker .= "mapGpx(" . $this->config['id'] . ",'/" .  $file->getPublicUrl() . "','" . $item['title'] . "','" . $item['color'] . "'," . $item['width'] . ");\n";
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
                        'icon' => PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('ods_osm')) . 'Resources/Public/OpenLayers/img/marker.png',
                        'type' => 'image',
                        'size_x' => 21,
                        'size_y' => 25,
                        'offset_x' => -11,
                        'offset_y' => -25
                    );
                }
                if ($marker['type'] != 'html') {
                    $marker['icon'] = $GLOBALS['TSFE']->absRefPrefix . $marker['icon'];
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

    /* ********************
        Backend include
    ******************** */

    public function getMapBE($layers, $mode, $lat, $lon, $zoom, $doc)
    {
        $jsMainLayer = $this->getMainLayers($layers, '../');

        // Include JS
        $this->getMapCore('../');
        Div::addJsFiles($this->scripts, $doc);

        // Action
        switch ($mode) {
            case 'vector':
                $action = $this->getJSvectors();
                break;
            default:
                $action = $this->getJScoordinates();
                break;
        }

        // just geht these values once:
        $latVar = $this->getJSfieldVariable('lat');
        $lonVar = $this->getJSfieldVariable('lon');
        $zoomVar = $this->getJSfieldVariable('zoom');

        return "var map; //complex object of type OpenLayers.Map

" . $action . "

	function map(){
        var lat = " . json_encode($lat) . ";
		var lon = " . json_encode($lon) . ";
        var zoom = " . json_encode($zoom) . ";
        if (typeof " . $latVar . " != 'undefined') {
			lat = " . $latVar . ".value;
		}
		if (typeof " . $lonVar . " != 'undefined') {
			lon = " . $lonVar . ".value;
		}
		if (typeof " . $zoomVar . " != 'undefined') {
            if (" . $zoomVar . ".value) {
                zoom = " . $zoomVar . ".value;
            }
        }
		" . $this->getMapMain() . "
		" . $jsMainLayer . "
        mapCenter(" . $this->config['id'] . ", lat, lon, zoom);
        var markers = new OpenLayers.Layer.Markers('Position');
		map.addLayer(markers);
		var size = new OpenLayers.Size(21,25);
		var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
		var icon = new OpenLayers.Icon('/typo3conf/ext/ods_osm/Resources/Public/Icons/icon_tx_odsosm_marker.png', size, offset);
		markers.addMarker(new OpenLayers.Marker(
			new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection('EPSG:4326'), map.getProjectionObject()),
			icon)
		);
		mapAction();
	}";
    }


    /**
	 * Get Javascript variable of edit form field of the given name
	 *
	 * @param string $name Field name, e.g. "lat" or "lon"
	 *
	 * @return string Javascript variable for the field
	 */
	protected function getJSfieldVariable($name)
	{
        /**
         * The button for the coordinatpicker is placed to the
         * longitute field. That's why we have in $this->P['itemName']
         * always "data[tt_address][1][longitude]" --> replacment is
         * necessary in case of latitude and zoom setting.
         * possible (known) values of $this->P['itemName']
         *
         * data[fe_users][1][tx_odsosm_lon]
         * data[tt_address][1][longitude]
         * data[tt_content][1][pi_flexform][data][sDEF][lDEF][lon][vDEF]
         */

        $replacements = [];
        if ($name === 'lat' || $name === 'zoom') {
            if (stripos($this->P['itemName'], 'fe_users') > 0 |
                stripos($this->P['itemName'], 'tt_content') > 0) {
                $replacements['lon'] = $name;
            } else if (stripos($this->P['itemName'], 'tt_address') > 0) {
                $replacements['long'] = $name;
            }
        }
		return 'window.opener.document.editform["'
			. strtr($this->P['itemName'], $replacements)
            . '"]';
	}


    function getJScoordinates()
    {
        return "
	OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
		defaultHandlerOptions: {
			'single': true,
			'double': false,
			'pixelTolerance': 0,
			'stopSingle': false,
			'stopDouble': false
		},
		initialize: function(options) {
			this.handlerOptions = OpenLayers.Util.extend(
				{}, this.defaultHandlerOptions
			);
			OpenLayers.Control.prototype.initialize.apply(
				this, arguments
			);
			this.handler = new OpenLayers.Handler.Click(
				this, {
					'click': this.onClick
				}, this.handlerOptions
			);
		},
		onClick: getCoordinates
	});

	function mapAction(){
		map.addControl(new OpenLayers.Control.MousePosition());
		var control = new OpenLayers.Control.Click({
			handlerOptions: {
				'single': true
			}
		});
		map.addControl(control);
		control.activate();
	}

	function getCoordinates(evt){
		var pixel = new OpenLayers.Pixel(evt.xy.x,evt.xy.y);
		var lonlat = map.getLonLatFromPixel(pixel);
		var lonlatGCS = OpenLayers.Layer.SphericalMercator.inverseMercator(lonlat.lon, lonlat.lat);
		setBEcoordinates(lonlatGCS.lon,lonlatGCS.lat);
	}
";
    }

    function getJSvectors()
    {
        return "
	function mapAction(){
		var vectors = new OpenLayers.Layer.Vector('Vector Layer');
		map.addControl(new OpenLayers.Control.MousePosition());
		map.addControl(new OpenLayers.Control.EditingToolbar(vectors));
		var options = {
			hover: true,
			onSelect: getVectors
		};
		var control = new OpenLayers.Control.SelectFeature(vectors, options);
		map.addControl(control);
		map.addLayer(vectors);
		control.activate();
	}

	function getVectors(feature){
		var format = new OpenLayers.Format.GeoJSON({
			'internalProjection': map.baseLayer.projection,
			'externalProjection': new OpenLayers.Projection('EPSG:4326')
		});
		var str = format.write(feature);
		setBEfield(str);
	}
";
    }
}
