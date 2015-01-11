<?php
/**
 * Class for updating the db
 */
class ext_update {
	/**
	 * Main function, returning the HTML content
	 *
	 * @return string HTML
	 */
	function main()	{
		$content = '';

		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$databaseUpdateUtility = $objectManager->get('Bobosch\\OdsOsm\\Utility\\DatabaseUpdateUtility');
		$status=$databaseUpdateUtility->doUpdate();
		
		if($status) return 'Update successful';
	}

	function access() {
		return t3lib_div::compat_version('6.0');
	}
}
?>