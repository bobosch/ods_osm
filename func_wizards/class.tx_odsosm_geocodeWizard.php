<?php
require_once t3lib_extMgm::extPath('ods_osm') . 'class.tx_odsosm_div.php';

/**
 * Mass-update geo coordinates in address records
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class tx_odsosm_geocodeWizard extends t3lib_extobjbase {
	/**
	 * Main function
	 *
	 * @returnstring Output HTML for the module
	 */
	public function main() {
		global $BACK_PATH, $LANG, $SOBE, $BE_USER, $TYPO3_DB;

		$html = '';
		if (isset($_POST['start']) && isset($_POST['geocode'])) {
			$html .= $this->geocode($_POST['geocode']);
		}

		$html .= $this->overview();

		return $html;
	}

	/**
	 * Run the geocoding process.
	 *
	 * @param string Geocoding mode: "missing" or "all"
	 *
	 * @return string HTML code with geocoding results
	 */
	protected function geocode($mode) {
		if ($mode != 'all' && $mode != 'missing') {
			//wrong mode
			$message = new t3lib_FlashMessage(
				'Invalid geocoding mode', '', t3lib_FlashMessage::ERROR
			);
			return $message->render();
		}

		if ($mode == 'missing') {
			$where = 'tx_odsosm_lon = 0 AND tx_odsosm_lat = 0';
		} else {
			$where = '1';
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*', 'tt_address', $where . ' AND pid = ' . $this->pObj->id
		);

		$count = 0;
		$updated = 0;
		$html = '';
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$oldRow = $row;
			++$count;
			if (!tx_odsosm_div::updateAddress($row)) {
				//not updated
				continue;
			}

			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'tt_address', 'uid = ' . intval($row['uid']),
				array(
					'tx_odsosm_lat' => $row['lat'],
					'tx_odsosm_lon' => $row['lon']
				)
			);

			$err = $GLOBALS['TYPO3_DB']->sql_error();
			if ($err) {
				$message = new t3lib_FlashMessage(
					'SQL error: ' . htmlspecialchars($err), '',
					t3lib_FlashMessage::ERROR
				);
				$html .= $message->render();
			}

			++$updated;
		}

		$message = new t3lib_FlashMessage(
			'Updated ' . $updated . ' of ' . $count . ' address records',
			'',
			$updated == $count
			? t3lib_FlashMessage::OK
			: t3lib_FlashMessage::WARNING
		);
		$html .= $message->render();

		return $html . '<br/><br/>';
	}

	/**
	 * Show overview of uncoded addresses and form to start geocoding
	 *
	 * @return string HTML code
	 */
	protected function overview() {
		global $TYPO3_DB;

		$num = $TYPO3_DB->exec_SELECTcountRows(
			'uid', 'tt_address',
			'tx_odsosm_lon = 0 AND tx_odsosm_lat = 0 AND pid = ' . $this->pObj->id
		);
		$numAll = $TYPO3_DB->exec_SELECTcountRows(
			'uid', 'tt_address', 'pid = ' . $this->pObj->id
		);

		$html = '<p>'
			. '<b>' . $num . '</b> addresses without coordinates on this page'
			. '</p>';
		$html .= '<p>'
			. '<b>' . $numAll . '</b> addresses in total on this page'
			. '</p>';

		$html .= '<br/>'
			. '<form action="' . $this->getFormUrl() . '" method="post">'
			. '<input type="radio" name="geocode" id="geocode-missing" value="missing" checked="checked"/>'
			. '<label for="geocode-missing">Update <b>missing</b> coordinates only</label>'
			. '<br/>'
			. '<input type="radio" name="geocode" id="geocode-all" value="all"/>'
			. '<label for="geocode-all">Update coordinates of <b>all</b> addresses</label>'
			. '<br/>'
			. '<br/>'
			. '<input type="submit" name="start" value="Start geocoding" />';

		return $html;
	}

	/**
	 * @return string
	 */
	protected function getFormUrl() {
		$urlParams = $this->pObj->MOD_SETTINGS;
		$urlParams['id'] = $this->pObj->id;
		return $this->pObj->doc->scriptID . '?' . t3lib_div::implodeArrayForUrl(
			'',
			$urlParams
		);
	}

}
?>
