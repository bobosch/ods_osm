<?php

namespace Bobosch\OdsOsm;

use \geoPHP;

class TceMain
{
    var $lon = array();
    var $lat = array();

    // ['t3lib/class.t3lib_tcemain.php']['processDatamapClass']
    function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, $obj)
    {
    }

    // ['t3lib/class.t3lib_tcemain.php']['processDatamapClass']
    function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, $obj)
    {
        switch ($table) {
            case 'tx_odsosm_track':
                $filename = PATH_site . 'uploads/tx_odsosm/' . $fieldArray['file'];
                if ($fieldArray['file'] && file_exists($filename)) {
                    // If extension is installed via composer, the class geoPHP is already known.
                    // Otherwise we use the (older) copy out of the extension folder.
                    if (!class_exists(geoPHP::class)) {
                        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm', 'Resources/Public/geoPHP/geoPHP.inc');
                    }
                    $polygon = geoPHP::load(file_get_contents($filename), pathinfo($filename, PATHINFO_EXTENSION));
                    $box = $polygon->getBBox();
                    $fieldArray['min_lon'] = sprintf('%01.6f', $box['minx']);
                    $fieldArray['min_lat'] = sprintf('%01.6f', $box['miny']);
                    $fieldArray['max_lon'] = sprintf('%01.6f', $box['maxx']);
                    $fieldArray['max_lat'] = sprintf('%01.6f', $box['maxy']);
                }
                break;

            case 'tx_odsosm_marker':
                if ($fieldArray['icon'] && file_exists(PATH_site . 'uploads/tx_odsosm/' . $fieldArray['icon'])) {
                    $size = getimagesize(PATH_site . 'uploads/tx_odsosm/' . $fieldArray['icon']);
                    $fieldArray['size_x'] = $size[0];
                    $fieldArray['size_y'] = $size[1];
                    $fieldArray['offset_x'] = -round($size[0] / 2);
                    $fieldArray['offset_y'] = -$size[1];
                }
                break;

            case 'tx_odsosm_vector':
                if (!empty($fieldArray['data'])) {
                    $this->lon = array();
                    $this->lat = array();

                    $vector = json_decode($fieldArray['data']);
                    foreach ($vector->geometry->coordinates[0] as $coordinates) {
                        $this->lon[] = $coordinates[0];
                        $this->lat[] = $coordinates[1];
                    }

                    $fieldArray['min_lon'] = sprintf('%01.6f', min($this->lon));
                    $fieldArray['min_lat'] = sprintf('%01.6f', min($this->lat));
                    $fieldArray['max_lon'] = sprintf('%01.6f', max($this->lon));
                    $fieldArray['max_lat'] = sprintf('%01.6f', max($this->lat));
                }
                break;
            default:
                $tc = Div::getTableConfig($table);
                if (isset($tc['lon'])) {
                    if (
                        (isset($tc['address']) && $fieldArray[$tc['address']]) ||
                        (isset($tc['street']) && $fieldArray[$tc['street']]) ||
                        (isset($tc['zip']) && $fieldArray[$tc['zip']]) ||
                        (isset($tc['city']) && $fieldArray[$tc['city']])
                    ) {
                        $config = Div::getConfig(array('autocomplete'));
                        // Search coordinates
                        if ($config['autocomplete']) {
                            // Generate address array with standard keys
                            $address = array();
                            foreach ($tc as $def => $field) {
                                if ($def == strtolower($def)) {
                                    $address[$def] = $obj->datamap[$table][$id][$field];
                                }
                            }
                            if ($config['autocomplete'] == 2 || floatval($address['longitude']) == 0) {
                                $ll = Div::updateAddress($address);
                                if ($ll) {
                                    // Optimize address
                                    $address['lon'] = sprintf($tc['FORMAT'], $address['lon']);
                                    $address['lat'] = sprintf($tc['FORMAT'], $address['lat']);
                                    if (isset($tc['address']) && !isset($tc['street'])) {
                                        if ($address['street']) {
                                            $address['address'] = $address['street'];
                                            if ($address['housenumber']) {
                                                $address['address'] .= ' ' . $address['housenumber'];
                                            }
                                        }
                                    }

                                    // Update fieldArray if address is set
                                    foreach ($tc as $def => $field) {
                                        if ($def == strtolower($def)) {
                                            if ($address[$def]) {
                                                $fieldArray[$field] = $address[$def];
                                            }
                                        }
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

?>