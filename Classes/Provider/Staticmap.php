<?php

namespace Bobosch\OdsOsm\Provider;

class Staticmap extends BaseProvider
{
    protected $uploadPath = 'uploads/tx_odsosm/';

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
        $url = strtr($layer['static_url'], $markerUrl);
        $filename = $this->uploadPath . 'map/' . md5($url) . '.png';

        // Cache image
        $cache = false;
        if (file_exists($filename)) {
            $cache = filectime($filename) > time() - 7 * 24 * 60 * 60;
        }
        if (!$cache) {
            $image = file_get_contents($url);
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

?>
