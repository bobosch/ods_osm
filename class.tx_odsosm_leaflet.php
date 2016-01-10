<?php
class tx_odsosm_leaflet extends tx_odsosm_common {
	protected $layers;
	protected $path_res;
	protected $path_leaflet;

	public function getMapCore($backpath=''){
		$this->path_res=($backpath ? $backpath : $GLOBALS['TSFE']->absRefPrefix).t3lib_extMgm::siteRelPath('ods_osm').'res/';
		$this->path_leaflet=($this->config['local_js'] ? $this->path_res.'leaflet/' : 'http://cdn.leafletjs.com/leaflet-0.7.3/');
		$GLOBALS['TSFE']->getPageRenderer()->addCssFile($this->path_leaflet.'leaflet.css');
		$this->scripts=array($this->path_leaflet.'leaflet.js');
	}

	public function getMapMain(){
		$controls = array();
		if($this->config['show_scalebar']) $controls['scalebar']='new L.control.scale()';

		$vars='';
		foreach($controls as $var=>$obj){
			$vars .= "\n\t\t\t".$this->config['id'].'.addControl('.$obj.");";
		}

		$jsMain=
			$this->config['id']."=new L.Map('".$this->config['id']."');
			L.Icon.Default.imagePath='".$this->path_leaflet."images';"
			.$vars;
		if($this->config['cluster']){
			$GLOBALS['TSFE']->getPageRenderer()->addCssFile($this->path_res.'leaflet-markercluster/MarkerCluster.css');
			$GLOBALS['TSFE']->getPageRenderer()->addCssFile($this->path_res.'leaflet-markercluster/MarkerCluster.Default.css');
			$this->scripts['leaflet-markercluster']=$this->path_res.'leaflet-markercluster/leaflet.markercluster.js';
			$jsMain.=$this->config['id'].'_c=new L.MarkerClusterGroup({maxClusterRadius:80});';
			$jsMain.=$this->config['id'].'.addLayer('.$this->config['id'].'_c);';
		}

		return $jsMain;
	}

	protected function getLayer($layer,$i,$backpath=''){
		$var=preg_replace('/[^a-z]/','',strtolower($layer['title']));
		$this->layers[$layer['overlay']][$layer['title']]=$var;

		if($layer['javascript_leaflet']){
			$jsLayer=strtr($layer['javascript_leaflet'],array(
				'###STATIC_SCRIPT###'=>$this->config['static_script'],
				'###TITLE###'=>$layer['title'],
				'###VISIBLE###'=>"'visibility':".($layer['visible'] ? 'true' : 'false'),
			)).";\n";
		}elseif($layer['tile_url']){
			$options=array();
			if($layer['max_zoom']) $options['maxZoom']=$layer['max_zoom'];
			if($layer['subdomains']) $options['subdomains']=$layer['subdomains'];
			if($layer['attribution']) $options['attribution']=$layer['attribution'];

			$jsLayer = 'new L.TileLayer(\''.$layer['tile_url'].'\','.json_encode($options).');';
		}

		$jsLayer = "\n\t\t\tvar ".$var.' = '.$jsLayer;

		// only show one base layer on the map
		if($i == 0)
			$jsLayer .= "\n\t\t\t".$this->config['id'].'.addLayer('.$var.');';

		return $jsLayer;
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
				$overlay[]='\''.$title.'\':'.$var;
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
		$jsMarker = '';
		$jsElementVar = $table . '_' . $item['uid'];
		$jsLayerVar = $jsElementVar;
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
				$jsMarker.='var ' . $jsElementVar . ' = new L.Marker([' . $item['tx_odsosm_lat'] . ', ' . $item['tx_odsosm_lon'] . '], {' . implode(',', $markerOptions) . "});\n";
				// Add group to layer switch
				if($item['group_title']) {
					if(!in_array($item['group_uid'], $this->layers[1])) {
						$this->layers[1]['<img src="'.$icon.'"> ' . $item['group_title']] = $item['group_uid'];
						$jsMarker .= 'var '.$item['group_uid'].' = L.layerGroup([' . $jsElementVar . "]);\n";
						$jsLayerVar = $item['group_uid'];
					} else {
						$jsMarker .= $item['group_uid'].'.addLayer(' . $jsElementVar . ");\n";
						$jsLayerVar = false;
					}
				}
				break;
			case 'tx_odsosm_track':
				$path = t3lib_extMgm::siteRelPath('ods_osm') .'res/';
				// Add tracks to layerswitcher
				$this->layers[1][$item['title']] = $jsElementVar;

				switch(strtolower(pathinfo($item['file'], PATHINFO_EXTENSION))){
					case 'kml':
						// include javascript file for KML support
						$this->scripts['leaflet-plugins']=$path .'leaflet-plugins/layer/vector/KML.js';

						$jsMarker .= 'var ' . $jsElementVar .' = new L.KML(';
						$jsMarker .= '"' .$GLOBALS['TSFE']->absRefPrefix .'uploads/tx_odsosm/' .$item['file'] .'"';
						$jsMarker .= ");\n";
						break;
					case 'gpx':
						// include javascript file for GPX support
						$this->scripts['leaflet-gpx']=$path.'leaflet-gpx/gpx.js';

						$jsMarker .= 'var ' . $jsElementVar .' = new L.GPX(';
						$jsMarker .= '"' .$GLOBALS['TSFE']->absRefPrefix .'uploads/tx_odsosm/' .$item['file'] .'"';
						$jsMarker .= ", { color: '" .$item['color'] ."', clickable: false";
						$jsMarker .= ", marker_options: { startIconUrl: '" .$path ."leaflet-gpx/pin-icon-start.png'";
						$jsMarker .= ", endIconUrl: '" .$path ."leaflet-gpx/pin-icon-end.png'";
						$jsMarker .= ", shadowUrl: '" .$path ."leaflet-gpx/pin-shadow.png'}";
						$jsMarker .= "});\n";
						break;
				}
				break;
			case 'tx_odsosm_vector':
				$jsMarker .= 'var ' . $jsElementVar . ' = new L.geoJson(' . $item['data'] . ');' . "\n";
				break;
			default:
				$jsElementVar = false;
				break;
		}

		if($jsElementVar) {
			if($item['popup']) {
				$jsMarker .= $jsElementVar . '.bindPopup("'.strtr($item['popup'], $this->escape_js)."\");\n";
				if ($item['initial_popup']) {
					$jsMarker .= $jsElementVar . ".openPopup();\n";
				}
			}

			if($jsLayerVar) {
				$jsMarker .= $this->config['id'] . ($this->config['cluster'] ? '_c':'') . '.addLayer(' . $jsLayerVar . ');' . "\n";
			}
		}
		
		return $jsMarker;
	}
}
?>
