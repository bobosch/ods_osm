<?php
class tx_odsosm_common {
	public $cObj;
	public $markers;

	protected $config;
	protected $escape_js=array(
		"\r\n"=>"<br />",
		"\n"=>"<br />",
		"\r"=>"<br />",
		"'"=>"\'",
		'"'=>'\"',
	);
	protected $script=false;

	public function init($config) {
		$this->config=$config;
	}

	public function getMainLayers($layers,$backpath=''){
		// Main layer
		$i=0;
		$jsMainLayer='';
		$scripts=array();
		foreach($layers as $layer){
			$jsMainLayer.=$this->getLayer($layer,$i,$backpath);
			$i++;
		}
		tx_odsosm_div::addJsFiles($scripts);
		return $jsMainLayer;
	}

	public function getMarkers($markers){
		$jsMarker='';
		foreach($markers as $table=>$items){
			foreach($items as $item){
				$jsMarker.=$this->getMarker($item,$table);
			}
		}
		return $jsMarker;
	}

	public function getScript(){
		return $this->script;
	}

	public function getHtml(){
		return '<div style="width:'.$this->config['width'].';height:'.$this->config['height'].';" id="'.$this->config['id'].'"></div>';
	}
}
?>