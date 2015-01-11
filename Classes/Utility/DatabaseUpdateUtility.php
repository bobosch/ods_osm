<?php
namespace Bobosch\OdsOsm\Utility;

/**
 * Utility used by the update script of the base extension and of the language packs
 */
class DatabaseUpdateUtility {

	/**
	 * @var string Name of the extension this class belongs to
	 */
	protected $extensionName = 'OdsOsm';

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Do the language pack update
	 *
	 * @return void
	 */
	public function doUpdate() {
		$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ods_osm');
		$fileContent = explode(LF, \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($extPath.'ext_tables_static+adt.sql'));
		$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE tx_odsosm_layer');
		foreach ($fileContent as $line) {
			$line = trim($line);
			if ($line && preg_match('#^INSERT#i', $line)) {
				$GLOBALS['TYPO3_DB']->sql_query($line);
			}
		}
		return true;
	}
}
?>