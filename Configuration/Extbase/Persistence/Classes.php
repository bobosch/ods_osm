<?php
declare(strict_types=1);

use Bobosch\OdsOsm\Domain\Model\Event;

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('calendarize')) {
    return [
        Event::class => [
            'tableName' => 'tx_calendarize_domain_model_event',
        ],
    ];
}
