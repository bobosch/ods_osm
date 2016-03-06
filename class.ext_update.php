<?php
/***************************************************************
 *  Copyright notice
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class ext_update {
	protected $messageArray = array();

	public function access() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('6.0');
	}

	/**
	 * Main update function called by the extension manager.
	 *
	 * @return string
	 */
	public function main() {
		$this->processUpdates();
		return $this->generateOutput();
	}

	/**
	 * Generates output by using flash messages
	 *
	 * @return string
	 */
	protected function generateOutput() {
		$output = '';
		foreach ($this->messageArray as $messageItem) {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\CMS\Core\Messaging\FlashMessage',
				$messageItem[2],
				$messageItem[1],
				$messageItem[0]);
			$output .= $flashMessage->render();
		}

		return $output;
	}
	
	/**
	 * The actual update function. Add your update task in here.
	 *
	 * @return void
	 */
	protected function processUpdates() {
		$this->importStaticData();
		$this->moveField('tt_address','tx_odsosm_lon','longitude');
		$this->moveField('tt_address','tx_odsosm_lat','latitude');
	}
	
	/**
	 * Import static data
	 *
	 * @return int
	 */
	protected function moveField($table,$from,$to) {
		$title = 'Update table "' . $table . '": Move field from "' . $from . '" to "' . $to . '"';
		$message = 'Move data in item ';
		$status = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			$table,
			$from . '>""'
		);

		if ($res) {
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$message.= $row['uid'] . ', ';
				$UPDATEres = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					$table,
					'uid=' . $row['uid'],
					array(
						$from => null,
						$to => $row[$from]
					)
				);
				if (!$UPDATEres) {
					$status = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
				}
			}
		} else {
			$message = 'No data to move.';
		}

		$this->messageArray[] = array($status, $title, $message);
		return $status;
	}

	/**
	 * Import static data
	 *
	 * @return int
	 */
	protected function importStaticData() {
		$title = 'Import static data';
		$message = '';
		$status = NULL;

		$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm');
		$fileContent = explode(LF, \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($extPath.'ext_tables_static+adt.sql'));
		$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE tx_odsosm_layer');
		foreach ($fileContent as $line) {
			$line = trim($line);
			if ($line && preg_match('#^INSERT#i', $line)) {
				if($GLOBALS['TYPO3_DB']->sql_query($line) === false) {
					$message = 'SQL ERROR:' .  $GLOBALS['TYPO3_DB']->sql_error();
					$status = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
				} else {
					$message = 'OK!';
					$status = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;
				}
			}
		}

		$this->messageArray[] = array($status, $title, $message);
		return $status;
	}
}
?>