<?php

namespace Bobosch\OdsOsm;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Log\Logger;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class Div
{
    const RESOURCE_BASE_PATH = 'EXT:ods_osm/Resources/Public/';

    public static function getConstraintsForQueryBuilder(
        $table,
        ContentObjectRenderer $cObj,
        QueryBuilder $queryBuilder
    ): array {
        $constraints = [];

        if (is_string($table)) {
            $ctrl = $GLOBALS['TCA'][$table]['ctrl'];
            // Enable fields
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

            // Version
            $constraints[] =
                $queryBuilder->expr()->gte($table . '.pid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));

            // Translation
            if ($ctrl['languageField'] ?? null) {
                $orConstraints = [
                    $queryBuilder->expr()->eq(
                        $table . '.' . $ctrl['languageField'],
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $table . '.' . $ctrl['languageField'],
                        $queryBuilder->createNamedParameter(-1, Connection::PARAM_INT)
                    ),
                ];

                $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');

                if ($languageAspect->getContentId() && $ctrl['transOrigPointerField']) {
                    $orConstraints[] = $queryBuilder->expr()->and($queryBuilder->expr()->eq($table . '.' . $ctrl['languageField'],
                        $queryBuilder->createNamedParameter((int) $languageAspect->getContentId(), Connection::PARAM_INT)), $queryBuilder->expr()->eq($table . '.' . $ctrl['transOrigPointerField'],
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)));
                }
                $constraints[] = $queryBuilder->expr()->or(...$orConstraints);
            }
        }
        return $constraints;
    }

