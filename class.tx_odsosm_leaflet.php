<?php
class tx_odsosm_leaflet extends tx_odsosm_common {
	protected $layers;
	protected $path_leaflet;

	public function getMapCore($backpath=''){
		$path=($backpath ? $backpath : $GLOBALS['TSFE']->absRefPrefix).t3lib_extMgm::siteRelPath('ods_osm').'res/';
		$this->path_leaflet=($this->config['local_js'] ? $path.'leaflet/' : 'http://cdn.leafletjs.com/leaflet-0.7.3/');
		$GLOBALS['TSFE']->getPageRenderer()->addCssFile($this->path_leaflet.'leaflet.css');
		$scripts=array($this->path_leaflet.'leaflet.js', $path.'leaflet-gpx/gpx.js');
		tx_odsosm_div::addJsFiles($scripts);
	}

	public function getMapMain(){
		return
			$this->config['id']."=new L.Map('".$this->config['id']."');
			L.Icon.Default.imagePath='".$this->path_leaflet."images';";
	}

	protected function getLayer($layer,$i,$backpath=''){
		$var=preg_replace('/[^a-z]/','',strtolower($layer['title']));
		$this->layers[$layer['overlay']][$layer['title']]=$var;

		$options=array();
		if($layer['max_zoom']) $options['maxZoom']=$layer['max_zoom'];
		if($layer['subdomains']) $options['subdomains']=$layer['subdomains'];
		if($layer['attribution']) $options['attribution']=$layer['attribution'];

		return '
			var '.$var.' = new L.TileLayer(\''.$layer['tile_url'].'\','.json_encode($options).');
			'.$this->config['id'].'.addLayer('.$var.');
		';
	}

	protected function getLayerSwitcher(){
		$base=array();
		if(is_array($this->layers[0]) && count($this->layers[0])>1){
			foreach($this->layers[0] as $title=>$var){
				$base[]='"'.$title.'":'.$var;
			}
		}
		$overlay=array();
		if(is_array($this->layers[1])){
			foreach($this->layers[1] as $title=>$var){
				$overlay[]='"'.$title.'":'.$var;
			}
		}
		return 'var layersControl=new L.Control.Layers({'.implode(',',$base).'},{'.implode(',',$overlay).'});
			'.$this->config['id'].'.addControl(layersControl);';
	}

	public function getMapCenter($lat,$lon,$zoom){
		$return='var center = new L.LatLng('.floatval($lat).','.floatval($lon).');'.$this->config['id'].'.setView(center,'.intval($zoom).');';
		if($this->config['position']) $return.=$this->config['id'].'.locate();'.$this->config['id'].'.on("locationfound",function(e){var radius=e.accuracy/2;L.circle(e.latlng,radius).addTo('.$this->config['id'].');});';
		return $return;
	}

	protected function getMarker($item,$table){
		$jsMarker='';
		switch($table){
			case 'fe_users':
			case 'tt_address':
				$markerOptions = array();
				if($item['tx_odsosm_marker'] && is_array($this->markers[$item['tx_odsosm_marker']])){
					$marker=$this->markers[$item['tx_odsosm_marker']];
					$icon=$GLOBALS['TSFE']->absRefPrefix.'uploads/tx_odsosm/'.$marker['icon'];
					$iconOptions = (object) array(
						'iconUrl' => $GLOBALS['TSFE']->absRefPrefix.'uploads/tx_odsosm/'.$marker['icon'],
						'iconSize' => array((int)$marker['size_x'], (int)$marker['size_y']),
						'iconAnchor' => array(-(int)$marker['offset_x'], -(int)$marker['offset_y']),
						'popupAnchor' => array(0, (int)$marker['offset_y'])
					);
					$markerOptions['icon'] = 'icon: new L.Icon(' . json_encode($iconOptions) . ')';
				}else{
					$icon=$GLOBALS['TSFE']->absRefPrefix.t3lib_extMgm::siteRelPath('ods_osm').'res/leaflet/images/marker-icon.png';
				}
				$jsMarker.='var marker=new L.Marker(['.$item['tx_odsosm_lat'].', '.$item['tx_odsosm_lon'].'], {' . implode(',', $markerOptions) . "});\n";
				if($item['popup']) {
					$jsMarker.='marker.bindPopup("'.strtr($item['popup'],$this->escape_js)."\");\n";
					if ($item['initial_popup']) {
						$jsMarker.="marker.openPopup();\n";
					}
				}
				// Add group to layer switch
				if($item['group_title']){
					if(!in_array($item['group_uid'], $this->layers[1])) {
						$this->layers[1]["<img src='".$icon."' /> ".$item['group_title']]=$item['group_uid'];
						$jsMarker.='var '.$item['group_uid']."=L.layerGroup([marker]);\n";
						$jsMarker.=$this->config['id'].'.addLayer('.$item['group_uid'].");\n";
					}else{
						$jsMarker.=$item['group_uid'].".addLayer(marker);\n";
					}
				}else{
					$jsMarker.=$this->config['id'].".addLayer(marker);\n";
				}
				break;
				case 'tx_odsosm_track':
					$path = t3lib_extMgm::siteRelPath('ods_osm').'res/leaflet-gpx/';
					$jsMarker = "var trackGPX = new L.GPX(";
					$jsMarker .= '"' . $GLOBALS['TSFE']->absRefPrefix.'uploads/tx_odsosm/' . $item['file'] . '"';
					$jsMarker .= ", { color: '" . $item['color'] . "', clickable: false ";
					$jsMarker .= ", marker_options: { startIconUrl: '" . $path . "pin-icon-start.png',";
					$jsMarker .= "endIconUrl: '" . $path . "pin-icon-end.png',";
					$jsMarker .= "shadowUrl: '" . $path . "pin-shadow.png'} ";
					$jsMarker .= "});";
					$jsMarker .= $this->config['id'] . ".addLayer(trackGPX);";
				break;
				case 'tx_odsosm_vector':
					$vData = json_decode($item['data']);
					$jsMarker .= "poly = new L.Polygon([";
					$isFirst = true;
					foreach($vData->{'geometry'}->{'coordinates'}[0] as $coord) {
						if (!$isFirst)
							$jsMarker .= ",";
						else
							$isFirst = false;
						$jsMarker .= "[" . $coord[1] . ", " . $coord[0] . "]";
					}
					$jsMarker .= "], {});";
					$jsMarker .= $this->config['id'] . ".addLayer(poly);";
				break;
		}
		return $jsMarker;
	}
}
?>
