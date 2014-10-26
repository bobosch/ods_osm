<?php
class tx_odsosm_openlayers extends tx_odsosm_common {
	protected $group_titles=array();

	public function getMapCore($backpath=''){
		$path=($backpath ? $backpath : $GLOBALS['TSFE']->absRefPrefix).t3lib_extMgm::siteRelPath('ods_osm').'res/';
		$scripts=array(
			($this->config['path_openlayers'] ? $this->config['path_openlayers'] : ($this->config['local_js'] ? $path.'OpenLayers' : 'http://www.openlayers.org/api')).'/OpenLayers.js',
			$path.'tx_odsosm_openlayers.js',
		);
		tx_odsosm_div::addJsFiles($scripts);
	}

	public function getMapMain(){
		$controls=array('oAttribution'=>'new OpenLayers.Control.Attribution()');
		if($this->config['show_layerswitcher']) $controls['oLayerSwitcher']="new OpenLayers.Control.LayerSwitcher({".($this->config['layerswitcher.']['div'] ? "'div':OpenLayers.Util.getElement('".$this->config['id']."_layerswitcher')" : "").$this->config['layerswitcher.']['options']."})";
		if($this->config['mouse_navigation']) $controls['oNavigation']="new OpenLayers.Control.Navigation()";
		if($this->config['show_pan_zoom']) $controls['oPanZoom']="new OpenLayers.Control.PanZoom".($this->config['show_pan_zoom']==1 ? 'Bar' : '')."()";
		if($this->config['show_scalebar']) $controls['oScalebar']="new OpenLayers.Control.ScaleLine()";

		$vars='';
		foreach($controls as $var=>$obj){
			$vars.=$var.'='.$obj.";\n";
		}

		return(
			$vars.
			$this->config['id']."=new OpenLayers.Map('".$this->config['id']."',{
				controls:[".implode(',',array_keys($controls))."],
				numZoomLevels:19,
				projection:new OpenLayers.Projection('EPSG:900913'),
				displayProjection:new OpenLayers.Projection('EPSG:4326')
			});\n".
			($this->config['show_layerswitcher']==2 ? "oLayerSwitcher.maximizeControl();\n" : "")
		);
	}
	
	public function getMapCenter($lat,$lon,$zoom){
		return "mapCenter(".$this->config['id'].",".floatval($lat).",".floatval($lon).",".intval($zoom).");";
	}

	protected function getLayerSwitcher(){
		if($this->config['layerswitcher.']['div']) $content.='<div id="'.$this->config['id'].'_layerswitcher" class="olControlLayerSwitcher"></div>';
	}

	protected function getLayer($layer,$i,$backpath=''){
		if($layer['javascript_include']){
			$javascript_include=strtr($layer['javascript_include'],array(
				'###STATIC_SCRIPT###'=>$this->config['static_script'],
			));
			$parts=parse_url($javascript_include);
			$filename=basename($parts['path']);
			if($parts['scheme']){
				$script=$javascript_include;
			}else{
				$script=$GLOBALS['TSFE']->absRefPrefix.$backpath.$javascript_include;
			}
			// Include javascript only once if different layers use the same javascript
			$this->scripts[$filename]=$script;
		}
		if($layer['javascript']){
			$jsMainLayer=$this->config['id'].".addLayer(".strtr($layer['javascript'],array(
				'###STATIC_SCRIPT###'=>$this->config['static_script'],
				'###TITLE###'=>$layer['title'],
				'###VISIBLE###'=>"'visibility':".($layer['visible'] ? 'true' : 'false'),
			)).");\n";
		}elseif($layer['tile_url']){
			// url
			$layer['tile_url']=strtr($layer['tile_url'],array('{x}'=>'${x}','{y}'=>'${y}','{z}'=>'${z}'));
			if(strpos($layer['tile_url'],'{s}')){
				if($layer['subdomains']){
					$subdomains=$layer['subdomains'];
				}else{
					$subdomains='abc';
				}
				$url=array();
				for($i=0;$i<strlen($subdomains);$i++){
					$url[]=strtr($layer['tile_url'],array('{s}'=>substr($subdomains,$i,1)));
				}
			}else{
				$url=$layer['tile_url'];
			}
			
			// options
			$options=array();
 			if($layer['attribution']) $options['attribution']=$layer['attribution'];
			if($layer['max_zoom']) $options['numZoomLevels']=$layer['max_zoom'];
			if($layer['overlay']) {$options['isBaseLayer']=false;$options['transparent']=true;}
			$options['tileOptions']['crossOriginKeyword']=null;
			$options['visibility']=$layer['visible'] ? true : false;

			$params=array(
				'"'.$layer['title'].'"',
				json_encode($url),
				json_encode($options,JSON_NUMERIC_CHECK)
			);
			$jsMainLayer=$this->config['id'].'.addLayer(new OpenLayers.Layer.OSM('.implode(',',$params).'));'."\n";
		}
		if(!$layer['overlay'] && $layer['visible']){
			$jsMainLayer.=$this->config['id'].'.setBaseLayer('.$this->config['id'].'.layers['.$i."]);\n";
		}

		return $jsMainLayer;
	}

	protected function getMarker($item,$table){
		$jsMarker='';
		switch($table){
			case 'fe_users':
			case 'tt_address':
				if($item['tx_odsosm_marker'] && is_array($this->markers[$item['tx_odsosm_marker']])){
					$marker=$this->markers[$item['tx_odsosm_marker']];
					$icon=$GLOBALS['TSFE']->absRefPrefix.'uploads/tx_odsosm/'.$marker['icon'];
				}else{
					$marker=array('size_x'=>21,'size_y'=>25,'offset_x'=>-11,'offset_y'=>-25);
					$icon=$GLOBALS['TSFE']->absRefPrefix.t3lib_extMgm::siteRelPath('ods_osm').'res/marker.png';
				}
				// Add group to layer switch
				if(!in_array($item['group_title'], $this->group_titles)) {
					$this->group_titles[$item['group_uid']]=$item['group_title'];
					$jsMarker.="var layerMarkers_".$item['group_uid']."=new OpenLayers.Layer.Markers('<img src=\"".$icon."\" /> ".$item['group_title']."');\n";
					$jsMarker.=$this->config['id'].'.addLayer(layerMarkers_'.$item['group_uid'].');';
				}
				$jsMarker.="mapMarker(".$this->config['id'].",layerMarkers_".$item['group_uid'].",".$item['tx_odsosm_lat'].",".$item['tx_odsosm_lon'].",'".$icon."',".$marker['size_x'].",".$marker['size_y'].",".$marker['offset_x'].",".$marker['offset_y'].",'".strtr($item['popup'],$this->escape_js)."',".intval($this->config['show_popups']).",".intval($item['initial_popup']).");\n";
			break;
			case 'tx_odsosm_track':
				$jsMarker.="mapGpx(".$this->config['id'].",'".$GLOBALS['TSFE']->absRefPrefix.'uploads/tx_odsosm/'.$item['file']."','".$item['title']."','".$item['color']."',".$item['width'].",".$item['visible'].");\n";
			break;
			case 'tx_odsosm_vector':
				$jsMarker.="mapVector(".$this->config['id'].",'".$item['title']."',".$item['data'].");\n";
			break;
		}
		return $jsMarker;
	}
}
?>
