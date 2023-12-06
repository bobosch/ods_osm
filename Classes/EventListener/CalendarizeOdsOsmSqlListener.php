<?php

declare(strict_types=1);

namespace Bobosch\OdsOsm\EventListener;

use HDNET\Calendarize\Register;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class CalendarizeOdsOsmSqlListener
{
    public function __invoke(AlterTableDefinitionStatementsEvent $event): void
    {
        if (ExtensionManagementUtility::isLoaded('calendarize')) {
            $event->addSqlData($this->getCalendarizeDatabaseString());
        }
    }

    /**
     * Get the calendarize string for the registered tables.
     *
     * @return string
     */
    protected function getCalendarizeDatabaseString()
    {
        $sql = [];
        foreach (Register::getRegister() as $configuration) {
            if ($configuration['tableName'] == 'tx_calendarize_domain_model_event') {
                $sql[] = "CREATE TABLE " . $configuration['tableName'] . " (
                    tx_odsosm_lon decimal(9,6) NOT NULL DEFAULT '0.000000',
                    tx_odsosm_lat decimal(8,6) NOT NULL DEFAULT '0.000000',
                );";
            }
        }

        return implode(LF, $sql);
    }
}
