<?php
class tx_odsosm_openlayers extends tx_odsosm_common {
	protected $scripts=array();
	protected $group_titles=array();

	public function getMap($layers,$markers,$lon,$lat,$zoom){
		$this->script="
			".$this->getMapMain()."
			".$this->getMainLayers($layers)."
			".$this->getMapCenter($lat,$lon,$zoom)."
			".$this->getMarkers($markers)."
			".$this->getMarkerLayer();

 		$this->getMapCore();

		/* ==================================================
			Map container
		================================================== */
		$content='<div style="width:'.$this->config['width'].';height:'.$this->config['height'].';" id="'.$this->config['id'].'"></div>';
		if($this->config['layerswitcher.']['div']) $content.='<div id="'.$this->config['id'].'_layerswitcher" class="olControlLayerSwitcher"></div>';

		return $content;
	}

	public function getMapCore($backpath=''){
		$path=$backpath.t3lib_extMgm::siteRelPath('ods_osm').'res/';
		$scripts=array(
			($this->config['path_openlayers'] ? $this->config['path_openlayers'] : ($this->config['local_js'] ? $path.'openlayers' : 'http://www.openlayers.org/api/2.11')).'/OpenLayers.js',
			$path.'main.js',
		);
		tx_odsosm_div::addJsFiles(array_merge($scripts,$this->scripts));
	}

	public function getMapMain(){
		return("
			oLayerSwitcher=".($this->config['show_layerswitcher'] ? "new OpenLayers.Control.LayerSwitcher({".($this->config['layerswitcher.']['div'] ? "'div':OpenLayers.Util.getElement('".$this->config['id']."_layerswitcher')" : "").$this->config['layerswitcher.']['options']."})" : "").";
			".$this->config['id']."=new OpenLayers.Map('".$this->config['id']."',{
				controls:[".
					($this->config['mouse_navigation'] ? "new OpenLayers.Control.Navigation()," : "").
					($this->config['show_pan_zoom'] ? "new OpenLayers.Control.PanZoom".($this->config['show_pan_zoom']==1 ? 'Bar' : '')."()," : "").
					"oLayerSwitcher,".
					($this->config['show_scalebar'] ? "new OpenLayers.Control.ScaleLine()," : "").
					"new OpenLayers.Control.Attribution()],
				maxExtent:new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
				maxResolution:156543.0399,
				numZoomLevels:19,
				units:'m',
				projection:new OpenLayers.Projection('EPSG:900913'),
				displayProjection:new OpenLayers.Projection('EPSG:4326')
			});\n".
			($this->config['show_layerswitcher']==2 ? "oLayerSwitcher.maximizeControl();\n" : "")
		);
	}

	public function getLayer($layer,$i,$backpath=''){
		if($layer['javascript_include']){
			$javascript_include=strtr($layer['javascript_include'],array(
				'###STATIC_SCRIPT###'=>$this->config['static_script'],
			));
			$parts=parse_url($javascript_include);
			$filename=basename($parts['path']);
			if($parts['scheme']){
				$script=($this->config['local_js'] && file_exists($backpath.'typo3conf/ext/ods_osm/res/layers/'.$filename)) ? $GLOBALS['TSFE']->absRefPrefix.$backpath.'typo3conf/ext/ods_osm/res/layers/'.$filename : $javascript_include;
			}else{
				$script=$GLOBALS['TSFE']->absRefPrefix.$backpath.$javascript_include;
			}
			// Include javascript only once if different layers use the same javascript
			$this->scripts[$filename]=$script;
		}
		if($layer['javascript']){
			$jsMainLayer=$this->config['id'].".addLayer(".strtr($layer['javascript'],array(
				'###TITLE###'=>$layer['title'],
				'###VISIBLE###'=>"'visibility':".($layer['visible'] ? 'true' : 'false'),
			)).");\n";
		}
		if(!$layer['overlay'] && $layer['visible']){
			$jsMainLayer.=$this->config['id'].'.setBaseLayer('.$this->config['id'].'.layers['.$i."]);\n";
		}

		return $jsMainLayer;
	}

	public function getMapCenter($lat,$lon,$zoom){
		return "mapCenter(".$this->config['id'].",".floatval($lat).",".floatval($lon).",".intval($zoom).");";
	}

	public function getMarker($item,$table){
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
				if(!in_array($item['group_title'], $this->group_titles)) {
					$this->group_titles[]=$item['group_title'];
					$jsMarker.="var layerMarkers_".array_search($item['group_title'],$this->group_titles)."=new OpenLayers.Layer.Markers('<img src=\"".$icon."\" /> ".$item['group_title']."');\n";
				}
				$jsMarker.="mapMarker(".$this->config['id'].",layerMarkers_".array_search($item['group_title'],$this->group_titles).",".$item['tx_odsosm_lat'].",".$item['tx_odsosm_lon'].",'".$icon."',".$marker['size_x'].",".$marker['size_y'].",".$marker['offset_x'].",".$marker['offset_y'].",'".strtr($item['popup'],$this->escape_js)."',".intval($this->config['show_popups']).",".intval($item['initial_popup']).");\n";
			break;
			case 'tx_odsosm_track':
				$jsMarker.="mapGpx(".$this->config['id'].",'".$GLOBALS['TSFE']->absRefPrefix.'uploads/tx_odsosm/'.$item['file']."','".$item['title']."','".$item['color']."',".$item['width'].");\n";
			break;
			case 'tx_odsosm_vector':
				$jsMarker.="mapVector(".$this->config['id'].",'".$item['title']."',".$item['data'].");\n";
			break;
		}
		return $jsMarker;
	}

	public function getMarkerLayer(){
		$jsMarkerLayer='';
		foreach($this->group_titles as $key=>$title) {
			$jsMarkerLayer.= $this->config['id'].".addLayer(layerMarkers_".$key.");\n";
		}
		return $jsMarkerLayer;
	}
}
?>
