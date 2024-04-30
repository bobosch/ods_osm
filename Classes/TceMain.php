<?php

namespace Bobosch\OdsOsm;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use \geoPHP\geoPHP;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TceMain
{
    public $lon = [];
    public $lat = [];

    // ['t3lib/class.t3lib_tcemain.php']['processDatamapClass']
    public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, $obj)
    {
    }

    /**
     * Generate a different preview link     *
     *
     * @param string $status status
     * @param string $table table name
     * @param int $id id of the record
     * @param array $fieldArray fieldArray
     * @param DataHandler $parentObject parent Object
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        array $fieldArray,
        DataHandler $parentObject
    ): void {
        // guard statement, abort here if no ods_osm table
        if (strpos($table, 'tx_odsosm_') !== 0) {
            return;
        }
        /*
         * The id may be integer already or the temporary NEW id. This depends, how the record was created
         *
         * case 1:
         *   - the user creates a tx_odsosm_track record
         *   - the file is added to the not yet saved record
         *   - the record is saved (status="new")
         *
         *  case 2:
         *   - the user creates a tx_odsosm_track record and saves it
         *   - the user remains in the dialog and adds the file
         *   - the user saves again (status="update")
         *
         * This hook is run for sys_file_reference and for tx_odsosm_track. We only do our work on tx_odsosm_track:
         *   - in case 1 --> id is not integer yet but the temporary NEW id
         *   - in case 2 --> id is integer
         */

        if ($status == "new") {
            $id = $parentObject->substNEWwithIDs[$id] ?? '';
        }

        if (!is_int($id)) {
            return;
        }

        /*
         * If ods_osm is installed via composer, the class geoPHP is already known.
         * Otherwise we use the copy in the local folder which is only available in the TER package.
         */
        if (!class_exists(geoPHP::class)) {
            require_once 'phar://' . ExtensionManagementUtility::extPath('ods_osm', 'Resources/Private/geophp.phar/vendor/autoload.php');
        }

        switch ($table) {
            case 'tx_odsosm_track':
                if (is_int($id)) {
                    $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                    $fileObjects = $fileRepository->findByRelation('tx_odsosm_track', 'file', $id);
                }
                if ($fileObjects) {
                    $file = $fileObjects[0];
                } else {
                    break;
                }

                $filename = Environment::getPublicPath() . '/' . $file->getPublicUrl();
                if (file_exists($filename)) {
                    try {
                        $polygon = geoPHP::load(file_get_contents($filename), pathinfo($filename, PATHINFO_EXTENSION));
                    } catch (\Exception $e) {
                        // silently ignore failure of parsing data
                        break;
                    }
                    $box = $polygon->getBBox();

                    // unfortunately we cannot pass the new values by reference in this hook, because the database operation is already done.
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable($table);
                    $queryBuilder
                        ->update('tx_odsosm_track')
                        ->where(
                            $queryBuilder->expr()->eq('uid', $id)
                        )
                        ->set('min_lon', sprintf('%01.6f', $box['minx']))
                        ->set('min_lat', sprintf('%01.6f', $box['miny']))
                        ->set('max_lon', sprintf('%01.6f', $box['maxx']))
                        ->set('max_lat', sprintf('%01.6f', $box['maxy']))
                        ->executeStatement();
                }
                break;
            case 'tx_odsosm_marker':
                if (is_int($id)) {
                    $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                    $fileObjects = $fileRepository->findByRelation('tx_odsosm_marker', 'icon', $id);
                }
                if ($fileObjects) {
                    $file = $fileObjects[0];
                } else {
                    break;
                }

                $filename = Environment::getPublicPath() . '/' . $file->getPublicUrl();
                if (file_exists($filename)) {
                    $size = getimagesize($filename);

                    if ($size) {
                        // unfortunately we cannot pass the new values by reference in this hook, because the database operation is already done.
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                            ->getQueryBuilderForTable($table);
                        $queryBuilder
                            ->update('tx_odsosm_marker')
                            ->where(
                                $queryBuilder->expr()->eq('uid', $id)
                            )
                            ->set('size_x', $size[0])
                            ->set('size_y', $size[1])
                            ->set('offset_x', -round($size[0] / 2))
                            ->set('offset_y', -$size[1])
                            ->executeStatement();
                    }
                }
                break;
            case 'tx_odsosm_vector':
                if (is_int($id)) {
                    $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                    $fileObjects = $fileRepository->findByRelation('tx_odsosm_vector', 'file', $id);
                }
                if ($fileObjects) {
                    $file = $fileObjects[0];
                } else {
                    break;
                }

                $filename = Environment::getPublicPath() . '/' . $file->getPublicUrl();
                if (file_exists($filename)) {

                    try {
                        $polygon = geoPHP::load(file_get_contents($filename), pathinfo($filename, PATHINFO_EXTENSION));
                    } catch (\Exception $e) {
                        // silently ignore failure of parsing geojson
                        break;
                    }

                    $box = $polygon->getBBox();
                    if ($box) {
                        // unfortunately we cannot pass the new values by reference in this hook, because the database operation is already done.
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                            ->getQueryBuilderForTable($table);

                        // write the bbox into database
                        $queryBuilder
                            ->update('tx_odsosm_vector')
                            ->where(
                                $queryBuilder->expr()->eq('uid', $id)
                            )
                            ->set('min_lon', sprintf('%01.6f', $box['minx']))
                            ->set('min_lat', sprintf('%01.6f', $box['miny']))
                            ->set('max_lon', sprintf('%01.6f', $box['maxx']))
                            ->set('max_lat', sprintf('%01.6f', $box['maxy']))
                            ->executeStatement();

                        // handle properties
                        $properties = (array)$polygon->getData();
                        if (empty($properties)) {
                            // seems to contain multiple polygones
                            $components = $polygon->getComponents();
                            // take the properties of the first polygon
                            $properties = (array)$components[0]->getData();
                        }

                        if (!empty($properties)) {

                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                                ->getQueryBuilderForTable($table);

                            $result = $queryBuilder
                                ->select('properties', 'properties_from_file')
                                ->from('tx_odsosm_vector')
                                ->where(
                                    $queryBuilder->expr()->eq('uid', $id)
                                )
                                ->setMaxResults(1)
                                ->executeQuery();

                            if ($row = $result->fetchAssociative()) {
                                if ($row['properties_from_file'] && !empty($properties)) {
                                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                                        ->getQueryBuilderForTable($table);

                                    $queryBuilder
                                        ->update('tx_odsosm_vector')
                                        ->where(
                                            $queryBuilder->expr()->eq('uid', $id)
                                        )
                                        ->set('properties', implode(', ', array_keys($properties)))
                                        ->set('properties_from_file', 0)
                                        ->executeStatement();
                                }
                            }
                        }
                    }
                }
                break;
        }
    }

    // ['t3lib/class.t3lib_tcemain.php']['processDatamapClass']
    public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, $obj): void
    {
        switch ($table) {
            case 'tx_odsosm_vector':
                if (!empty($fieldArray['data'] ?? false)) {
                    $this->lon = [];
                    $this->lat = [];

                    /*
                     * If ods_osm is installed via composer, the class geoPHP is already known.
                     * Otherwise we use the copy in the local folder which is only available in the TER package.
                     */
                    if (!class_exists(geoPHP::class)) {
                        require_once 'phar://' . ExtensionManagementUtility::extPath('ods_osm', 'Resources/Private/geophp.phar/vendor/autoload.php');
                    }

                    try {
                        $polygon = geoPHP::load(($fieldArray['data']));
                    } catch (\Exception $e) {
                        // silently ignore failure of parsing geojson
                        break;
                    }

                    if ($polygon) {
                        $box = $polygon->getBBox();

                        $fieldArray['min_lon'] = sprintf('%01.6f', $box['minx']);
                        $fieldArray['min_lat'] = sprintf('%01.6f', $box['miny']);
                        $fieldArray['max_lon'] = sprintf('%01.6f', $box['maxx']);
                        $fieldArray['max_lat'] = sprintf('%01.6f', $box['maxy']);

                        // handle properties
                        $properties = [];
                        $properties = (array)$polygon->getData();
                        if (empty($properties)) {
                            // seems to contain multiple polygones
                            $components = $polygon->getComponents();
                            // take the properties of the first polygon
                            $properties = (array)$components[0]->getData();
                        }

                        if (!empty($properties)) {

                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                                ->getQueryBuilderForTable($table);

                            $result = $queryBuilder
                                ->select('properties', 'properties_from_file')
                                ->from('tx_odsosm_vector')
                                ->where(
                                    $queryBuilder->expr()->eq('uid', $id)
                                )
                                ->setMaxResults(1)
                                ->executeQuery();

                            if ($row = $result->fetchAssociative()) {
                                if ($row['properties_from_file'] && !empty($properties)) {

                                    $fieldArray['properties'] = implode(', ', array_keys($properties));
                                    $fieldArray['properties_from_file'] = 0;
                                }
                            }
                        }
                    } else {
                        $fieldArray['min_lon'] = 0;
                        $fieldArray['min_lat'] = 0;
                        $fieldArray['max_lon'] = 0;
                        $fieldArray['max_lat'] = 0;
                    }
                }
                break;
            default:
                $tc = Div::getTableConfig($table);
                if ($tc['lon'] ?? false) {
                    if (
                        (isset($tc['address']) && ($fieldArray[$tc['address']] ?? null))
                        || (isset($tc['street']) && ($fieldArray[$tc['street']] ?? null))
                        || (isset($tc['zip']) && ($fieldArray[$tc['zip']] ?? null))
                        || (isset($tc['city']) && ($fieldArray[$tc['city']] ?? null))
                    ) {
                        $config = Div::getConfig(['autocomplete']);
                        // Search coordinates
                        if ($config['autocomplete']) {
                            // Generate address array with standard keys
                            $address = [];
                            foreach ($tc as $def => $field) {
                                if ($def == strtolower($def)) {
                                    $address[$def] = $obj->datamap[$table][$id][$field];
                                }
                            }
                            if ($config['autocomplete'] == 2 || (float) ($address['longitude'] ?? 0) == 0) {
                                $ll = Div::updateAddress($address);
                                if ($ll) {
                                    // Optimize address
                                    $address['lon'] = sprintf($tc['FORMAT'], $address['lon']);
                                    $address['lat'] = sprintf($tc['FORMAT'], $address['lat']);
                                    if (($address['type'] ?? false) == 'structured') {
                                        if (isset($tc['address']) && !isset($tc['street'])) {
                                            if ($address['street'] ?? false) {
                                                $address['address'] = $address['street'];
                                                if ($address['housenumber'] ?? false) {
                                                    $address['address'] .= ' ' . $address['housenumber'];
                                                }
                                            }
                                        }
                                    } elseif ($tc['address'] ?? false) {
                                        if ($address['street'] ?? false) {
                                            $address['address'] = $address['street'];
                                            if ($address['housenumber'] ?? false) {
                                                $address['address'] .= ' ' . $address['housenumber'];
                                            }
                                            $address['address'] .= ', ' . $address['zip'] . ' ' . $address['city'];
                                            $address['address'] .= ', ' . $address['country'];
                                        }
                                    }

                                    // Update fieldArray if address is set
                                    foreach ($tc as $def => $field) {
                                        if ($def != strtolower($def)) {
                                            continue;
                                        }
                                        if (!$address[$def]) {
                                            continue;
                                        }
                                        $fieldArray[$field] = $address[$def];
                                    }
                                }
                            }
                        }
                    }
                }
                break;
        }
    }
}
