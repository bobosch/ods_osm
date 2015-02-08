<?php
$BACK_PATH = '../../../../typo3/';
define('TYPO3_MOD_PATH', '../typo3conf/ext/ods_osm/wizard/');
$MCONF['name']='xMOD_tx_odsosm_wizard';
$MCONF['access']='user,group';
$MCONF['script']='index.php';

require_once ($BACK_PATH.'init.php');
$LANG->includeLLFile('EXT:ods_osm/wizard/locallang.xml');

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
		$library->P=$this->P;

		// Layer
		$layers=$GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_odsosm_layer','uid IN ('.$this->config['layer'].')');

		$this->doc->JScode.='
<script type="text/javascript">
'.$library->getMapBE($layers,t3lib_div::_GP('mode'),$row['lat'],$row['lon'],$row['zoom']).'
</script>
';

		$this->content.='<div style="position:absolute;width:100%;height:100%;" id="map"></div><script type="text/javascript">map();</script>';
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