    public static function addJsFiles($scripts, $doc): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
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
                $script['sri'] ?? '',
                false,
                'anonymous'
            );
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
        $config = self::getConfig(['cache_enabled', 'geo_service']);

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
        $config = self::getConfig(['default_country', 'geo_service_email', 'geo_service_user']);
        $ll = false;

        $country = strtoupper(strlen($address['country'] ?? false) == 2 ? $address['country'] : $config['default_country']);
        $email = GeneralUtility::validEmail($config['geo_service_email']) ? $config['geo_service_email'] : ($_SERVER['SERVER_ADMIN'] ?? 'unkown@example.com');

        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) {
            $service_names = [0 => 'cache', 1 => 'geonames', 2 => 'nominatim'];
            self::getLogger()->debug('Search address using ' . $service_names[$service], $address);
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tx_odsosm_geocache');

        switch ($service) {
            case 0: // cache
                $where = [];
                if ($country) {
                    $where[] = 'country=' . $connection->quote($country, ParameterType::STRING);
                }
                if ($address['city'] ?? false) {
                    $where[] = '(city=' . $connection->quote($address['city'], ParameterType::STRING) . ' OR search_city=' . $connection->quote($address['city'], ParameterType::STRING) . ')';
                }
                if ($address['zip'] ?? false) {
                    $where[] = 'zip=' . $connection->quote($address['zip'], ParameterType::STRING);
                }
                if ($address['street'] ?? false) {
                    $where[] = 'street=' . $connection->quote($address['street'], ParameterType::STRING);
                }
                if ($address['housenumber'] ?? false) {
                    $where[] = 'housenumber=' . $connection->quote($address['housenumber'], ParameterType::STRING);
                }

                if ($where) {
                    $where[] = 'deleted=0';

                    $res = $connection->executeQuery(
                        'SELECT * FROM tx_odsosm_geocache WHERE ' . implode(' AND ', $where)
                    );
                    $row = $res->fetchAssociative();

                    if ($row) {
                        $ll = true;

                        $set = [
                            'tstamp' => time(),
                            'cache_hit' => $row['cache_hit'] + 1,
                        ];
                        $connection->update('tx_odsosm_geocache', $set, ['uid' => (int) $row['uid']]);

                        $address['lat'] = $row['lat'];
                        $address['lon'] = $row['lon'];
                        if ($row['zip'] ?? false) {
                            $address['zip'] = $row['zip'];
                        }
                        if ($row['city'] ?? false) {
                            $address['city'] = $row['city'];
                        }
                        if ($row['state'] ?? false) {
                            $address['state'] = $row['state'];
                        }
                        if (empty($address['country'] ?? false)) {
                            $address['country'] = $row['country'];
                        }
                    }
                }
                break;

            case 1: // http://www.geonames.org/
                if ($country) {
                    $query['country'] = $country;
                }
                if ($address['city'] ?? false) {
                    $query['placename'] = $address['city'];
                }
                if ($address['zip'] ?? false) {
                    $query['postalcode'] = $address['zip'];
                }

                if ($query) {
                    $query['maxRows'] = 1;
                    $query['username'] = $config['geo_service_user'];

                    /** @var RequestFactory $requestFactory */
                    $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
                    $configuration = [
                        'timeout' => 60,
                        'headers' => [
                            'Accept' => 'application/json',
                            'User-Agent' => 'TYPO3 extension ods_osm/' . ExtensionManagementUtility::getExtensionVersion('ods_osm')
                        ],
                    ];

                    // secure endpoint available, too: https://secure.geonames.org/postalCodeSearchJSON?
                    $response = $requestFactory->request('http://api.geonames.org/postalCodeSearchJSON?' . http_build_query($query, '', '&'), 'GET', $configuration);
                    $content  = $response->getBody()->getContents();
                    $result = json_decode($content, true);

                    if ($result) {
                        if ($result['status'] ?? false) {
                            if ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) {
                                self::getLogger()->debug('GeoNames message', (array)$result['status']['message']);
                            }
                            self::flashMessage(
                                (string)$result['status']['message'],
                                'GeoNames message',
                                AbstractMessage::WARNING
                            );
                        }

                        if ($result['postalCodes'][0] ?? false) {
                            $ll = true;
                            $address['lat'] = (string)$result['postalCodes'][0]['lat'];
                            $address['lon'] = (string)$result['postalCodes'][0]['lng'];
                            if ($result['postalCodes'][0]['postalCode'] ?? false) {
                                $address['zip'] = (string)$result['postalCodes'][0]['postalCode'];
                            }
                            if ($result['postalCodes'][0]['placeName'] ?? false) {
                                $address['city'] = (string)$result['postalCodes'][0]['placeName'];
                            }
                            if (empty($address['country'] ?? false)) {
                                $address['country'] = (string)$result['postalCodes'][0]['countryCode'];
                            }
                        }
                    } elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false) {
                        self::getLogger()->error('No valid response from GeoNames service.');
                    }
                }
                break;

            case 2: // http://nominatim.openstreetmap.org/
                $query['country'] = $country;
                $query['email'] = $email;
                $query['addressdetails'] = 1;
                $query['format'] = 'jsonv2';

                if ($address['type'] == 'structured') {
                    if ($address['city'] ?? false) {
                        $query['city'] = $address['city'];
                    }
                    if ($address['zip'] ?? false) {
                        $query['postalcode'] = $address['zip'];
                    }
                    if ($address['street'] ?? false) {
                        $query['street'] = $address['street'];
                    }
                    if ($address['housenumber'] ?? false) {
                        $query['street'] = $address['housenumber'] . ' ' . $query['street'];
                    }

                    if ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false) {
                        self::getLogger()->debug('Nominatim structured', $query);
                    }
                    $ll = self::searchAddressNominatim($query, $address);

                    if (!$ll && ($query['postalcode'] ?? false)) {
                        unset($query['postalcode']);

                        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false) {
                            self::getLogger()->debug('Nominatim retrying without zip', $query);
                        }
                        $ll = self::searchAddressNominatim($query, $address);
                    }
                }

                if ($address['type'] == 'unstructured') {
                    $query['q'] = $address['address'];

                    if ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false) {
                        self::getLogger()->debug('Nominatim unstructured', $query);
                    }
                    $ll = self::searchAddressNominatim($query, $address);
                }
                break;
        }

        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false) {
            if ($ll) {
                self::getLogger()->debug('Return address', $address);
            } else {
                self::getLogger()->debug('No address found.');
            }
        }

        return $ll;
    }

    /**
     * Search for the given address in Nominatim service.
     *
     * Data lat, lon, zip and city may get updated.
     *
     * @param array $query The query sent to the nominatim API
     * @param array &$address Address record from database
     *
     * @return boolean True if the address was found and got updated.
     */
    protected static function searchAddressNominatim($query, &$address)
    {
        $ll = false;

        /** @var RequestFactory $requestFactory */
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $configuration = [
            'timeout' => 60,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'TYPO3 extension ods_osm/' . ExtensionManagementUtility::getExtensionVersion('ods_osm')
            ],
        ];

        $response = $requestFactory->request('https://nominatim.openstreetmap.org/search?' . http_build_query($query, '', '&'), 'GET', $configuration);
        $content  = $response->getBody()->getContents();
        $result = json_decode($content, true);

        // Save value in cache
        if ($result) {
            // take the first result
            if ($result[0] ?? false) {
                $ll = true;
                $address['lat'] = (string)$result[0]['lat'];
                $address['lon'] = (string)$result[0]['lon'];
                if ($result[0]['address']['road'] ?? false) {
                    $address['street'] = (string)$result[0]['address']['road'];
                }
                if ($result[0]['address']['house_number'] ?? false) {
                    $address['housenumber'] = (string)$result[0]['address']['house_number'];
                }
                if ($result[0]['address']['postcode'] ?? false) {
                    $address['zip'] = (string)$result[0]['address']['postcode'];
                }
                if ($result[0]['address']['city'] ?? false) {
                    $address['city'] = $result[0]['address']['city'];
                } else if ($result[0]['address']['village'] ?? false) {
                    $address['city'] = (string)$result[0]['address']['village'];
                }
                if ($result[0]['address']['state'] ?? false) {
                    $address['state'] = (string)$result[0]['address']['state'];
                }
                if (($result[0]['address']['country_code'] ?? false) && empty($address['country'] ?? false)) {
                    $address['country'] = strtoupper((string)$result[0]['address']['country_code']);
                }
            }
        } elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false) {
            self::getLogger()->error('No valid response from Nominatim service.');
        }

        return $ll;
    }

    public static function flashMessage($message, $title, $status): void
    {
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            $title,
            $status
        );
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $flashMessageQueue->addMessage($flashMessage);
    }

    public static function updateCache($address, $search = []): void
    {
        $set = [
            'search_city' => $search['city'] ?? '',
            'country' => $address['country'] ?? '',
            'state' => $address['state'] ?? '',
            'city' => $address['city'] ?? '',
            'zip' => $address['zip'] ?? '',
            'street' => $address['street'] ?? '',
            'housenumber' => $address['housenumber'] ?? '',
        ];

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tx_odsosm_geocache');

        $res = $connection->select(
            ['*'], 'tx_odsosm_geocache', $set
        );
        $row = $res->fetchAssociative();
        if ($row) {
            $set = [
                'tstamp' => time(),
                'service_hit' => $row['service_hit'] + 1,
            ];
            $connection->update('tx_odsosm_geocache', $set, ['uid' => (int) $row['uid']]);
        } else {
            $set['tstamp'] = time();
            $set['crdate'] = time();
            $set['service_hit'] = 1;
            $set['lat'] = $address['lat'];
            $set['lon'] = $address['lon'];
            $connection->insert('tx_odsosm_geocache', $set);
        }
    }

    public static function splitAddressField(&$address): void
    {
        // Address field contains street if country, city or zip is set
        if (($address['country'] ?? false) || ($address['city'] ?? false) || ($address['zip'] ?? false)) {
            $address['type'] = 'structured';
            if ($address['address'] && !($address['street'] ?? false)) {
                $address['street'] = $address['address'];
            }
            if (!($address['housenumber'] ?? false) && ($address['street'] ?? false)) {
                // Split street and house number
                preg_match('/^(.+)\s(\d+(\s*[^\d\s]+)*)$/', $address['street'], $matches);
                if ($matches) {
                    $address['street'] = $matches[1];
                    $address['housenumber'] = $matches[2];
                }
            }
        } elseif ($address['address'] ?? false) {
            $address['type'] = 'unstructured';
        } else {
            $address['type'] = 'empty';
        }
    }

    /**
     * Get extension configuration, and if not available use
     * default configuration. Optional parameter checks if
     * single value is available.
     *
     * @param array $values
     *
     * @return array
     */
    public static function getConfig($values = [])
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ods_osm'] ?? [];
        $getDefault = [];

        if ($config && is_array($values) && count($values)) {
            foreach ($values as $value) {
                if (!isset($config[$value])) {
                    $getDefault[] = $value;
                }
            }
        }

        if (empty($config) || count($getDefault)) {
            $default = parse_ini_file(ExtensionManagementUtility::extPath('ods_osm') . 'ext_conf_template.txt');
            if (empty($config)) {
                $config = $default;
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

        // load configuration for calendarize only if extension is loaded
        if (ExtensionManagementUtility::isLoaded('calendarize')) {
            $tables['tx_calendarize_domain_model_event'] = [
                'FORMAT' => '%01.6f',
                'lon' => 'tx_odsosm_lon',
                'lat' => 'tx_odsosm_lat',
                'address' => 'location',
            ];
        }
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

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['tables'] ?? null)) {
            $tables = array_merge($tables, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['tables']);
        }

        return $table ? ($tables[$table] ?? []) : $tables;
    }

    /**
     * @return Logger
     */
    protected static function getLogger()
    {
        /** @var $loggerManager LogManager */
        $loggerManager = GeneralUtility::makeInstance(LogManager::class);

        return $loggerManager->getLogger(static::class);
    }

}
