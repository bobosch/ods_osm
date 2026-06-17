<?php

declare(strict_types=1);

defined('TYPO3') || die;

return [
    'dependencies' => [
        'backend',
    ],
    'imports' => [
        '@bobosch/ods-osm/' => 'EXT:ods_osm/Resources/Public/JavaScript/',
    ],
];
