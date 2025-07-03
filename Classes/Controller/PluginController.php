<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Robert Heel <typo3@bobosch.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace Bobosch\OdsOsm\Controller;

use Bobosch\OdsOsm\Div;
use Bobosch\OdsOsm\Provider\BaseProvider;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Plugin 'OpenStreetMap' for the 'ods_osm' extension.
 *
 * @author    Robert Heel <typo3@bobosch.de>
 * @package    TYPO3
 * @subpackage    tx_odsosm
 */
class PluginController
{
    protected array $config = [];

    protected array $hooks = [];

    protected array $lats = [];

    protected array $lons = [];

    /** @var ConnectionPool */
    protected $connectionPool;

    /** @var BaseProvider */
    protected $library;

    protected ContentObjectRenderer $contentObjectRenderer;

    public function setContentObjectRenderer(ContentObjectRenderer $contentObjectRenderer): void
    {
        $this->contentObjectRenderer = $contentObjectRenderer;
    }

    /**
     * The main method of the PlugIn
     *
     * @param string $content The PlugIn content
     * @param array $conf The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main(string $content, array $conf): string
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $this->init($conf);

        if ($this->config['marker'] || $this->config['no_marker']) {
            return $this->getMap();
        }

        return $content;
    }

    public function init($conf): void
    {
        $this->initializeFlexFormOfPlugin(); // Init FlexForm configuration for plugin

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['class.tx_odsosm_pi1.php'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ods_osm']['class.tx_odsosm_pi1.php'] as $classRef) {
                $this->hooks[] = GeneralUtility::makeInstance($classRef);
            }
        }

        /* --------------------------------------------------
        Configuration may be done in different places
        - FlexForm ($flex)
        - TypoScript ($conf)
        - extension settings
        -------------------------------------------------- */

        $flex = [];
        $options = [
            'cluster',
            'cluster_radius',
            'height',
            'lat',
            'layer',
            'leaflet_layer',
            'library',
            'lon',
            'marker',
            'marker_popup_initial',
            'mouse_position',
            'openlayers_layer',
            'base_layer',
            'overlays',
            'overlays_active',
            'position',
            'show_layerswitcher',
            'layerswitcher_activationMode',
            'show_scalebar',
            'show_fullscreen',
            'show_popups',
            'staticmap_layer',
            'use_coords_only_nomarker',
            'width',
            'zoom',
            'enable_scrollwheelzoom',
            'enable_dragging',
        ];
        // fill flex array, if there is a flexform data available
        if ($this->contentObjectRenderer->data['pi_flexform'] ?? null) {
            foreach ($options as $option) {
                $value = $this->getValueFromFlexForm($this->contentObjectRenderer->data['pi_flexform'] ?? null, $option);
                switch ($option) {
                    case 'lat':
                    case 'lon':
                        if ($value != 0) {
                            $flex[$option] = $value;
                        }

                        break;
                    case 'marker':
                    case 'marker_popup_initial':
                        $flex[$option] = $this->splitGroup($value, 'tt_address');
                        break;
                    default:
                        $flex[$option] = $value;
                        break;
                }
            }

            if ($flex['library'] === 'staticmap' && !empty($flex['staticmap_layer'])) {
                $flex['layer'] = $flex['staticmap_layer'];
            } elseif (!empty($flex['base_layer'])) {
                $flex['layer'] = $flex['base_layer'];
            }
        }

        // merge configs together into $this->config
        // 1. get extension configuration
        $this->config = Div::getConfig();
        // 2. get TypoScript settings
        ArrayUtility::mergeRecursiveWithOverrule($this->config, $conf);
        // 3. merge Flexform settings, but skip empty values.
        ArrayUtility::mergeRecursiveWithOverrule($this->config, $flex, true, false);

        if (!is_array($this->config['marker'] ?? null)) {
            $this->config['marker'] = [];
        }

