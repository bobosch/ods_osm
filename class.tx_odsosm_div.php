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

	/**
	 * Update the given address record with geo data from a geocoding service.
	 *
	 * Note that the record does not get update in database.
	 *
	 * @param array &$address Address record from database
	 *
	 * @return boolean True if the address got updated, false if not.
	 *
	 * @uses searchAddress()
	 */
	function updateAddress(&$address){
		$config=unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ods_osm']);
		
		tx_odsosm_div::splitAddressField($address);

		// Use cache only when enabled
		if($config['cache_enabled']==1) $ll=tx_odsosm_div::searchAddress($address,0);

		if(!$ll){
			$search=$address;
			$ll=tx_odsosm_div::searchAddress($address,$config['geo_service']);
			// Update cache when enabled or needed for statistic
			if($ll && $config['cache_enabled']) tx_odsosm_div::updateCache($address,$search);
		}
		return $ll;
	}

	/**
	 * Search for the given address in one of the geocoding services and update
	 * its data.
	 *
	 * Data lat, lon, zip and city may get updated.
	 *
	 * @param array   &$address Address record from database
	 * @param integer $service  Geocoding service to use
	 *						  - 0: internal caching database table
	 *						  - 1: geonames.org
	 *						  - 2: nominatim.openstreetmap.org
	 *
	 * @return boolean True if the address got updated, false if not.
	 */
	function searchAddress(&$address,$service=0){
		$config=unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ods_osm']);
		$ll=false;

		$country=strtoupper(strlen($address['country'])==2 ? $address['country'] : $config['default_country']);
		$email=t3lib_div::validEmail($config['geo_service_email']) ? $config['geo_service_email'] : $_SERVER['SERVER_ADMIN'];

		switch($service){
			case 0: // cache
				$where=array();
				if($country) $where[]='country='.$GLOBALS['TYPO3_DB']->fullQuoteStr($country,'tx_odsosm_geocache');
				if($address['city']) $where[]='(city='.$GLOBALS['TYPO3_DB']->fullQuoteStr($address['city'],'tx_odsosm_geocache').' OR search_city='.$GLOBALS['TYPO3_DB']->fullQuoteStr($address['city'],'tx_odsosm_geocache').')';
				if($address['zip']) $where[]='zip='.$GLOBALS['TYPO3_DB']->fullQuoteStr($address['zip'],'tx_odsosm_geocache');
				if($address['street']) $where[]='street='.$GLOBALS['TYPO3_DB']->fullQuoteStr($address['street'],'tx_odsosm_geocache');
				if($address['housenumber']) $where[]='housenumber='.$GLOBALS['TYPO3_DB']->fullQuoteStr($address['housenumber'],'tx_odsosm_geocache');

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
						$set=array(
							'tstamp'=>time(),
							'cache_hit'=>$row['cache_hit']+1,
						);
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_odsosm_geocache','uid='.$row['uid'],$set);
						$address['lat']=$row['lat'];
						$address['lon']=$row['lon'];
						if($row['zip']) $address['zip']=$row['zip'];
						if($row['city']) $address['city']=$row['city'];
						if($row['state']) $address['state']=$row['state'];
						if(empty($address['country'])) $address['country']=$row['country'];
					}
				}
			break;

			case 1: // http://www.geonames.org/
				if($country) $query['country']=$country;
				if($address['city']) $query['placename']=$address['city'];
				if($address['zip']) $query['postalcode']=$address['zip'];

				if($query){
					$query['maxRows']=1;

					$xml=t3lib_div::getURL(
						'http://ws.geonames.org/postalCodeSearch?'.http_build_query($query),
						false,
						'User-Agent: TYPO3 extension ods_osm/'.t3lib_extMgm::getExtensionVersion('ods_osm')
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
				$query['country']=$country;
				$query['email']=$email;
				$query['addressdetails']=1;
				$query['format']='xml';

				if($this->address_type=='structured'){
					if($address['city']) $query['city']=$address['city'];
					if($address['zip']) $query['postalcode']=$address['zip'];
					if($address['street']) $query['street']=$address['street'];
					if($address['housenumber']) $query['street']=$address['housenumber'].' '.$query['street'];

					t3lib_div::devLog('search_address Nominatim structured', "ods_osm", -1, $query);
					$ll=tx_odsosm_div::searchAddressNominatim($query,$address);
					
					if(!$ll && $query['postalcode']){
						unset($query['postalcode']);

						t3lib_div::devLog('search_address Nominatim retrying without zip', "ods_osm", -1, $query);
						$ll=tx_odsosm_div::searchAddressNominatim($query,$address);
					}
				}

				if($this->address_type=='unstructured'){
					$query['q']=$address['address'];

					t3lib_div::devLog('search_address Nominatim unstructured', "ods_osm", -1, $query);
					$ll=tx_odsosm_div::searchAddressNominatim($query,$address);
				}
			break;
		}

		return $ll;
	}
	
	function searchAddressNominatim($query,&$address){
		$ll=false;
	
		$xml=t3lib_div::getURL(
			'http://nominatim.openstreetmap.org/search?'.http_build_query($query),
			false,
			'User-Agent: TYPO3 extension ods_osm/'.t3lib_extMgm::getExtensionVersion('ods_osm')
		);

		if($xml){
			$xmlobj=new SimpleXMLElement($xml);
			if($xmlobj->place){
				$ll=true;
				$address['lat']=(string)$xmlobj->place['lat'];
				$address['lon']=(string)$xmlobj->place['lon'];
				if($xmlobj->place->road) $address['street']=(string)$xmlobj->place->road;
				if($xmlobj->place->house_number) $address['housenumber']=(string)$xmlobj->place->house_number;
				if($xmlobj->place->postcode) $address['zip']=(string)$xmlobj->place->postcode;
				if($xmlobj->place->city || $xmlobj->place->villag) $address['city']=$xmlobj->place->city ? (string)$xmlobj->place->city : (string)$xmlobj->place->village;
				if($xmlobj->place->state) $address['state']=(string)$xmlobj->place->state;
				if($xmlobj->place->country_code && empty($address['country'])) $address['country']=strtoupper((string)$xmlobj->place->country_code);
			}
		}
		
		return $ll;
	}

	function updateCache($address,$search=array()){
		$set=array(
			'search_city'=>$search['city'],
			'country'=>$address['country'],
			'state'=>$address['state'],
			'city'=>$address['city'],
			'zip'=>$address['zip'],
			'street'=>$address['street'],
			'housenumber'=>$address['housenumber'],
		);

		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_odsosm_geocache',
			implode(' AND ',tx_odsosm_div::getSet($set))
		);
		$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if($row){
			$set=array(
				'tstamp'=>time(),
				'service_hit'=>$row['service_hit']+1,
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_odsosm_geocache','uid='.$row['uid'],$set);
		}else{
			$set['tstamp']=time();
			$set['crdate']=time();
			$set['service_hit']=1;
			$set['lat']=$address['lat'];
			$set['lon']=$address['lon'];
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_odsosm_geocache',$set);
		}
	}
	
	function splitAddressField(&$address){
		// Address field contains street if country, city or zip is set
		if($address['country'] || $address['city'] || $address['zip']){
			$this->address_type='structured';
			// Split street and house number
			preg_match('/^(.+)\s(\d+(\s*[^\d\s]+)*)$/',$address['address'],$matches);
			if($matches){
				$address['street']=$matches[1];
				$address['housenumber']=$matches[2];
			}else{
				$address['street']=$address['address'];
			}
		}elseif($address['address']){
			$this->address_type='unstructured';
		}else{
			$this->address_type='empty';
		}
	}

	function getSet($data){
		$set=array();
		foreach($data as $field=>$value){
			$set[$field]='`'.$field.'`="'.mysql_real_escape_string($value).'"';
		}
		return($set);
	}
}
?>