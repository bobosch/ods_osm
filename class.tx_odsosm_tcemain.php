<?php
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm') . 'class.tx_odsosm_div.php');

class tx_odsosm_tcemain {
	var $lon=array();
	var $lat=array();

	// ['t3lib/class.t3lib_tcemain.php']['processDatamapClass']
	function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, $obj){
	}

	// ['t3lib/class.t3lib_tcemain.php']['processDatamapClass']
	function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, $obj) {
		switch($table){
			case 'fe_users':
			case 'tt_address':
				$config=tx_odsosm_div::getConfig(array('autocomplete'));
				$field=$config['fieldnames'][$table];

				// Search coordinates
				if($config['autocomplete'] && ($fieldArray['zip'] || $fieldArray['city'] || $fieldArray['address'])){
					$address=$obj->datamap[$table][$id];
					if($config['autocomplete']==2 || floatval($address[$field['lon']])==0){
						$ll=tx_odsosm_div::updateAddress($address);
						if($ll){
							$fieldArray[$field['lon']]=sprintf($field['format'],$address['lon']);
							$fieldArray[$field['lat']]=sprintf($field['format'],$address['lat']);
							if($address['street']){
								$fieldArray['address']=$address['street'];
								if($address['housenumber']) $fieldArray['address'].=' '.$address['housenumber'];
							}
							if($address['zip']) $fieldArray['zip']=$address['zip'];
							if($address['city']) $fieldArray['city']=$address['city'];
							if($address['state'] && $table=='tt_address') $fieldArray['region']=$address['state'];
							if($address['country']) $fieldArray['country']=$address['country'];
						}
					}
				}
				break;

			case 'tx_odsosm_track':
				$filename=PATH_site.'uploads/tx_odsosm/'.$fieldArray['file'];
				if($fieldArray['file'] && file_exists($filename)){
					require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm','res/geoPHP/geoPHP.inc');
					$polygon = geoPHP::load(file_get_contents($filename),pathinfo($filename,PATHINFO_EXTENSION));
					$box = $polygon->getBBox();
					$fieldArray['min_lon']=sprintf('%01.6f',$box['minx']);
					$fieldArray['min_lat']=sprintf('%01.6f',$box['miny']);
					$fieldArray['max_lon']=sprintf('%01.6f',$box['maxx']);
					$fieldArray['max_lat']=sprintf('%01.6f',$box['maxy']);
				}
				break;

			case 'tx_odsosm_marker':
				if($fieldArray['icon'] && file_exists(PATH_site.'uploads/tx_odsosm/'.$fieldArray['icon'])){
					$size=getimagesize(PATH_site.'uploads/tx_odsosm/'.$fieldArray['icon']);
					$fieldArray['size_x']=$size[0];
					$fieldArray['size_y']=$size[1];
					$fieldArray['offset_x']=-round($size[0]/2);
					$fieldArray['offset_y']=-$size[1];
				}
				break;

			case 'tx_odsosm_vector':
				if($fieldArray['data']){
					$this->lon=array();
					$this->lat=array();

					$vector=json_decode($fieldArray['data']);
					foreach($vector->geometry->coordinates[0] as $coordinates){
						$this->lon[]=$coordinates[0];
						$this->lat[]=$coordinates[1];
					}
				}
				$fieldArray['min_lon']=sprintf('%01.6f',min($this->lon));
				$fieldArray['min_lat']=sprintf('%01.6f',min($this->lat));
				$fieldArray['max_lon']=sprintf('%01.6f',max($this->lon));
				$fieldArray['max_lat']=sprintf('%01.6f',max($this->lat));
				break;
		}
	}
}
?>