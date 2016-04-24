<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Robert Heel <typo3@bobosch.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm').'class.tx_odsosm_common.php');
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm').'class.tx_odsosm_div.php');


/**
 * Plugin 'Openstreetmap' for the 'ods_osm' extension.
 *
 * @author	Robert Heel <typo3@bobosch.de>
 * @package	TYPO3
 * @subpackage	tx_odsosm
 */
class tx_odsosm_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
	var $prefixId      = 'tx_odsosm_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_odsosm_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'ods_osm';	// The extension key.
	var $uploadPath    = 'uploads/tx_odsosm/';
	var $pi_checkCHash = true;

	var $config;
	var $hooks;
	var $lats=array();
	var $lons=array();

	protected $library;

	function init($conf){
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin

		$this->hooks=array();
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['class.tx_odsosm_pi1.php'])){
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['class.tx_odsosm_pi1.php'] as $classRef){
				$this->hooks[]=&\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
			}
		}

		/* --------------------------------------------------
			Configuration (order of priority)
			- FlexForm
			- TypoScript
			- Extension
		-------------------------------------------------- */

		$flex=array();
		$options=array('cluster', 'height', 'lat', 'layer', 'leaflet_layer', 'library', 'lon', 'marker', 'marker_popup_initial', 'mouse_navigation', 'openlayers_layer', 'openlayers3_layer', 'position', 'show_layerswitcher', 'show_scalebar', 'show_pan_zoom', 'show_popups', 'static_layer', 'use_coords_only_nomarker', 'width', 'zoom');
		foreach($options as $option){
			$value=$this->pi_getFFvalue($this->cObj->data['pi_flexform'],$option,'sDEF');
			if($value){
				switch($option){
					case 'lat':
					case 'lon':
						if($value!=0) $flex[$option]=$value;
						break;
					case 'marker':
					case 'marker_popup_initial':
						$flex[$option]=$this->splitGroup($value,'tt_address');
						break;
					default:
						$flex[$option]=$value;
						break;
				}
			}
		}
		if($flex['library']) $flex['layer']=$flex[$flex['library'].'_layer'];

		$this->config=array_merge(tx_odsosm_div::getConfig(),$conf,$flex);
		if(!is_array($this->config['marker'])) $this->config['marker']=array();
		if(is_array($conf['marker.'])){
			foreach($conf['marker.'] as $name=>$value){
				if(is_string($value) && !empty($value)) {
					if(!is_array($this->config['marker'][$name])) $this->config['marker'][$name]=array();
					$this->config['marker'][$name]=array_merge($this->config['marker'][$name],explode(',',$value));
				}
			}
		}

		$this->config['layer']=explode(',',$this->config['layer']);

		if(is_numeric($this->config['height'])) $this->config['height'].='px';
		if(is_numeric($this->config['width'])) $this->config['width'].='px';

		if($this->config['show_layerswitcher']){
			$this->config['layers_visible']=array();
		}else{
			$this->config['layers_visible']=$this->config['layer'];
		}

		if($this->config['external_control']){
			if(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('lon')) $this->config['lon']=\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('lon');
			if(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('lat')) $this->config['lat']=\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('lat');
			if(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('zoom')) $this->config['zoom']=\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('zoom');
			if(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('layers')) $this->config['layers_visible']=explode(',',\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('layers'));
			if(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('records')) $this->config['marker']=$this->splitGroup(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('records'),'tt_address');
		}

		$this->config['id']='osm_'.$this->cObj->data['uid'];
		$this->config['marker']=$this->extractGroup($this->config['marker']);
		
		// Show this marker's popup intially
		if(is_array($this->config['marker_popup_initial'])){
			foreach($this->config['marker_popup_initial'] as $table=>$records) {
				foreach($records as $uid) {
					if(isset($this->config['marker'][$table][$uid])) {
						$this->config['marker'][$table][$uid]['initial_popup'] = true;
					}
				}
			}
		}

		// Library
		if(empty($this->config['library'])) $this->config['library']='leaflet';
		require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm').'class.tx_odsosm_'.$this->config['library'].'.php');
		$this->library=\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_odsosm_'.$this->config['library']);
		$this->library->init($this->config);
		$this->library->cObj=$this->cObj;
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->init($conf);

		if($this->config['marker'] || $this->config['no_marker']){
			$content=$this->getMap();
		}

		return $this->pi_wrapInBaseClass($content);
	}

	function splitGroup($group,$default=''){
		$groups=explode(',',$group);
		foreach($groups as $group){
			$item=\TYPO3\CMS\Core\Utility\GeneralUtility::revExplode('_',$group,2);
			if(count($item)==1){
				$record_ids[$default][]=$item[0];
			}else{
				$record_ids[$item[0]][]=$item[1];
			}
		}
		return($record_ids);
	}

	function extractGroup($record_ids){
		$tables = tx_odsosm_div::getTableConfig();

		if(count($record_ids)==0) {
			$record_ids['pages']=array($GLOBALS['TSFE']->id);
		}

		// get pages
		if(!empty($record_ids['pages'])){
			$pids=implode(',',$record_ids['pages']);
			foreach(array_keys($tables) as $table){
				if($table != 'tt_content') {
					$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', $table, 'pid IN (' . $pids . ')' . tx_odsosm_div::getWhere($table,$this->cObj));
					while($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
						$record_ids[$table][]=$row['uid'];
					}
				}
			}
		}

		// get records
		$records=array();
		foreach($record_ids as $table=>$items){
			$tc = $tables[$table];
			foreach($items as $item){
				$item=intval($item);
				$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid=' . $item . tx_odsosm_div::getWhere($table,$this->cObj));
				$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$row=tx_odsosm_div::getOverlay($table,$row);
				if($row) {
					// Group with relation to a field
					if(is_array($tc['FIND_IN_SET'])) {
						foreach($tc['FIND_IN_SET'] as $t => $f) {
							$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $t, 'FIND_IN_SET("' . $item . '",' . $f . ')' . tx_odsosm_div::getWhere($t, $this->cObj));
							while($r=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
								$records[$t][$r['uid']]=$r;
								$records[$t][$r['uid']]['group_uid']=$table . '_' . $row['uid'];
								$records[$t][$r['uid']]['group_title']=$row['title'];
								$records[$t][$r['uid']]['group_description']=$row['description'];
								$records[$t][$r['uid']]['tx_odsosm_marker']=$row['tx_odsosm_marker'];
								$records[$t][$r['uid']]['longitude']=$r[$tables[$t]['lon']];
								$records[$t][$r['uid']]['latitude']=$r[$tables[$t]['lat']];
							}
						}
					}

					// Group with mm relation
					if(is_array($tc['MM'])) {
						foreach($tc['MM'] as $t => $f) {
							$res=$GLOBALS['TYPO3_DB']->exec_SELECT_mm_query($t . '.*', $f['local'], $f['mm'], $f['foreign'], 'AND ' . $table . '.uid=' . $item . tx_odsosm_div::getWhere($t,$this->cObj));
							while($r=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
								$records[$t][$r['uid']]=$r;
								$records[$t][$r['uid']]['group_uid']=$table . '_' . $row['uid'];
								$records[$t][$r['uid']]['group_title']=$row['title'];
								$records[$t][$r['uid']]['group_description']=$row['description'];
								$records[$t][$r['uid']]['tx_odsosm_marker']=$row['tx_odsosm_marker'];
								$records[$t][$r['uid']]['longitude']=$r[$tables[$t]['lon']];
								$records[$t][$r['uid']]['latitude']=$r[$tables[$t]['lat']];
							}
						}
					}

					// Marker
					if(isset($tc['lon'])) {
						$records[$table][$item]=$row;
						$records[$table][$item]['longitude']=$row[$tc['lon']];
						$records[$table][$item]['latitude']=$row[$tc['lat']];
					}

					// Special element
					if($tc===true) {
						$records[$table][$item]=$row;
					}
				}
			}
		}

		// Hook to change records
		foreach($this->hooks as $hook){
			if(method_exists($hook,'changeRecords')){
				$hook->changeRecords($records,$record_ids,$this);
			}
		}

		// get lon&lat
		foreach($records as $table=>$items){
			foreach($items as $uid=>$row){
				switch($table){
					case 'tx_odsosm_track':
					case 'tx_odsosm_vector':
						if($row['min_lon']){
							$this->lons[]=floatval($row['min_lon']);
							$this->lats[]=floatval($row['min_lat']);
							$this->lons[]=floatval($row['max_lon']);
							$this->lats[]=floatval($row['max_lat']);
						}else{
							unset($records[$table][$uid]);
						}
						break;
					default:
						$this->lons[]=floatval($row['longitude']);
						$this->lats[]=floatval($row['latitude']);
						break;
				}
			}
		}

		// No markers
		if(count($this->lons)==0){
			if($this->config['no_marker']==1){
				$this->lons[]=$this->config['lon'];
				$this->lats[]=$this->config['lat'];
			}
		}

		return($records);
	}

	function getMap(){
		/* ==================================================
			Marker
		================================================== */
		// Get icon records
		$icons = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_odsosm_marker', '1' . tx_odsosm_div::getWhere('tx_odsosm_marker',$this->cObj), '', '', '', 'uid');

		// Prepare markers
		$markers=$this->config['marker'];
		$local_cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
		foreach($markers as $table=>&$items){
			foreach($items as $key=>&$item){
				$popup = is_string($this->config['popup.'][$table]) && is_array($this->config['popup.'][$table.'.']) && $this->config['show_popups'];
				$icon = is_string($this->config['icon.'][$table]) && is_array($this->config['icon.'][$table.'.']);
				if($popup || $icon){
					$local_cObj->start($item,$table);
				}

				// Add popup information
				if($popup){
					$item['popup']=$local_cObj->cObjGetSingle($this->config['popup.'][$table],$this->config['popup.'][$table.'.']);
				}
				
				// Add icon information
				if($item['tx_odsosm_marker']) {
					$item['tx_odsosm_marker'] = $icons[$item['tx_odsosm_marker']];
					$item['tx_odsosm_marker']['icon'] = 'uploads/tx_odsosm/' . $item['tx_odsosm_marker']['icon'];
					$item['tx_odsosm_marker']['type'] = 'image';
				} elseif($icon) {
					$conf = $this->config['icon.'][$table.'.'];
					$html = $local_cObj->cObjGetSingle($this->config['icon.'][$table],$this->config['icon.'][$table.'.']);
					if($this->config['icon.'][$table] == 'IMAGE') {
						$info = $GLOBALS['TSFE']->lastImageInfo;
						$item['tx_odsosm_marker'] = array(
							'icon' => $info['origFile'],
							'type' => 'image',
							'size_x' => $info[0],
							'size_y' => $info[1],
							'offset_x' => -$info[0]/2,
							'offset_y' => -$info[1],
						);
					} elseif($this->config['icon.'][$table] == 'TEXT') {
						$item['tx_odsosm_marker'] = array(
							'icon' => $html,
							'type' => 'html',
							'size_x' => $conf['size_x'],
							'size_y' => $conf['size_y'],
							'offset_x' => $conf['offset_x'],
							'offset_y' => $conf['offset_y'],
						);
					}
				}
			}
		}

		/* ==================================================
			Layers
		================================================== */
		$layers=$GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_odsosm_layer','uid IN ('.implode(',',$this->config['layer']).')'.$this->cObj->enableFields('tx_odsosm_layer'),'','FIELD(uid,'.implode(',',$this->config['layer']).')','','uid');
		// set visible flag
		foreach($this->config['layers_visible'] as $key){
			if($layers[$key]) $layers[$key]['visible']=true;
		}

		/* ==================================================
			Map center
		================================================== */
		if($this->config['lon']==0 || $this->config['use_coords_only_nomarker']){
			$lon=array_sum($this->lons)/count($this->lons);
			$lat=array_sum($this->lats)/count($this->lats);
		}else{
			$lon=floatval($this->config['lon']);
			$lat=floatval($this->config['lat']);
		}
		$zoom=intval($this->config['zoom']);

		/* ==================================================
			Map
		================================================== */
		$content=$this->library->getMap($layers,$markers,$lon,$lat,$zoom);
		$script=$this->library->getScript();
		if($script){
			switch ($this->config['JSlibrary']) {
				case 'jquery':
					$GLOBALS['TSFE']->getPageRenderer()->addJsInlineCode(
						$this->config['id'],
						'$(document).ready(function() {' . $script . '});'
					);
					break;
				default:
					$GLOBALS['TSFE']->getPageRenderer()->addJsInlineCode(
						$this->config['id'],
						'document.addEventListener("DOMContentLoaded", function(){' . $script . '}, false);'
					);
					break;
			}
		}

		return($content);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ods_osm/pi1/class.tx_odsosm_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ods_osm/pi1/class.tx_odsosm_pi1.php']);
}
?>