        if (is_array($conf['marker.'])) {
            foreach ($conf['marker.'] as $name => $value) {
                if (is_string($value) && ($value !== '' && $value !== '0')) {
                    if (!is_array($this->config['marker'][$name] ?? null)) {
                        $this->config['marker'][$name] = [];
                    }

                    $this->config['marker'][$name] = array_merge($this->config['marker'][$name], explode(',', $value));
                }
            }
        }

        $this->config['layer'] = explode(',', $this->config['layer'] . (empty($this->config['overlays']) ? '' : ',' . $this->config['overlays']));

        if (is_numeric($this->config['height'])) {
            $this->config['height'] .= 'px';
        }

        if (is_numeric($this->config['width'])) {
            $this->config['width'] .= 'px';
        }

        $this->config['layers_visible'] = $this->config['show_layerswitcher'] ?? false ? [] : $this->config['layer'];

        if ($this->config['external_control'] ?? false) {
            if ($GLOBALS['TYPO3_REQUEST']->getParsedBody()['lon'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['lon'] ?? null) {
                $this->config['lon'] = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['lon'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['lon'] ?? null;
            }

            if ($GLOBALS['TYPO3_REQUEST']->getParsedBody()['lat'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['lat'] ?? null) {
                $this->config['lat'] = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['lat'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['lat'] ?? null;
            }

            if ($GLOBALS['TYPO3_REQUEST']->getParsedBody()['zoom'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['zoom'] ?? null) {
                $this->config['zoom'] = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['zoom'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['zoom'] ?? null;
            }

            if ($GLOBALS['TYPO3_REQUEST']->getParsedBody()['layers'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['layers'] ?? null) {
                $this->config['layers_visible'] = explode(',', $GLOBALS['TYPO3_REQUEST']->getParsedBody()['layers'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['layers'] ?? null);
            }

            if ($GLOBALS['TYPO3_REQUEST']->getParsedBody()['records'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['records'] ?? null) {
                $this->config['marker'] = $this->splitGroup($GLOBALS['TYPO3_REQUEST']->getParsedBody()['records'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['records'] ?? null, 'tt_address');
            }
        }

        // If EXT:calendarize is installed and the single view is called, we try to fetch the right event.
        if (ExtensionManagementUtility::isLoaded('calendarize') && (($GLOBALS['TYPO3_REQUEST']->getParsedBody()['tx_calendarize_calendar'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['tx_calendarize_calendar'] ?? null)['index'] ?? false)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_calendarize_domain_model_index');
            $result = $queryBuilder
                ->select('foreign_uid')
                ->from('tx_calendarize_domain_model_index')
                ->where(
                    $queryBuilder->expr()->eq(
                        'tx_calendarize_domain_model_index.uid',
                        $queryBuilder->createNamedParameter((int) ($GLOBALS['TYPO3_REQUEST']->getParsedBody()['tx_calendarize_calendar'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['tx_calendarize_calendar'] ?? null)['index'], Connection::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->executeQuery();
            if ($row = $result->fetchAssociative()) {
                $this->config['marker']['tx_calendarize_domain_model_event'][] = $row['foreign_uid'];
            }
        }

        $this->config['id'] = 'osm_' . ($this->contentObjectRenderer->data['uid'] ?? uniqid());

        $this->config['marker'] = $this->extractGroup($this->config['marker']);

        // Show this marker's popup intially
        if (is_array($this->config['marker_popup_initial'])) {
            foreach ($this->config['marker_popup_initial'] as $table => $records) {
                foreach ($records as $uid) {
                    if (isset($this->config['marker'][$table][$uid])) {
                        $this->config['marker'][$table][$uid]['initial_popup'] = true;
                    }
                }
            }
        }

        // Library
        if (empty($this->config['library'])) {
            $this->config['library'] = 'leaflet';
        }

        $this->library = GeneralUtility::makeInstance('Bobosch\\OdsOsm\\Provider\\' . GeneralUtility::underscoredToUpperCamelCase($this->config['library']));
        $this->library->init($this->config);
        $this->library->cObj = $this->contentObjectRenderer;
    }

    protected function initializeFlexFormOfPlugin()
    {
        $field = 'pi_flexform';
        // Converting flexform data into array
        $fieldData = $this->contentObjectRenderer->data[$field] ?? null;
        if (!is_array($fieldData) && $fieldData) {
            $this->contentObjectRenderer->data[$field] = GeneralUtility::xml2array((string)$fieldData);
            if (!is_array($this->contentObjectRenderer->data[$field])) {
                $this->contentObjectRenderer->data[$field] = [];
            }
        }
    }

    /**
     * Return value from somewhere inside a FlexForm structure
     *
     * @param array $flexFormData FlexForm data
     * @param string $fieldName Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
     * @param string $sheet Sheet pointer, eg. "sDEF
     * @param string $lang Language pointer, eg. "lDEF
     * @param string $value Value pointer, eg. "vDEF
     * @return string|null The content.
     */
    protected function getValueFromFlexForm(array $flexFormData, $fieldName, $sheet = 'sDEF', $lang = 'lDEF', $value = 'vDEF')
    {
        $sheetArray = $flexFormData['data'][$sheet][$lang] ?? '';
        if (is_array($sheetArray)) {
            return $this->getValueFromFlexFormSheetArray($sheetArray, explode('/', $fieldName), $value);
        }

        return null;
    }

    /**
     * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
     *
     * @param array $sheetArray Multidimensional array, typically FlexForm contents
     * @param array $fieldNameArr Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array and return element number X (whether this is right behavior is not settled yet...)
     * @param string $value Value for outermost key, typ. "vDEF" depending on language.
     * @return mixed The value, typ. string.
     * @internal
     * @see getValueFromFlexForm()
     */
    protected function getValueFromFlexFormSheetArray($sheetArray, $fieldNameArr, $value)
    {
        $tempArr = $sheetArray;
        foreach ($fieldNameArr as $v) {
            if (MathUtility::canBeInterpretedAsInteger($v)) {
                if (is_array($tempArr)) {
                    $c = 0;
                    foreach ($tempArr as $values) {
                        if ($c == $v) {
                            $tempArr = $values;
                            break;
                        }

                        $c++;
                    }
                }
            } elseif (isset($tempArr[$v])) {
                $tempArr = $tempArr[$v];
            }
        }

        return $tempArr[$value] ?? '';
    }

    protected function splitGroup($group, $default = ''): array
    {
        $groups = explode(',', (string) $group);
        $recordIds = [];
        foreach ($groups as $tempGroup) {
            $item = GeneralUtility::revExplode('_', $tempGroup, 2);
            if (count($item) === 1) {
                $recordIds[$default][] = $item[0];
            } else {
                $recordIds[$item[0]][] = $item[1];
            }
        }

        return $recordIds;
    }

    private function extractGroup(array $recordIds): array
    {
        $tables = Div::getTableConfig();

        // if no markers are set, select current page to find records on it
        if ($recordIds === []) {
            //@extensionScannerIgnoreLine
            $recordIds['pages'] = [$GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.page.information')->getId()];
        }

        // get all marker records on configured page.
        if (!empty($recordIds['pages'])) {
            foreach (array_keys($tables) as $table) {
                if ($table !== 'tt_content') {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable($table);

                    $result = $queryBuilder
                        ->select($table . '.uid')
                        ->from($table)->where($queryBuilder->expr()->in(
                        $table . '.pid',
                        $queryBuilder->createNamedParameter(
                            $recordIds['pages'],
                            Connection::PARAM_INT_ARRAY
                        )
                    ))->executeQuery();

                    while ($resArray = $result->fetchAssociative()) {
                        if (!in_array($resArray['uid'], $recordIds[$table] ?? [])) {
                            $recordIds[$table][] = $resArray['uid'];
                        }
                    }
                }
            }
        }

        // get marker records from db
        $records = [];
        foreach ($recordIds as $table => $items) {
            $tc = $tables[$table] ?? [];
            $connection = $this->connectionPool
                ->getConnectionForTable($table)
                ->createSchemaManager()
                ->tablesExist([$table]);

            // table seems not available --> break to avoid exceptions
            if ($connection === false) {
                break;
            }

            foreach ($items as $item) {
                $item = (int) $item;

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($table);

                // hint: language overlay e.g. of tt_address records is done automatically
                $result = $queryBuilder
                    ->select('*')
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->eq(
                            $table . '.uid',
                            $queryBuilder->createNamedParameter($item, Connection::PARAM_INT)
                        )
                    )->setMaxResults(1)->executeQuery();

                if ($row = $result->fetchAssociative()) {
                    // Group with relation to a field
                    if (is_array($tc['FIND_IN_SET'] ?? null)) {
                        foreach ($tc['FIND_IN_SET'] as $t => $f) {
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                                ->getQueryBuilderForTable($table);

                            $result = $queryBuilder
                                ->select('*')
                                ->from($t)
                                ->where(
                                    $queryBuilder->expr()->inSet(
                                        $f,
                                        $queryBuilder->createNamedParameter(
                                            $item,
                                            Connection::PARAM_INT
                                        )
                                    )
                                )->executeQuery();

                            while ($resArray = $result->fetchAssociative()) {
                                $records[$t][$resArray['uid']] = $resArray;
                                $records[$t][$resArray['uid']]['group_uid'] = $table . '_' . $row['uid'];
                                $records[$t][$resArray['uid']]['group_title'] = $row['title'];
                                $records[$t][$resArray['uid']]['group_description'] = $row['description'];
                                $records[$t][$resArray['uid']]['tx_odsosm_marker'] = $row['tx_odsosm_marker'];
                                $records[$t][$resArray['uid']]['longitude'] = $resArray[$tables[$t]['lon']];
                                $records[$t][$resArray['uid']]['latitude'] = $resArray[$tables[$t]['lat']];
                            }
                        }
                    }

                    // Group with mm relation
                    if (is_array($tc['MM'] ?? null)) {
                        foreach ($tc['MM'] as $t => $f) {
                            $local = $f['local'];
                            $mm = $f['mm'];
                            $foreign = $f['foreign'];

                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($foreign);
                            $constraints = Div::getConstraintsForQueryBuilder($foreign, $this->contentObjectRenderer, $queryBuilder);

                            // set uid
                            $constraints[] = $queryBuilder->expr()->eq($local . '.uid', $queryBuilder->createNamedParameter($item, Connection::PARAM_INT));

                            $rows = $queryBuilder
                                ->select($foreign . '.*')
                                ->from($foreign)
                                ->join(
                                    $foreign,
                                    $mm,
                                    $mm,
                                    $queryBuilder->expr()->eq($foreign . '.uid', $queryBuilder->quoteIdentifier($mm . '.uid_foreign'))
                                )
                                ->join(
                                    $mm,
                                    $local,
                                    $local,
                                    $queryBuilder->expr()->eq($local . '.uid', $queryBuilder->quoteIdentifier($mm . '.uid_local'))
                                )
                                ->where(...$constraints)
                                ->executeQuery()
                                ->fetchAllAssociative();

                            foreach ($rows as $r) {
                                $records[$t][$r['uid']] = $r;
                                $records[$t][$r['uid']]['group_uid'] = $table . '_' . $row['uid'];
                                $records[$t][$r['uid']]['group_title'] = $row['title'];
                                $records[$t][$r['uid']]['group_description'] = $row['description'];
                                $records[$t][$r['uid']]['tx_odsosm_marker'] = $row['tx_odsosm_marker'];
                                $records[$t][$r['uid']]['longitude'] = $r[$tables[$t]['lon']];
                                $records[$t][$r['uid']]['latitude'] = $r[$tables[$t]['lat']];
                            }
                        }
                    }

                    // Marker
                    if (isset($tc['lon'])) {
                        $records[$table][$item] = $row;
                        $records[$table][$item]['longitude'] = $row[$tc['lon']];
                        $records[$table][$item]['latitude'] = $row[$tc['lat']];
                    }

                    // Special element
                    if ($tc === true && $row) {
                        $records[$table][$item] = $row;
                    }
                }
            }
        }

        // Hook to change records
        foreach ($this->hooks as $hook) {
            if (method_exists($hook, 'changeRecords')) {
                $hook->changeRecords($records, $recordIds, $this);
            }
        }

        // get lon & lat
        foreach ($records as $table => $items) {
            foreach ($items as $uid => $row) {
                switch ($table) {
                    case 'tx_odsosm_track':
                    case 'tx_odsosm_vector':
                        if ($row['min_lon']) {
                            $this->lons[] = (float) $row['min_lon'];
                            $this->lats[] = (float) $row['min_lat'];
                            $this->lons[] = (float) $row['max_lon'];
                            $this->lats[] = (float) $row['max_lat'];
                        } else {
                            unset($records[$table][$uid]);
                        }

                        break;
                    default:
                        $this->lons[] = (float) $row['longitude'];
                        $this->lats[] = (float) $row['latitude'];
                        break;
                }
            }
        }

        // No markers
        if ($this->lons === [] && $this->config['no_marker'] == 1) {
            if (($this->config['lon'] ?? false) && ($this->config['lat'] ?? false)) {
                $this->lons[] = $this->config['lon'];
                $this->lats[] = $this->config['lat'];
            } else {
                $this->lons[] = $this->config['default_lon'];
                $this->lats[] = $this->config['default_lat'];
            }
        }

        return $records;
    }

    public function getMap(): string
    {
        /* ==================================================
        Marker
        ================================================== */
        // Get icon records
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_odsosm_marker');

        $result = $queryBuilder
            ->select('*')
            ->from('tx_odsosm_marker')->executeQuery();

        $icons = [];
        while ($resArray = $result->fetchAssociative()) {
            $icons[$resArray['uid']] = $resArray;
        }

        // Prepare markers
        $markers = $this->config['marker'];
        $local_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        foreach ($markers as $table => &$items) {
            foreach ($items as &$item) {
                $popup = is_string($this->config['popup.'][$table] ?? null) && is_array($this->config['popup.'][$table . '.'] ?? null) && $this->config['show_popups'];
                $icon = is_string($this->config['icon.'][$table] ?? null) && is_array($this->config['icon.'][$table . '.'] ?? null);
                if ($popup || $icon) {
                    $local_cObj->start($item, $table);
                }

                // Add popup information
                if ($popup) {
                    $item['popup'] = $local_cObj->cObjGetSingle($this->config['popup.'][$table], $this->config['popup.'][$table . '.']);
                }

                // Add icon information
                if ($item['tx_odsosm_marker'] ?? null) {
                    $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
                    $fileObjects = $fileRepository->findByRelation('tx_odsosm_marker', 'icon', $item['tx_odsosm_marker']);
                    if ($fileObjects) {
                        $file = $fileObjects[0];
                    } else {
                        continue;
                    }

                    $item['tx_odsosm_marker'] = $icons[$item['tx_odsosm_marker']];
                    $item['tx_odsosm_marker']['icon'] = $file;
                    $item['tx_odsosm_marker']['type'] = 'image';
                } elseif ($icon) {
                    if ($this->config['icon.'][$table] === 'IMAGE') {
                        $info = $this->contentObjectRenderer->getImgResource(
                            $this->config['icon.'][$table . '.']['file'] ?? '',
                            $this->config['icon.'][$table . '.']['file.'] ?? []
                        );
                        $item['tx_odsosm_marker'] = [
                            'icon' => $info['processedFile'],
                            'type' => 'image',
                            'size_x' => $info[0],
                            'size_y' => $info[1],
                            'offset_x' => -$info[0] / 2,
                            'offset_y' => -$info[1],
                        ];
                    } elseif ($this->config['icon.'][$table] === 'TEXT') {
                        $conf = $this->config['icon.'][$table . '.'];
                        $html = $local_cObj->cObjGetSingle(
                            $this->config['icon.'][$table],
                            $this->config['icon.'][$table . '.']
                        );
                        $item['tx_odsosm_marker'] = [
                            'icon' => $html,
                            'type' => 'html',
                            'size_x' => $conf['size_x'],
                            'size_y' => $conf['size_y'],
                            'offset_x' => $conf['offset_x'],
                            'offset_y' => $conf['offset_y'],
                        ];
                    }
                }
            }
        }

        /* ==================================================
        Layers
        ================================================== */
        $layers = [];
        $baseLayers = [];
        if (!in_array(implode(',', $this->config['layer']), ['', '0'], true)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_odsosm_layer');

            $result = $queryBuilder
                ->select('*')
                ->addSelectLiteral(
                    'FIELD(uid, ' . implode(',', $this->config['layer']) . ') as custom_sorting'
                )
                ->from('tx_odsosm_layer')
                ->where(
                    $queryBuilder->expr()->in(
                        'tx_odsosm_layer.uid',
                        $queryBuilder->createNamedParameter(
                            $this->config['layer'],
                            Connection::PARAM_INT_ARRAY
                        )
                    ),
                )
                ->orderBy('custom_sorting')
                ->orderBy('sorting')
                ->executeQuery();

            while ($resArray = $result->fetchAssociative()) {
                $baseLayers[$resArray['uid']] = $resArray;
                $baseLayers[$resArray['uid']]['visible'] = false;
            }

            // set visible flag
            if (isset($this->config['layers_visible'])) {
                foreach ($this->config['layers_visible'] as $key) {
                    if ($baseLayers[$key] ?? false) {
                        $baseLayers[$key]['visible'] = true;
                    }
                }
            }

            if (isset($this->config['overlays_active'])) {
                foreach (explode(',', $this->config['overlays_active']) as $key) {
                    if ($baseLayers[$key] ?? false) {
                        $baseLayers[$key]['visible'] = true;
                    }
                }
            }
        }

        foreach ($baseLayers as $layer) {
            if ($layer['overlay'] == 1) {
                $layers[1][] = $layer;
            } else {
                $layers[0][] = $layer;
            }
        }

        // $layers[0] = $baseLayers;
        // $layers[1] = $overlays;
        $layers[2] = []; // markers will be filled in provider classes

        /* ==================================================
        Map center
        ================================================== */
        if ($this->config['lon'] ?? false || $this->config['use_coords_only_nomarker'] ?? false) {
            $lon = array_sum($this->lons) / count($this->lons);
            $lat = array_sum($this->lats) / count($this->lats);
        } else {
            $lon = (float)($this->config['lon'] ?? $this->config['default_lon']);
            $lat = (float)($this->config['lat'] ?? $this->config['default_lat']);
        }

        $zoom = (int)$this->config['zoom'];

        /* ==================================================
        Map
        ================================================== */
        $content = $this->library->getMap($layers, $markers, $lon, $lat, $zoom);
        $script = $this->library->getScript();
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        if ($script) {
            match ($this->config['JSlibrary']) {
                'jquery' => $pageRenderer->addJsFooterInlineCode(
                    $this->config['id'],
                    '$(document).ready(function() {' . $script . '});'
                ),
                default => $pageRenderer->addJsFooterInlineCode(
                    $this->config['id'],
                    'document.addEventListener("DOMContentLoaded", function(){' . $script . '}, false);'
                ),
            };
        }

        return $content;
    }
}
