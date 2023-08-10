<?php
declare(strict_types=1);
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

use Bobosch\OdsOsm\Domain\Model\Event;

if (ExtensionManagementUtility::isLoaded('calendarize')) {
    return [
        Event::class => [
            'tableName' => 'tx_calendarize_domain_model_event',
        ],
    ];
}
