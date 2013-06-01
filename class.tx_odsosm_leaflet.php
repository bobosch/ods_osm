<?php
class tx_odsosm_leaflet extends tx_odsosm_common {
	protected $layers;

	public function getMap($layers,$markers,$lon,$lat,$zoom){
 		$this->getMapCore();

		$this->script="
			".$this->getMapMain()."
			".$this->getMainLayers($layers)."
			".$this->getMapCenter($lat,$lon,$zoom)."
			".$this->getMarkers($markers)."
			".$this->getMarkerLayer();

		if($this->config['show_layerswitcher']){
			$this->script.=$this->getLayerSwitcher();
		}

		return $this->getHtml();
	}

	public function getMapCore($backpath=''){
		$path=$backpath.t3lib_extMgm::siteRelPath('ods_osm').'res/';
		$GLOBALS['TSFE']->getPageRenderer()->addCssFile($path.'leaflet/leaflet.css');
		$scripts=array($path.'leaflet/leaflet.js');
		tx_odsosm_div::addJsFiles($scripts);
	}

	public function getMapMain(){
		return "var ".$this->config['id']."=new L.Map('".$this->config['id']."');";
	}

	public function getLayer($layer,$i,$backpath=''){
		$var=preg_replace('/[^a-z]/','',strtolower($layer['title']));
		$this->layers[$layer['overlay']][$layer['title']]=$var;

		$options=array(
			'attribution'=>'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
		);
		if($layer['max_zoom']) $options['maxZoom']=$layer['max_zoom'];
		if($layer['subdomains']) $options['subdomains']=$layer['subdomains'];

		return '
			var '.$var.' = new L.TileLayer(\''.$layer['tile_url'].'\','.json_encode($options).');
			'.$this->config['id'].'.addLayer('.$var.');
		';
	}

	public function getLayerSwitcher(){
		$base=array();
		foreach($this->layers[0] as $title=>$var){
			$base[]='"'.$title.'":'.$var;
		}
		$overlay=array();
		foreach($this->layers[1] as $title=>$var){
			$overlay[]='"'.$title.'":'.$var;
		}
		return 'var layersControl=new L.Control.Layers({'.implode(',',$base).'},{'.implode(',',$overlay).'});
			'.$this->config['id'].'.addControl(layersControl);';
	}

	public function getMapCenter($lat,$lon,$zoom){
		return '
			var center = new L.LatLng('.floatval($lat).','.floatval($lon).');
			'.$this->config['id'].'.setView(center,'.intval($zoom).');
		';
	}

	public function getMarker($item,$table,$text){
		$jsMarker='';
		switch($table){
			case 'fe_users':
			case 'tt_address':
				$markerOptions = array();
				if($item['tx_odsosm_marker'] && is_array($this->markers[$item['tx_odsosm_marker']])){
					$marker=$this->markers[$item['tx_odsosm_marker']];
					$iconOptions = (object) array(
						'iconUrl' => $GLOBALS['TSFE']->absRefPrefix.'uploads/tx_odsosm/'.$marker['icon'],
						'iconSize' => array((int)$marker['size_x'], (int)$marker['size_y']),
						'iconAnchor' => array(-(int)$marker['offset_x'], -(int)$marker['offset_y']),
						'popupAnchor' => array(0, (int)$marker['offset_y'])
					);
					$markerOptions['icon'] = 'icon: new L.Icon(' . json_encode($iconOptions) . ')';
				}
				$jsMarker='var markerLocation = new L.LatLng('.$item['tx_odsosm_lat'].', '.$item['tx_odsosm_lon'].');
				var marker = new L.Marker(markerLocation, {' . implode(',', $markerOptions) . '});
				'.$this->config['id'].'.addLayer(marker);';
				if($item['popup']) $jsMarker.='marker.bindPopup("'.strtr($item['popup'],$this->escape_js).'");';
				break;
		}
		return $jsMarker;
	}

	public function getMarkerLayer(){
	}
}
?>