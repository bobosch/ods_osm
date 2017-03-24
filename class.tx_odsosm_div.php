<?php
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class tx_odsosm_div {
	public static function getWhere($table,$cObj){
		if(is_string($table)){
			$ctrl=$GLOBALS['TCA'][$table]['ctrl'];
			// Enable fields
			$where=$cObj->enableFields($table);
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

	public static function getOverlay($table,$row){
		if(is_string($table) && is_array($row)){
			$ctrl=$GLOBALS['TCA'][$table]['ctrl'];
			// Version
			// - Table has versioning
			// - Current user is in workspace
			// - Versioning is enabled
			if($ctrl['versioningWS'] && $GLOBALS['BE_USER']->workspace && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')){
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

	public static function addJsFiles($scripts,$doc=false){
		if(TYPO3_MODE=='BE'){
			foreach($scripts as $script){
				$doc->JScode.='<script src="'.$script.'" type="text/javascript"></script>';
			}
		}else{
            /** @var PageRenderer $pageRenderer */
			$pageRenderer = null;
			if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()) < 8000000) {
				$pageRenderer = $GLOBALS['TSFE']->getPageRendered();
			} else {
				$pageRenderer = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
			}
			foreach($scripts as $script){
				$pageRenderer->addJsFile($script,'text/javascript',false);
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
	public static function updateAddress(&$address){
		$config=self::getConfig(array('cache_enabled','geo_service'));

		self::splitAddressField($address);

		// Use cache only when enabled
		if($config['cache_enabled']==1) $ll=self::searchAddress($address,0);

		if(!$ll){
			$search=$address;
			$ll=self::searchAddress($address,$config['geo_service']);
			// Update cache when enabled or needed for statistic
			if($ll && $config['cache_enabled']) self::updateCache($address,$search);
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
	public static function searchAddress(&$address,$service=0){
		$config=self::getConfig(array('default_country','geo_service_email','geo_service_user'));
		$ll=false;

		$country=strtoupper(strlen($address['country'])==2 ? $address['country'] : $config['default_country']);
		$email=\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($config['geo_service_email']) ? $config['geo_service_email'] : $_SERVER['SERVER_ADMIN'];

		if(TYPO3_DLOG){
			$service_names=array(0=>'cache',1=>'geonames',2=>'nominatim');
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Search address using '.$service_names[$service],'ods_osm',0,$address);
		}
		
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
					$query['username']=$config['geo_service_user'];

					$xml=self::getURL('http://api.geonames.org/postalCodeSearch?'.http_build_query($query,'','&'));

					if($xml){
						$xmlobj=new SimpleXMLElement($xml);
						if($xmlobj->status){
							if(TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('GeoNames message','ods_osm',2,(array)$xmlobj->status->attributes());
							self::flashMessage(
								(string)$xmlobj->status->attributes()->message,
								'GeoNames message',
								\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
							);
						}
						
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

				if($address['type']=='structured'){
					if($address['city']) $query['city']=$address['city'];
					if($address['zip']) $query['postalcode']=$address['zip'];
					if($address['street']) $query['street']=$address['street'];
					if($address['housenumber']) $query['street']=$address['housenumber'].' '.$query['street'];

					if(TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Nominatim structured', 'ods_osm', -1, $query);
					$ll=self::searchAddressNominatim($query,$address);
					
					if(!$ll && $query['postalcode']){
						unset($query['postalcode']);

						if(TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Nominatim retrying without zip', 'ods_osm', -1, $query);
						$ll=self::searchAddressNominatim($query,$address);
					}
				}

				if($address['type']=='unstructured'){
					$query['q']=$address['address'];

					if(TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Nominatim unstructured', 'ods_osm', -1, $query);
					$ll=self::searchAddressNominatim($query,$address);
				}
			break;
		}

		if(TYPO3_DLOG){
			if($ll)	\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Return address','ods_osm',0,$address);
			else \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('No address found','ods_osm',0);
		}

		return $ll;
	}
	
	protected static function searchAddressNominatim($query,&$address){
		$ll=false;
	
		$xml=self::getURL('http://nominatim.openstreetmap.org/search?'.http_build_query($query,'','&'));

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
	
	public static function getURL($url) {
		$ret=\TYPO3\CMS\Core\Utility\GeneralUtility::getURL(
			$url,
			false,
			'User-Agent: TYPO3 extension ods_osm/' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('ods_osm')
		);
		if($ret===false) {
			if(TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('\TYPO3\CMS\Core\Utility\GeneralUtility::getURL failed', 'ods_osm', 3, $url);
			self::flashMessage(
				'Server connection error.',
				'ods_osm',
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
		}
		
		return $ret;
	}
	
	public static function flashMessage($message,$title,$status) {
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\CMS\Core\Messaging\FlashMessage',
			$message,
			$title,
			$status
		);
		$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Messaging\FlashMessageService');
		$flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
		$flashMessageQueue->addMessage($flashMessage);
	}

	public static function updateCache($address,$search=array()){
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
			implode(' AND ',self::getSet($set,'tx_odsosm_geocache'))
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
	
	public static function splitAddressField(&$address){
		// Address field contains street if country, city or zip is set
		if($address['country'] || $address['city'] || $address['zip']){
			$address['type']='structured';
			if($address['address'] && !$address['street']) $address['street']=$address['address'];
			if(!$address['housenumber']) {
				// Split street and house number
				preg_match('/^(.+)\s(\d+(\s*[^\d\s]+)*)$/',$address['street'],$matches);
				if($matches){
					$address['street']=$matches[1];
					$address['housenumber']=$matches[2];
				}
			}
		}elseif($address['address']){
			$address['type']='unstructured';
		}else{
			$address['type']='empty';
		}
	}

	public static function getSet($data,$table){
		$set=array();
		foreach($data as $field=>$value){
			$set[$field]='`'.$field.'`='.$GLOBALS['TYPO3_DB']->fullQuoteStr($value,$table);
		}
		return $set;
	}
	
	/* Get extension configuration, and if not available use default configuration. Optional parameter checks if single value is available. */
	public static function getConfig($values=array()){
		$config=unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ods_osm']);
		$getDefault=array();
		
		if($config && is_array($values) && count($values)){
			foreach($values as $value){
				if(!isset($config[$value])) $getDefault[]=$value;
			}
		}
		
		if($config===false || count($getDefault)){
			$default=parse_ini_file(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm') . 'ext_conf_template.txt');
			if($config===false){
				return $default;
			}else{
				foreach($getDefault as $value){
					$config[$value]=$default[$value];
				}
			}
		}

		return $config;
	}

	public static function getTableConfig($table=false){
		$tables = array(
			'fe_groups' => array(
				'FIND_IN_SET' => array(
					'fe_users' => 'usergroup',
				),
			),
			'fe_users' => array(
				'FORMAT' => '%01.6f',
				'lon' => 'tx_odsosm_lon',
				'lat' => 'tx_odsosm_lat',
				'address' => 'address',
				'zip' => 'zip',
				'city' => 'city',
				'country' => 'country',
			),
			'tt_content'=>array(
				'FORMAT' => '%01.6f',
				'lon' => 'lon',
				'lat' => 'lat',
			),
            'tt_address' => array(
                'lat' => 'latitude',
                'lon' => 'longitude'
            ),
			'tx_odsosm_track' => true,
			'tx_odsosm_vector' => true,
		);

		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['tables'])){
			$tables = array_merge($tables, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['tables']);
		}
		
		return $table ? $tables[$table] : $tables;
	}
}
?>