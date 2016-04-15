<?php
class tx_odsosm_common {
	public $cObj; // Must set from instantiating class

	protected $config;
	protected $script;
	protected $scripts=array();

	// Implement these functions
	public function getMapCore($backpath=''){}
	public function getMapMain(){}
	public function getMapCenter($lat,$lon,$zoom){}
	protected function getLayer($layer,$i,$backpath=''){}
	protected function getMarker($item,$table){}

	public function init($config) {
		$this->config=$config;
	}

	public function getMap($layers,$markers,$lon,$lat,$zoom){
		$this->getMapCore();

		$this->script="
			".$this->getMapMain()."
			".$this->getMainLayers($layers)."
			".$this->getMapCenter($lat,$lon,$zoom)."
			".$this->getMarkers($markers);

		if($this->config['show_layerswitcher']){
			$this->script.=$this->getLayerSwitcher();
		}

		tx_odsosm_div::addJsFiles($this->scripts);

		return $this->getHtml();
	}

	public function getMainLayers($layers,$backpath=''){
		// Main layer
		$i=0;
		$jsMainLayer='';
		foreach($layers as $layer){
			$jsMainLayer.=$this->getLayer($layer,$i,$backpath);
			$i++;
		}
		tx_odsosm_div::addJsFiles($this->scripts);
		return $jsMainLayer;
	}

	public function getScript(){
		return $this->script;
	}

	protected function getMarkers($markers){
		$jsMarker='';
		foreach($markers as $table=>$items){
			foreach($items as $item){
				$jsMarker.=$this->getMarker($item,$table);
			}
		}
		return $jsMarker;
	}

	protected function getLayerSwitcher(){
		return '';
	}

	protected function getHtml(){
		return '<div style="width:'.$this->config['width'].';height:'.$this->config['height'].';" id="'.$this->config['id'].'"></div>';
	}
}
?>