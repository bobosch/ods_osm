<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2023 Alexander Bigga <alexander@bigga.de>
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

namespace Bobosch\OdsOsm\Backend\FormDataProvider;

/**
 * This class is inspired by the FlexFormManipulation::class of the
 * news extension by Georg Ringer
 */

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Manipulate flexforms of ods_osm_pi to ensure only available
 * tables are allowed to select records.
 */
class FlexFormManipulation implements FormDataProviderInterface
{
    /**
     * Manipulate data of ods_osm_pi plugin.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result): array
    {
        if ($result['tableName'] === 'tt_content'
            && $result['databaseRow']['CType'] === 'list'
            && $result['databaseRow']['list_type'] === 'ods_osm_pi1'
            && is_array($result['processedTca']['columns']['pi_flexform']['config']['ds'])
        ) {
            // get flexform data structure
            $dataStructure = $result['processedTca']['columns']['pi_flexform']['config']['ds'];

            // check / manipulate data structure
            $dataStructure = $this->fixAllowedTables($dataStructure);

            // set checked data structure
            $result['processedTca']['columns']['pi_flexform']['config']['ds'] = $dataStructure;
        }

        return $result;
    }

    /**
     * Check for optionally installed extensions and add the
     * corresponding tables to the list of allowed tables for marker.
     *
     * @param array &$dataStructure flexform structure
     * @return array Modified structure
     */
    protected function fixAllowedTables(array $dataStructure): array
    {
        $markerAllowedTables = [ 'fe_users', 'fe_groups', 'pages', 'sys_category', 'tx_odsosm_track', 'tx_odsosm_vector' ];
        $markerPopupInitialAllowedTables = [ 'fe_users' ];

        $extensions = [
            'tt_address' => 'tt_address',
            'calendarize' => 'tx_calendarize_domain_model_event'
        ];

        foreach ($extensions as $extension => $table) {
            if (ExtensionManagementUtility::isLoaded($extension)) {
                $markerAllowedTables[] = $table;
                $markerPopupInitialAllowedTables[] = $table;
            }
        }

        // set manipulated value for marker -> config -> allowed
        if ($dataStructure['sheets']['sDEF']['ROOT']['el']['marker']['config']['allowed'] ?? false) {
            $dataStructure['sheets']['sDEF']['ROOT']['el']['marker']['config']['allowed'] = implode(',', $markerAllowedTables);
        }

        // set manipulated value for marker_popup_initial -> config -> allowed
        if ($dataStructure['sheets']['sDEF']['ROOT']['el']['marker_popup_initial']['config']['allowed'] ?? false) {
            $dataStructure['sheets']['sDEF']['ROOT']['el']['marker_popup_initial']['config']['allowed'] = implode(',', $markerPopupInitialAllowedTables);
        }

        return $dataStructure;
    }

}
