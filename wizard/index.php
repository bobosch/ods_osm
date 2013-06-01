<?php
$BACK_PATH = '../../../../typo3/';
define('TYPO3_MOD_PATH', '../typo3conf/ext/ods_osm/wizard/');
$MCONF['name']='xMOD_tx_odsosm_wizard';
$MCONF['access']='user,group';
$MCONF['script']='index.php';

require_once ($BACK_PATH.'init.php');
require_once ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:ods_osm/wizard/locallang.xml');

require_once(PATH_t3lib."class.t3lib_scbase.php");
require_once(t3lib_extMgm::extPath('ods_osm').'class.tx_odsosm_common.php');
require_once(t3lib_extMgm::extPath('ods_osm').'class.tx_odsosm_openlayers.php');
require_once(t3lib_extMgm::extPath('ods_osm').'class.tx_odsosm_div.php');

class tx_odsosm_wizard extends t3lib_SCbase {
	// Internal, static: GPvars
	var $P; // Wizard parameters, coming from TCEforms linking to the wizard.
	var $config;

	/**
	* Main function of the module. Write the content to $this->content
	*
	* @return   the wizard
	*/
	function main(){
		global $BE_USER,$BACK_PATH;

		// Draw the header.
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $BACK_PATH;

		// GPvars:
		$this->P=t3lib_div::_GP('P');
			
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id) || ($BE_USER->user["uid"] && !$this->id))    {
			if (($BE_USER->user['admin'] && !$this->id) || ($BE_USER->user["uid"] && !$this->id)) {
				$this->moduleContent();
			}
		}
	}

	/**
	* Outputting the accumulated content to screen
	*
	* @return	void
	*/
	function printContent(){
		echo $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		echo $this->content;
		echo $this->doc->endPage();
	}

  function moduleContent(){
		global $LANG;

		$this->config=unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ods_osm']);
		$this->config['id']='map';
		$this->config['layer']=1;
		$this->config['mouse_navigation']=true;
		$this->config['show_pan_zoom']=1;

		switch($this->P['table']){
			case 'tx_odsosm_vector':
				$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('(max_lat+min_lat)/2 AS lat,(max_lon+min_lon)/2 AS lon',$this->P['table'],'uid='.intval($this->P['uid']));
				$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$row['zoom']=15;
			break;
			default:
				$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_odsosm_lat AS lat,tx_odsosm_lon AS lon',$this->P['table'],'uid='.intval($this->P['uid']));
				$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$row['zoom']=15;
			break;
		}

		if(floatval($row['lon'])==0){
			$row['lon']=$this->config['default_lon'];
			$row['lat']=$this->config['default_lat'];
			$row['zoom']=$this->config['default_zoom'];
		}

		// Library
		$library=t3lib_div::makeInstance('tx_odsosm_openlayers');
		$library->init($this->config);
		$library->doc=$this->doc;

		// Layer
		$layers=$GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,javascript,javascript_include','tx_odsosm_layer','uid IN ('.$this->config['layer'].')');
		$jsMainLayer=$library->getMainLayers($layers,$GLOBALS['BACK_PATH'].'../');

		// Include CSS
		// $this->doc->getPageRenderer()->addCssFile('../res/openlayers/theme/default/style.css','stylesheet','all','',false);

		// Include JS
		$library->getMapCore($GLOBALS['BACK_PATH'].'../');

		// Action
		switch(t3lib_div::_GP('mode')){
			case 'vector':
				$action=$this->getJSvectors();
			break;
			default:
				$action=$this->getJScoordinates();
			break;
		}

		$this->doc->JScode.="
<script type=\"text/javascript\">
	var map; //complex object of type OpenLayers.Map

".$action."

	function map(){
		".$library->getMapMain()."
		".$jsMainLayer."
		".$library->getMapCenter($row['lat'],$row['lon'],$row['zoom'])."
		mapAction();
	}
</script>
";

		$this->content.='<div style="position:absolute;width:100%;height:100%;" id="map"></div><script type="text/javascript">map();</script>';
	}

	function getJScoordinates(){
		return "
	OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {                
		defaultHandlerOptions: {
			'single': true,
			'double': false,
			'pixelTolerance': 0,
			'stopSingle': false,
			'stopDouble': false
		},
		initialize: function(options) {
			this.handlerOptions = OpenLayers.Util.extend(
				{}, this.defaultHandlerOptions
			);
			OpenLayers.Control.prototype.initialize.apply(
				this, arguments
			); 
			this.handler = new OpenLayers.Handler.Click(
				this, {
					'click': this.onClick
				}, this.handlerOptions
			);
		}, 
		onClick: getCoordinates
	});

	function mapAction(){
		map.addControl(new OpenLayers.Control.MousePosition());
		var control = new OpenLayers.Control.Click({
			handlerOptions: {
				'single': true
			}
		});
		map.addControl(control);
		control.activate();
	}

	function getCoordinates(evt){
		var pixel = new OpenLayers.Pixel(evt.xy.x,evt.xy.y);
		var lonlat = map.getLonLatFromPixel(pixel);
		var lonlatGCS = OpenLayers.Layer.SphericalMercator.inverseMercator(lonlat.lon, lonlat.lat);
		".$this->getJSsetField('lonlatGCS.lon')."
		".$this->getJSsetField('lonlatGCS.lat',array('lon'=>'lat'))."
		window.opener.focus();
		close();
	}
";
	}

	function getJSvectors(){
		return "
	function mapAction(){
		var vectors = new OpenLayers.Layer.Vector('Vector Layer');
		map.addControl(new OpenLayers.Control.MousePosition());
		map.addControl(new OpenLayers.Control.EditingToolbar(vectors));
		var options = {
			hover: true,
			onSelect: getVectors
		};
		var control = new OpenLayers.Control.SelectFeature(vectors, options);
		map.addControl(control);
		map.addLayer(vectors);
		control.activate();
	}

	function getVectors(feature){
		var format = new OpenLayers.Format.GeoJSON({
			'internalProjection': map.baseLayer.projection,
			'externalProjection': new OpenLayers.Projection('EPSG:4326')
		});
		var str = format.write(feature);
		".$this->getJSsetField('str')."
		window.opener.focus();
		close();
	}
";
	}

	function getJSsetField($valueString,$replace=array()){
		$replace_hr=$replace;
		$replace_hr['_hr']='';
		return "
		window.opener.document.editform['".strtr($this->P['itemName'],$replace_hr)."'].value=".$valueString.";
		window.opener.document.editform['".strtr($this->P['itemName'],$replace)."'].value=".$valueString.";
		window.opener.".strtr($this->P['fieldChangeFunc']['TBE_EDITOR_fieldChanged'],$replace);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ods_osm/wizard/index.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ods_osm/wizard/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_odsosm_wizard');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
