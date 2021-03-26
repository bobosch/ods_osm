<?php

namespace Bobosch\OdsOsm;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class Div
{

    public static function getConstraintsForQueryBuilder($table, ContentObjectRenderer $cObj,
        \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder) : array
    {
        $constraints = [];

        if (is_string($table)) {
            $ctrl = $GLOBALS['TCA'][$table]['ctrl'];
            // Enable fields
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

            // Version
            $constraints[] =
                $queryBuilder->expr()->gte($table . '.pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT));

            // Translation
            if ($ctrl['languageField']) {
                $orConstraints = [
                        $queryBuilder->expr()->eq($table . '.' . $ctrl['languageField'], $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                        $queryBuilder->expr()->eq($table . '.' . $ctrl['languageField'], $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT))
                    ];

                $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');

                if ($languageAspect->getContentId() && $ctrl['transOrigPointerField']) {
                    $orConstraints[] = $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq($table . '.' . $ctrl['languageField'],
                            $queryBuilder->createNamedParameter((int) $languageAspect->getContentId(), \PDO::PARAM_INT)),
                        $queryBuilder->expr()->eq($table . '.' . $ctrl['transOrigPointerField'],
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
                    );
                }
                $constraints[] = $queryBuilder->expr()->orX(...$orConstraints);
            }
        }
        return $constraints;
    }

    public static function addJsFiles($scripts, $doc)
    {
        if (TYPO3_MODE == 'BE') {
            foreach ($scripts as $script) {
                $doc->JScode .= '<script src="' . $script['src'] . '" type="text/javascript"></script>';
            }
        } else {
            $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
            foreach ($scripts as $script) {
                $pageRenderer->addJsFooterFile(
                    $script['src'],
                    'text/javascript',
                    true,
                    false,
                    '',
                    false,
                    '|',
                    false,
                    $script['sri'],
                    false,
                    'anonymous'
                );
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
    public static function updateAddress(&$address)
    {
        $config = self::getConfig(array('cache_enabled', 'geo_service'));

        self::splitAddressField($address);

        // Use cache only when enabled
        if ($config['cache_enabled'] == 1) {
            $ll = self::searchAddress($address, 0);
        }

        if (!$ll) {
            $search = $address;
            $ll = self::searchAddress($address, $config['geo_service']);
            // Update cache when enabled or needed for statistic
            if ($ll && $config['cache_enabled']) {
                self::updateCache($address, $search);
            }
        }

        return $ll;
    }

    /**
     * Search for the given address in one of the geocoding services and update
     * its data.
     *
     * Data lat, lon, zip and city may get updated.
     *
     * @param array &$address Address record from database
     * @param integer $service Geocoding service to use
     *                          - 0: internal caching database table
     *                          - 1: geonames.org
     *                          - 2: nominatim.openstreetmap.org
     *
     * @return boolean True if the address got updated, false if not.
     */
    public static function searchAddress(&$address, $service = 0)
    {
        $config = self::getConfig(array('default_country', 'geo_service_email', 'geo_service_user'));
        $ll = false;

        $country = strtoupper(strlen($address['country']) == 2 ? $address['country'] : $config['default_country']);
        $email = GeneralUtility::validEmail($config['geo_service_email']) ? $config['geo_service_email'] : $_SERVER['SERVER_ADMIN'];

        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']) {
            $service_names = array(0 => 'cache', 1 => 'geonames', 2 => 'nominatim');
            self::getLogger()->debug('Search address using ' . $service_names[$service], $address);
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tx_odsosm_geocache');

        switch ($service) {
            case 0: // cache
                $where = array();
                if ($country) {
                    $where[] = 'country=' . $connection->quote($country, ParameterType::STRING);
                }
                if ($address['city']) {
                    $where[] = '(city=' . $connection->quote($address['city'], ParameterType::STRING) . ' OR search_city=' . $connection->quote($address['city'], ParameterType::STRING) . ')';
                }
                if ($address['zip']) {
                    $where[] = 'zip=' . $connection->quote($address['zip'], ParameterType::STRING);
                }
                if ($address['street']) {
                    $where[] = 'street=' . $connection->quote($address['street'], ParameterType::STRING);
                }
                if ($address['housenumber']) {
                    $where[] = 'housenumber=' . $connection->quote($address['housenumber'], ParameterType::STRING);
                }

                if ($where) {
                    $where[] = 'deleted=0';

                    $res = $connection->executeQuery(
                        'SELECT * FROM tx_odsosm_geocache WHERE ' . implode(' AND ', $where)
                    );
                    $row = $res->fetch(FetchMode::ASSOCIATIVE);

                    if ($row) {
                        $ll = true;

                        $set = array(
                            'tstamp' => time(),
                            'cache_hit' => $row['cache_hit'] + 1,
                        );
                        $connection->update('tx_odsosm_geocache', $set, ['uid' => intval($row['uid'])]);

                        $address['lat'] = $row['lat'];
                        $address['lon'] = $row['lon'];
                        if ($row['zip']) {
                            $address['zip'] = $row['zip'];
                        }
                        if ($row['city']) {
                            $address['city'] = $row['city'];
                        }
                        if ($row['state']) {
                            $address['state'] = $row['state'];
                        }
                        if (empty($address['country'])) {
                            $address['country'] = $row['country'];
                        }
                    }
                }
                break;

            case 1: // http://www.geonames.org/
                if ($country) {
                    $query['country'] = $country;
                }
                if ($address['city']) {
                    $query['placename'] = $address['city'];
                }
                if ($address['zip']) {
                    $query['postalcode'] = $address['zip'];
                }

                if ($query) {
                    $query['maxRows'] = 1;
                    $query['username'] = $config['geo_service_user'];

                    $xml = self::getURL('http://api.geonames.org/postalCodeSearch?' . http_build_query($query, '', '&'));

                    if ($xml) {
                        $xmlobj = new \SimpleXMLElement($xml);
                        if ($xmlobj->status) {
                            if ($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']) {
                                self::getLogger()->debug('GeoNames message', (array)$xmlobj->status->attributes());
                            }
                            self::flashMessage(
                                (string)$xmlobj->status->attributes()->message,
                                'GeoNames message',
                                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                            );
                        }

                        if ($xmlobj->code) {
                            $ll = true;
                            $address['lat'] = (string)$xmlobj->code->lat;
                            $address['lon'] = (string)$xmlobj->code->lng;
                            if ($xmlobj->code->postalcode) {
                                $address['zip'] = (string)$xmlobj->code->postalcode;
                            }
                            if ($xmlobj->code->name) {
                                $address['city'] = (string)$xmlobj->code->name;
                            }
                            if (empty($address['country'])) {
                                $address['country'] = (string)$xmlobj->code->countryCode;
                            }
                        }
                    }
                }
                break;

            case 2: // http://nominatim.openstreetmap.org/
                $query['country'] = $country;
                $query['email'] = $email;
                $query['addressdetails'] = 1;
                $query['format'] = 'xml';

                if ($address['type'] == 'structured') {
                    if ($address['city']) {
                        $query['city'] = $address['city'];
                    }
                    if ($address['zip']) {
                        $query['postalcode'] = $address['zip'];
                    }
                    if ($address['street']) {
                        $query['street'] = $address['street'];
                    }
                    if ($address['housenumber']) {
                        $query['street'] = $address['housenumber'] . ' ' . $query['street'];
                    }

                    if ($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']) {
                        self::getLogger()->debug('Nominatim structured', $query);
                    }
                    $ll = self::searchAddressNominatim($query, $address);

                    if (!$ll && $query['postalcode']) {
                        unset($query['postalcode']);

                        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']) {
                            self::getLogger()->debug('Nominatim retrying without zip', $query);
                        }
                        $ll = self::searchAddressNominatim($query, $address);
                    }
                }

                if ($address['type'] == 'unstructured') {
                    $query['q'] = $address['address'];

                    if ($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']) {
                        self::getLogger()->debug('Nominatim unstructured', $query);
                    }
                    $ll = self::searchAddressNominatim($query, $address);
                }
                break;
        }

        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']) {
            if ($ll) {
                self::getLogger()->debug('Return address', $address);
            } else {
                self::getLogger()->debug('No address found.');
            }
        }

        return $ll;
    }

    protected static function searchAddressNominatim($query, &$address)
    {
        $ll = false;

        $xml = self::getURL('https://nominatim.openstreetmap.org/search?' . http_build_query($query, '', '&'));

        if ($xml) {
            $xmlobj = new \SimpleXMLElement($xml);
            if ($xmlobj->place) {
                $ll = true;
                $address['lat'] = (string)$xmlobj->place['lat'];
                $address['lon'] = (string)$xmlobj->place['lon'];
                if ($xmlobj->place->road) {
                    $address['street'] = (string)$xmlobj->place->road;
                }
                if ($xmlobj->place->house_number) {
                    $address['housenumber'] = (string)$xmlobj->place->house_number;
                }
                if ($xmlobj->place->postcode) {
                    $address['zip'] = (string)$xmlobj->place->postcode;
                }
                if ($xmlobj->place->city || $xmlobj->place->villag) {
                    $address['city'] = $xmlobj->place->city ? (string)$xmlobj->place->city : (string)$xmlobj->place->village;
                }
                if ($xmlobj->place->state) {
                    $address['state'] = (string)$xmlobj->place->state;
                }
                if ($xmlobj->place->country_code && empty($address['country'])) {
                    $address['country'] = strtoupper((string)$xmlobj->place->country_code);
                }
            }
        }

        return $ll;
    }

    public static function getURL($url)
    {
        $ret = GeneralUtility::getURL(
            $url,
            false,
            'User-Agent: TYPO3 extension ods_osm/' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('ods_osm')
        );
        if ($ret === false) {
            if ($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']) {
                self::getLogger()->error('GeneralUtility::getURL failed', $url);
            }
            self::flashMessage(
                'Server connection error.',
                'ods_osm',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
        }

        return $ret;
    }

    public static function flashMessage($message, $title, $status)
    {
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Messaging\FlashMessage::class,
            $message,
            $title,
            $status
        );
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $flashMessageQueue->addMessage($flashMessage);
    }

    public static function updateCache($address, $search = array())
    {
        $set = array(
            'search_city' => $search['city'] ?? '',
            'country' => $address['country'] ?? '',
            'state' => $address['state'] ?? '',
            'city' => $address['city'] ?? '',
            'zip' => $address['zip'] ?? '',
            'street' => $address['street'] ?? '',
            'housenumber' => $address['housenumber'] ?? '',
        );

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tx_odsosm_geocache');

        $res = $connection->select(
            ['*'], 'tx_odsosm_geocache', $set
        );
        $row = $res->fetch(FetchMode::ASSOCIATIVE);
        if ($row) {
            $set = array(
                'tstamp' => time(),
                'service_hit' => $row['service_hit'] + 1,
            );
            $connection->update('tx_odsosm_geocache', $set, ['uid' => intval($row['uid'])]);
        } else {
            $set['tstamp'] = time();
            $set['crdate'] = time();
            $set['service_hit'] = 1;
            $set['lat'] = $address['lat'];
            $set['lon'] = $address['lon'];
            $connection->insert('tx_odsosm_geocache', $set);
        }
    }

    public static function splitAddressField(&$address)
    {
        // Address field contains street if country, city or zip is set
        if ($address['country'] || $address['city'] || $address['zip']) {
            $address['type'] = 'structured';
            if ($address['address'] && !$address['street']) {
                $address['street'] = $address['address'];
            }
            if (!$address['housenumber']) {
                // Split street and house number
                preg_match('/^(.+)\s(\d+(\s*[^\d\s]+)*)$/', $address['street'], $matches);
                if ($matches) {
                    $address['street'] = $matches[1];
                    $address['housenumber'] = $matches[2];
                }
            }
        } elseif ($address['address']) {
            $address['type'] = 'unstructured';
        } else {
            $address['type'] = 'empty';
        }
    }

    /* Get extension configuration, and if not available use default configuration. Optional parameter checks if single value is available. */
    public static function getConfig($values = array())
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ods_osm'];
        $getDefault = array();

        if ($config && is_array($values) && count($values)) {
            foreach ($values as $value) {
                if (!isset($config[$value])) {
                    $getDefault[] = $value;
                }
            }
        }

        if ($config === false || count($getDefault)) {
            $default = parse_ini_file(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm') . 'ext_conf_template.txt');
            if ($config === false) {
                return $default;
            } else {
                foreach ($getDefault as $value) {
                    $config[$value] = $default[$value];
                }
            }
        }

        return $config;
    }

    public static function getTableConfig($table = false)
    {
        $tables = [
            'fe_groups' => [
                'FIND_IN_SET' => [
                    'fe_users' => 'usergroup',
                ],
            ],
            'fe_users' => [
                'FORMAT' => '%01.6f',
                'lon' => 'tx_odsosm_lon',
                'lat' => 'tx_odsosm_lat',
                'address' => 'address',
                'zip' => 'zip',
                'city' => 'city',
                'country' => 'country',
            ],
            'tt_content' => [
                'FORMAT' => '%01.6f',
                'lon' => 'lon',
                'lat' => 'lat',
            ],
            'tx_odsosm_track' => true,
            'tx_odsosm_vector' => true,
            'sys_category' => [
                'MM' => [
                    'tt_address' => [
                        'local' => 'sys_category',
                        'mm' => 'sys_category_record_mm',
                        'foreign' => 'tt_address'
                    ]
                ]
            ]
        ];

        // load configuration for tt_address only if extension is loaded
        if (ExtensionManagementUtility::isLoaded('tt_address')) {
            $tables['tt_address'] = [
                'FORMAT' => '%01.11f',
                'lon' => 'longitude',
                'lat' => 'latitude',
                'address' => 'address',
                'zip' => 'zip',
                'city' => 'city',
                'state' => 'region',
                'country' => 'country',
            ];
        }

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['tables'])) {
            $tables = array_merge($tables, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['tables']);
        }

        return $table ? $tables[$table] : $tables;
    }

    /**
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected static function getLogger()
    {
        /** @var $loggerManager LogManager */
        $loggerManager = GeneralUtility::makeInstance(LogManager::class);

        return $loggerManager->getLogger(static::class);
    }
}
