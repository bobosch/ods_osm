<?php

namespace Bobosch\OdsOsm\Provider;

use Bobosch\OdsOsm\Div;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

abstract class BaseProvider
{
    /** @var ContentObjectRenderer */
    public $cObj; // Must set from instantiating class
    protected $config;
    protected $script;
    protected $scripts = array();

    // Implement these functions
    public function getMapCore($backpath = '')
    {
    }

    public function getMapMain()
    {
    }

    public function getMapCenter($lat, $lon, $zoom)
    {
    }

    /**
     * @return string
     */
    protected function getLayer($layer, $i, $backpath = '')
    {
    }

    /**
     * @return string
     */
    protected function getMarker($item, $table)
    {
    }

    public function init($config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getMap($layers, $markers, $lon, $lat, $zoom)
    {
        $this->getMapCore();

        $this->script = "
			" . $this->getMapMain() . "
			" . $this->getMainLayers($layers) . "
			" . $this->getMapCenter($lat, $lon, $zoom) . "
			" . $this->getMarkers($markers);

        if ($this->config['show_layerswitcher']) {
            $this->script .= $this->getLayerSwitcher();
        }

        Div::addJsFiles($this->scripts, null);

        return $this->getHtml();
    }

    /**
     * @return string
     */
    public function getMainLayers($layers, $backpath = '')
    {
        // Main layer
        $i = 0;
        $jsMainLayer = '';
        foreach ($layers as $layer) {
            $jsMainLayer .= $this->getLayer($layer, $i, $backpath);
            $i++;
        }
        Div::addJsFiles($this->scripts, null);

        return $jsMainLayer;
    }

    /**
     * @return string
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * @return string
     */
    protected function getMarkers($markers)
    {
        $jsMarker = '';
        foreach ($markers as $table => $items) {
            foreach ($items as $item) {
                $jsMarker .= $this->getMarker($item, $table);
            }
        }

        return $jsMarker;
    }

    /**
     * @return string
     */
    protected function getLayerSwitcher()
    {
        return '';
    }

    /**
     * @return string
     */
    protected function getHtml()
    {
        return '<div style="width:' . $this->config['width'] . ';height:' . $this->config['height'] . ';" id="' . $this->config['id'] . '"></div>';
    }

    /**
     * @return string
     */
    protected function getTileUrl($layer)
    {
        if (strpos($layer['tile_url'], '://') !== false) {
            return $layer['tile_url'];
        }
        // if protocoll is missing, we add http:// or https://
        if ($layer['tile_https'] == 1) {
            return 'https://' . $layer['tile_url'];
        } else {
            return 'http://' . $layer['tile_url'];
        }
    }
}
