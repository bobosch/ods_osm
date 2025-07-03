<?php

namespace Bobosch\OdsOsm\Provider;

use Bobosch\OdsOsm\Div;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

abstract class BaseProvider
{
    // Must set from instantiating class
    public ContentObjectRenderer $cObj;

    protected PageRenderer $pageRenderer;

    protected array $config = [];

    protected string $script = '';

    /** keeping all JavaScripts to be included */
    protected array $scripts = [];

    protected array $layers = [
        0 => [], // Base
        1 => [], // Overlay
        2 => [], // Marker
    ];

    public function __construct()
    {
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
    }

    // Implement these functions
    abstract public function getMapCore(string $backPath = ''): void;

    abstract public function getMapMain(): string;

    abstract public function getMapCenter($lat, $lon, $zoom): string;

    abstract protected function getLayer($layer, $i, string $backPath = ''): string;

    abstract protected function getMarker(array $item, string $table): string;

    /**
     * Get JavaScript code for fulltext button
     *
     * @return string The JavaScript to add the fullscreen button
     */
    abstract protected function getFullScreen(): string;

    public function init(array $config): void
    {
        $this->config = $config;
    }

    public function getMap(array $layers, array $markers, $lon, $lat, $zoom): string
    {
        $this->getMapCore();

        $this->layers = $layers;

        $baseLayers = $layers[0] ?? null;
        $overlays = $layers[1] ?? null;

        $this->script = "
			" . $this->getMapMain() . "
			" . $this->getBaseLayers($baseLayers) . "
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

    public function getBaseLayers($layers, string $backPath = ''): string
    {
        // Main layer
        $i = 0;
        $jsBaseLayer = [];
        if (is_array($layers) && $layers !== []) {
            foreach ($layers as $layer) {
                $jsBaseLayer[] = $this->getLayer($layer, $i, $backPath);
                $i++;
            }
        }

        return implode("\n", ($jsBaseLayer));
    }

    public function getOverlayLayers($layers, string $backPath = ''): string
    {
        // Main layer
        $i = 0;
        $jsOverlayLayer = [];
        if (is_array($layers) && $layers !== []) {
            foreach ($layers as $layer) {
                $jsOverlayLayer[] = $this->getLayer($layer, $i, $backPath);
                $i++;
            }
        }

        return implode("\n", ($jsOverlayLayer));
    }

    public function getScript(): string
    {
        return $this->script;
    }

    protected function getMarkers(array $markers): string
    {
        $jsMarker = '';
        foreach ($markers as $table => $items) {
            foreach ($items as $item) {
                $jsMarker .= $this->getMarker($item, $table);
            }
        }

        return $jsMarker;
    }

    abstract protected function getLayerSwitcher(): string;

    protected function getHtml(): string
    {
        $mousePosition = '';
        $popupCode = '';
        if ($this->config['library'] === 'openlayers') {
            if ($this->config['mouse_position']) {
                $mousePosition = '<div id="mouse-position-' . $this->config['id'] . '">' . LocalizationUtility::translate('mouse_position', 'OdsOsm') . ':&nbsp;</div>';
            }

            $popupCode = '
                <div id="popup" class="ol-popup">
                <a href="#" id="popup-closer" class="ol-popup-closer"></a>
                <div id="popup-content"></div>
            </div>';
        }

        return '<div style="width:' . $this->config['width'] . '; height:' . $this->config['height'] . '; " id="' . $this->config['id'] . '"></div>' . $mousePosition . $popupCode;
    }

    protected function getTileUrl(array $layer): string
    {
        if (str_contains((string) $layer['tile_url'], '://')) {
            return $layer['tile_url'];
        }

        // if the protocol is missing, we add http:// or https://
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
