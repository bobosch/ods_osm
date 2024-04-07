<?php

namespace Bobosch\OdsOsm\Provider;

use Bobosch\OdsOsm\Div;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

abstract class BaseProvider
{
    /** @var ContentObjectRenderer */
    public $cObj; // Must set from instantiating class
    protected $config;
    protected $script;

    /**
     *
     */
    protected $pageRenderer;

    /** @var array keeping all JavaScripts to be included */
    protected $scripts = [];

    protected $layers = [
        0 => [], // Base
        1 => [], // Overlay
        2 => [], // Marker
    ];

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
        return '';
    }

    /**
     * @return string
     */
    protected function getMarker($item, $table)
    {
        return '';
    }

    /**
     * Get JavaScript code for fulltext button
     *
     * @return string The JavaScript to add the fullscreen button
     */
    protected function getFullScreen()
    {
        return '';
    }

    public function init($config): void
    {
        $this->config = $config;
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * @return string
     */
    public function getMap($layers, $markers, $lon, $lat, $zoom)
    {
        $this->getMapCore();

        $this->layers = $layers;

        $baselayers = $layers[0] ?? null;
        $overlays = $layers[1] ?? null;

        $this->script = "
			" . $this->getMapMain() . "
			" . $this->getBaseLayers($baselayers) . "
		    " . $this->getOverlayLayers($overlays) . "
			" . $this->getMapCenter($lat, $lon, $zoom) . "
			" . $this->getMarkers($markers);

        if (($this->config['show_layerswitcher'] ?? null) && ($this->config['show_layerswitcher'] > 0)) {
            $this->script .= $this->getLayerSwitcher() . "\n";
        }

        if ($this->config['show_fullscreen'] ?? null) {
            $this->script .= $this->getFullScreen() . "\n";
        }

        Div::addJsFiles($this->scripts, null);

        return $this->getHtml();
    }

    /**
     * @return string
     */
    public function getBaseLayers($layers, $backpath = '')
    {
        // Main layer
        $i = 0;
        $jsBaseLayer = [];
        if (is_array($layers) && !empty($layers)) {
            foreach ($layers as $layer) {
                $jsBaseLayer[] = $this->getLayer($layer, $i, $backpath);
                $i++;
            }
        }

        return implode("\n", ($jsBaseLayer));
    }

    /**
     * @return string
     */
    public function getOverlayLayers($layers, $backpath = '')
    {
        // Main layer
        $i = 0;
        $jsOverlayLayer = [];
        if (is_array($layers) && !empty($layers)) {
            foreach ($layers as $layer) {
                $jsOverlayLayer[] = $this->getLayer($layer, $i, $backpath);
                $i++;
            }
        }

        return implode("\n", ($jsOverlayLayer));
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
        $mousePosition = '';
        $popupcode = '';
        if ($this->config['library'] == 'openlayers') {
            if ($this->config['mouse_position']) {
                $mousePosition = '<div id="mouse-position-' . $this->config['id'] . '">' . LocalizationUtility::translate('mouse_position', 'OdsOsm') . ':&nbsp;</div>';
            }
            $popupcode = '
                <div id="popup" class="ol-popup">
                <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                <div id="popup-content"></div>
            </div>';
        }
        return '<div style="width:' . $this->config['width'] . '; height:' . $this->config['height'] . '; " id="' . $this->config['id'] . '"></div>' . $mousePosition . $popupcode;
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
        }
        return 'http://' . $layer['tile_url'];
    }

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }
}
