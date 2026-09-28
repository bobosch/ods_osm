<?php

declare(strict_types=1);

/*
 *  Copyright notice
 *
 *  (c) 2022 Alexander Bigga <alexander@bigga.de>
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
 */

namespace Bobosch\OdsOsm\Updates;

use Doctrine\DBAL\Types\Type;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Fix value of datbase field tx_odsosm_marker.icon so that the schema conversion from
 * text to int will not fail
 * @see
 */
#[UpgradeWizard('odsOsmFixMarkerIconValueInDb')]
class FixMarkerIconValueInDb implements UpgradeWizardInterface
{
    protected const TABLE = 'tx_odsosm_marker';

    protected const FIELDNAME = 'icon';

    /**
     * Return the identifier for this wizard
     * This must be the same string as used in the ext_localconf class registration
     */
    public function getIdentifier(): string
    {
        return 'odsOsmMigrateMarkerIconValueInDb';
    }

    /**
     * Return the speaking name of this wizard
     */
    public function getTitle(): string
    {
        return 'EXT:ods_osm: Fix value of tx_odsosm_marker.icon (if empty string)';
    }

    /**
     * Return the description for this wizard
     */
    public function getDescription(): string
    {
        return 'This wizard changes the value of tx_odsosm_marker.icon (if empty string) changing it ot "0".'
            . ' This makes it possible to perform the database schema update from text to int'
            . ' This update wizzard will currently only run in TYPO3 version 13';
    }

    /**
     * Called when a wizard reports that an update is necessary
     */
    public function executeUpdate(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
           ->getQueryBuilderForTable(self::TABLE);
        $updateResult = $queryBuilder->update(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'icon',
                    $queryBuilder->createNamedParameter('')
                )
            )->set('icon', '0')
            ->executeStatement();

        // exit with false if at least one update statement is not successful
        if (! ((bool) $updateResult)) {
            return false;
        }

        return true;
    }

    /**
     * Is an update necessary?
     *
     * Looks for ods_osm plugins in tt_content table to be migrated
     */
    public function updateNecessary(): bool
    {
        // check if TYPO3 version is v13, some of the following may not work for older or newer TYPO3 version
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if ($typo3Version->getMajorVersion() !== 13) {
            return false;
        }

        // check if tx_odsosm_marker.icon is still type text, if not no update necessary!
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable(self::TABLE);
        $schemaManager = $connection->createSchemaManager();
        $databaseColumns = $schemaManager->listTableColumns(self::TABLE);

        if (isset($databaseColumns[self::FIELDNAME])) {
            // 4. Get the specific column type object (e.g., StringType, IntegerType)
            $columnType = $databaseColumns[self::FIELDNAME]->getType();

            // Get the internal Doctrine type name string (e.g., "string", "integer", "text")
            $typeName = Type::lookupName($columnType);
            if ($typeName !== 'text') {
                // no update necessary because already converted from text
                return false;
            }
        } else {
            // unable to perform update
            return false;
        }

        // check if tx_odsosm_marker.icon still contains empty string: if not no update necessary
        $queryBuilder = $connectionPool->getQueryBuilderForTable(self::TABLE);
        $row = $queryBuilder->select('*')
            ->from(self::TABLE)
            ->where(
                // check for empty string
                $queryBuilder->expr()->eq('icon', $queryBuilder->createNamedParameter(''))
            )->setMaxResults(1)
            ->executeQuery()
        ->fetchAssociative() ?: [];
        return $row ? true : false;
    }

    /**
     * Returns an array of class names of Prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [];
    }
}
