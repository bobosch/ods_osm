<?php
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class tx_odsosm_openlayers3 extends tx_odsosm_common {
	protected $layers;

	public function getMapCore($backpath=''){
 		$path=($backpath ? $backpath : $GLOBALS['TSFE']->absRefPrefix) . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('ods_osm') . 'res/';
 		$path=($this->config['local_js'] ? $path.'OpenLayers3/' : 'http://ol3js.org/en/master/');
		/** @var PageRenderer $pageRenderer */
		$pageRenderer = null;
		if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()) < 8000000) {
			$pageRenderer = $GLOBALS['TSFE']->getPageRendered();
		} else {
			$pageRenderer = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
		}
		$pageRenderer->addCssFile($path.'css/ol.css');
		$this->scripts=array($path.'build/ol.js');
	}

	public function getMapMain(){
	}

	public function getMapCenter($lat,$lon,$zoom){
		return '
			view.setCenter(ol.proj.transform(['.$lon.', '.$lat.'], \'EPSG:4326\', \'EPSG:3857\'));
			view.setZoom('.$zoom.');
		';
	}

	protected function getLayer($layer,$i,$backpath=''){
		if(empty($layer['subdomains'])) $layer['subdomains']='abc';
		$layer['subdomains']=substr($layer['subdomains'],0,1).'-'.substr($layer['subdomains'],-1,1);
		$layer['tile_url']=strtr($this->getTileUrl($layer),array('{s}'=>'{'.$layer['subdomains'].'}'));

		// TODO: Move map code to getMapMain
		return "
		view=new ol.View({
			center: [0, 0],
			zoom: 1
		});

		var map = new ol.Map({
			target: '".$this->config['id']."',
			controls: ol.control.defaults({
				attributionOptions: /** @type {olx.control.AttributionOptions} */ ({
					collapsible: false
				})
			}),
			layers: [
				new ol.layer.Tile({
					source: new ol.source.OSM({
						attributions: [
							new ol.Attribution({
								html: '".$layer['attribution']."'
							})
						],
						url: '".$layer['tile_url']."'
					})
				})
			],
			view: view
		});
		";
	}

	protected function getMarker($item,$table){
	}
}
?>
