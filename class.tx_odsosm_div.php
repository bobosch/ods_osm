<?php
class tx_odsosm_div {
	function getWhere($table){
		if(is_string($table)){
			$ctrl=$GLOBALS['TCA'][$table]['ctrl'];
			// Enable fields
			$where=$this->cObj->enableFields($table);
			// Version
			$where.=' AND '.$table.'.pid>=0';
			// Translation
			if($ctrl['languageField']){
				$where.=' AND ('.$table.'.'.$ctrl['languageField'].' IN (-1,0)';
				if($GLOBALS['TSFE']->sys_language_content && $ctrl['transOrigPointerField']){
					$where.=' OR ('.$table.'.'.$ctrl['languageField'].'='.intval($GLOBALS['TSFE']->sys_language_content).' AND '.$table.'.'.$ctrl['transOrigPointerField'].'=0)';
				}
				$where.=')';
			}
		}
		return $where;
	}

	function getOverlay($table,$row){
		if(is_string($table) && is_array($row)){
			$ctrl=$GLOBALS['TCA'][$table]['ctrl'];
			// Version
			// - Table has versioning
			// - Current user is in workspace
			// - Versioning is enabled
			if($ctrl['versioningWS'] && $GLOBALS['BE_USER']->workspace && t3lib_extMgm::isLoaded('version')){
				$GLOBALS['TSFE']->sys_page->versionOL($table,$row);
			}
			// Translation
			// - Table has translation
			// - Current language is not default
			// - Translation is enabled
			if($ctrl['languageField'] && $GLOBALS['TSFE']->sys_language_content){
				$row=$GLOBALS['TSFE']->sys_page->getRecordOverlay($table,$row,$GLOBALS['TSFE']->sys_language_content,$GLOBALS['TSFE']->sys_language_contentOL);
			}
		}
		return $row;
	}

	function addJsFiles($scripts){
		if(TYPO3_MODE=='BE'){
// 			$pagerender=$this->doc->getPageRenderer();
			foreach($scripts as $script){
				$this->doc->JScode.='<script src="'.$script.'" type="text/javascript"></script>';
			}
		}else{
			$pagerender=$GLOBALS['TSFE']->getPageRenderer();
			foreach($scripts as $script){
				$pagerender->addJsFile($script,'text/javascript',false);
			}
		}
	}

	function updateAddress(&$address){
		$config=unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ods_osm']);

		$ll=tx_odsosm_div::searchAddress($address,0);
		if(!$ll){
			$ll=tx_odsosm_div::searchAddress($address,$config['geo_service']);
			if($ll) tx_odsosm_div::updateCache($address);
		}
		return $ll;
	}

	function searchAddress(&$address,$service=0){
		$config=unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ods_osm']);
		$ll=false;

		$country=strtoupper(strlen($address['country'])==2 ? $address['country'] : $config['default_country']);

		switch($service){
			case 0: // cache
				$where=array();
				if($address['zip']) $where[]='zip='.$GLOBALS['TYPO3_DB']->fullQuoteStr($address['zip'],'tx_odsosm_geocache');
				if($address['city']) $where[]='city='.$GLOBALS['TYPO3_DB']->fullQuoteStr($address['city'],'tx_odsosm_geocache');
				if($country) $where[]='country='.$GLOBALS['TYPO3_DB']->fullQuoteStr($country,'tx_odsosm_geocache');

				if($where){
					$where[]='deleted=0';

					$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						'tx_odsosm_geocache',
						implode(' AND ',$where)
					);
					$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

					if($row){
						$ll=true;
						$address['lat']=$row['lat'];
						$address['lon']=$row['lon'];
						if($row['zip']) $address['zip']=$row['zip'];
						if($row['city']) $address['city']=$row['city'];
						if(empty($address['country'])) $address['country']=$row['country'];
					}
				}
			break;

			case 1: // http://www.geonames.org/
				if($address['zip']) $query['postalcode']=$address['zip'];
				if($address['city']) $query['placename']=$address['city'];
				if($country) $query['country']=$country;

				if($query){
					$query['maxRows']=1;

					$xml=t3lib_div::getURL(
						'http://ws.geonames.org/postalCodeSearch?'.http_build_query($query),
						false,
						'User-Agent: TYPO3 extension ods_osm/1.3'
					);

					if($xml){
						$xmlobj=new SimpleXMLElement($xml);
						if($xmlobj->code){
							$ll=true;
							$address['lat']=(string)$xmlobj->code->lat;
							$address['lon']=(string)$xmlobj->code->lng;
							if($xmlobj->code->postalcode) $address['zip']=(string)$xmlobj->code->postalcode;
							if($xmlobj->code->name) $address['city']=(string)$xmlobj->code->name;
							if(empty($address['country'])) $address['country']=(string)$xmlobj->code->countryCode;
						}
					}
				}
			break;

			case 2: // http://nominatim.openstreetmap.org/
				if($address['zip']) $q[]=$address['zip'];
				if($address['city']) $q[]=$address['city'];
				if($country) $q[]=$country;

				if($q){
					$query['q']=implode(',',$q);
					$query['addressdetails']=1;
					$query['format']='xml';
					$query['email']=t3lib_div::validEmail($config['geo_service_email']) ? $config['geo_service_email'] : $_SERVER['SERVER_ADMIN'];

					$xml=t3lib_div::getURL(
						'http://nominatim.openstreetmap.org/search?'.http_build_query($query),
						false,
						'User-Agent: TYPO3 extension ods_osm/1.3'
					);

					if($xml){
						$xmlobj=new SimpleXMLElement($xml);
						if($xmlobj->place){
							$ll=true;
							$address['lat']=(string)$xmlobj->place['lat'];
							$address['lon']=(string)$xmlobj->place['lon'];
							if($xmlobj->place->postcode) $address['zip']=(string)$xmlobj->place->postcode;
							if($xmlobj->place->city || $xmlobj->place->villag) $address['city']=$xmlobj->place->city ? (string)$xmlobj->place->city : (string)$xmlobj->place->village;
							if(empty($address['country'])) $address['country']=strtoupper((string)$xmlobj->place->country_code);
						}
					}
				}
			break;
		}

		return $ll;
	}

	function updateCache($address){
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_odsosm_geocache',
			array(
				'tstamp'=>time(),
				'crdate'=>time(),
				'country'=>$address['country'],
				'city'=>$address['city'],
				'zip'=>$address['zip'],
				'lat'=>$address['lat'],
				'lon'=>$address['lon'],
			)
		);
	}
}
?>