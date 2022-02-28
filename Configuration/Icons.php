<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

$iconPath = 'EXT:ods_osm/Resources/Public/Icons/';
return [
    'coordinate-picker-wizard' => [
        'provider' => BitmapIconProvider::class,
        'source' => $iconPath . 'ce_wiz.png',
    ],
    'vectordraw-wizard' => [
        'provider' => BitmapIconProvider::class,
        'source' => $iconPath . 'vector.png',
    ],
    'ods_osm' => [
        'provider' => BitmapIconProvider::class,
        'source' => $iconPath . 'osm.png',
    ],
];
