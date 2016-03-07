<?php
// TYPO3 6.2 compatibility
$LANG->includeLLFile('EXT:ods_osm/wizard/locallang.xml');

require_once(t3lib_extMgm::extPath('ods_osm').'class.tx_odsosm_common.php');
require_once(t3lib_extMgm::extPath('ods_osm').'class.tx_odsosm_openlayers.php');
require_once(t3lib_extMgm::extPath('ods_osm').'class.tx_odsosm_div.php');

class tx_odsosm_wizard extends t3lib_SCbase {
	// Internal, static: GPvars
	var $P; // Wizard parameters, coming from TCEforms linking to the wizard.

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
		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id) || ($BE_USER->user['uid'] && !$this->id))    {
			if (($BE_USER->user['admin'] && !$this->id) || ($BE_USER->user['uid'] && !$this->id)) {
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
		$config=\tx_odsosm_div::getConfig();
		$config['id']='map';
		$config['layer']=1;
		$config['mouse_navigation']=true;
		$config['show_pan_zoom']=1;

		$field=$config['fieldnames'][$this->P['table']];

		switch($this->P['table']){
			case 'tx_odsosm_vector':
				$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('(max_lat+min_lat)/2 AS lat,(max_lon+min_lon)/2 AS lon',$this->P['table'],'uid='.intval($this->P['uid']));
				$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$row['zoom']=15;
				$js='function setBEfield(data) {
					'.$this->getJSsetField($this->P,'data').'
					close();
				}';
				break;
			default:
				$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery($field['lon'] . ' AS lon, ' . $field['lat'] . ' AS lat', $this->P['table'], 'uid=' . intval($this->P['uid']));
				$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$row['zoom']=15;
				$js='function setBEcoordinates(lon,lat) {
					'.$this->getJSsetField($this->P,'lon').'
					'.$this->getJSsetField($this->P,'lat',array($field['lon']=>$field['lat'])).'
					close();
				}';
				break;
		}

		if(floatval($row['lon'])==0){
			$row['lon']=$config['default_lon'];
			$row['lat']=$config['default_lat'];
			$row['zoom']=$config['default_zoom'];
		}

		// Library
		$library=t3lib_div::makeInstance('tx_odsosm_openlayers');
		$library->init($config);
		$library->doc=$this->doc;
		$library->P=$this->P;

		// Layer
		$layers=$GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_odsosm_layer','uid IN ('.$config['layer'].')');

		$this->doc->JScode.='
			<script type="text/javascript">
				'.$library->getMapBE($layers,$this->P['params']['mode'],$row['lat'],$row['lon'],$row['zoom']).'
				'.$js.'
			</script>
		';

		$this->content.='<div style="position:absolute;width:100%;height:100%;" id="map"></div><script type="text/javascript">map();</script>';
	}

	function getJSsetField($P,$valueString,$replace=array()){
		$replace_hr=$replace;
		$replace_hr['_hr']='';
		return "
 		window.opener.document.editform['".strtr($P['itemName'],$replace_hr)."'].value=".$valueString.";
		window.opener.document.editform['".strtr($P['itemName'],$replace)."'].value=".$valueString.";
		window.opener.".strtr($P['fieldChangeFunc']['TBE_EDITOR_fieldChanged'],$replace);
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
