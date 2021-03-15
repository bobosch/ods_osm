<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'OpenStreetMap',
    'description' => 'Add an interactive OpenStreetMap map to your website. Can also show other OpenLayers compatible maps.',
    'author' => 'Robert Heel',
    'author_email' => 'typo3@bobosch.de',
    'category' => 'plugin',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' =>  [],
    ],
    'createDirs' => 'uploads/tx_odsosm/map',
    'state' => 'stable',
    'uploadfolder' => 1,
    'version' => '3.2.0',
];
