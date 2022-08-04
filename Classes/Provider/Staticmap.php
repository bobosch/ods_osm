<?php

namespace Bobosch\OdsOsm\Provider;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Core\Environment;

class Staticmap extends BaseProvider
{
    protected $uploadPath = 'fileadmin/tx_odsosm/staticmap';

    public function getMap($layers, $markers, $lon, $lat, $zoom)
    {
        foreach ($markers as $table => $items) {
            foreach ($items as $item) {
                switch ($table) {
                    case 'tx_odsosm_track':
                    case 'tx_odsosm_vector':
                        break;
                    default:
                        $lon = $item['longitude'];
                        $lat = $item['latitude'];
                        if (is_array($item['tx_odsosm_marker'])) {
                            $marker = $item['tx_odsosm_marker'];
                            $icon = $marker['icon'];
                        } else {
                            $marker = array('size_x' => 21, 'size_y' => 25, 'offset_x' => -11, 'offset_y' => -25);
                            $icon = 'EXT:ods_osm/Resources/Public/OpenLayers/img/marker.png';
                        }
                        break 3;
                }
            }
        }

        $markerUrl = array(
            '###lon###' => $lon,
            '###lat###' => $lat,
            '###zoom###' => $zoom,
            '###width###' => intval($this->config['width']),
            '###height###' => intval($this->config['height']),
        );

        $layer = array_shift($layers);
        $url = strtr($layer[0]['static_url'], $markerUrl);

        $this->uploadPath = Environment::getPublicPath() . '/'  . $this->uploadPath;
        if (!is_dir($this->uploadPath)) {
            GeneralUtility::mkdir_deep($this->uploadPath);
        }

        $filename = $this->uploadPath . md5($url) . '.png';

        // Cache image
        $cache = false;
        if (file_exists($filename)) {
            $cache = filectime($filename) > time() - 7 * 24 * 60 * 60;
        }
        if (!$cache) {
            $referer = $_SERVER['HTTP_REFERER'];
            $opts = array(
                'http'=>array(
                    'header'=>array("Referer: $referer\r\n")
                )
            );
            $context = stream_context_create($opts);
            $image = file_get_contents($url, false, $context);
            if ($image) {
                file_put_contents($filename, $image);
            }
        }

        // Generate image tag
//		$config['file'] = $filename;
        $config = array(
            'file' => 'GIFBUILDER',
            'file.' => array(
                'format' => 'png',
                'XY' => '[10.w],[10.h]',
                '10' => 'IMAGE',
                '10.' => array(
                    'file' => $filename,
                ),
                '20' => 'IMAGE',
                '20.' => array(
                    'offset' => ($this->config['width'] / 2 + $marker['offset_x']) . ',' . ($this->config['height'] / 2 + $marker['offset_y']),
                    'file' => $icon,
                ),
            ),
        );

        $content = $this->cObj->cObjGetSingle('IMAGE', $config);

        return ($content);
    }
}
