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

/**
 * Class that adds the wizard icon.
 *
 * @author	Robert Heel <typo3@bobosch.de>
 * @package	TYPO3
 * @subpackage	tx_odsosm
 */
class tx_odsosm_pi1_wizicon {
	/**
	 * Path to locallang file (with : as postfix)
	 *
	 * @var string
	 */
	protected $locallangPath = 'LLL:EXT:ods_osm/locallang.xml:';

	/**
	 * Processing the wizard items array
	 *
	 * @param array $wizardItems
	 * @return array
	 */
	public function proc($wizardItems = array()) {
		$wizardItems['plugins_tx_odsosm_pi1'] = array(
			'icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ods_osm') . 'pi1/ce_wiz.png',
			'title' => $GLOBALS['LANG']->sL($this->locallangPath . 'pi1_title'),
			'description' => $GLOBALS['LANG']->sL($this->locallangPath . 'pi1_plus_wiz_description'),
			'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=ods_osm_pi1',
			'tt_content_defValues' => array(
				'CType' => 'list',
			),
		);

		return $wizardItems;
	}
}
?>