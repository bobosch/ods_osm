<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 Alexander Bigga <alexander@bigga.de>
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

namespace Bobosch\OdsOsm\Updates;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Install\Updates\ChattyInterface;

use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Migrate location of marker and track files to new, FAL-based location
 */
class FileLocationUpdater implements UpgradeWizardInterface, ChattyInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ResourceStorage
     */
    protected $storage;

    /**
     * Array with table and fields to migrate
     *
     * @var string
     */
    protected $fieldsToMigrate = [
        'tx_odsosm_marker' => 'icon',
        'tx_odsosm_track' => 'file'
    ];

    /**
     * the source file resides here
     *
     * @var string
     */
    protected $sourcePath = 'uploads/tx_odsosm/';

    /**
     * target folder after migration
     * Relative to fileadmin
     *
     * @var string
     */
    protected $targetPath = '_migrated/tx_odsosm/';

    /**
     * Return the identifier for this wizard
     * This must be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'odsOsmFileLocationUpdater';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'EXT:ods_osm: Migrate used files to FAL';
    }

    /**
     * Get description
     *
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Move marker images and track files of EXT:ods_osm to fileadmin/_migrated/tx_odsosm/ and convert reference in records.';
    }

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $numRecords = $this->falGetRecordsFromTable(true);
        if ($numRecords > 0) {
            return true;
        }
        return false;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $result = true;
        try {
            $numRecords = $this->falGetRecordsFromTable(true);
            if ($numRecords > 0) {
                $this->falPerformUpdate();
            }
        } catch (\Exception $e) {
            // If something goes wrong, migrateField() logs an error
            $result = false;
        }
        return $result;
    }


    /**
     * Get records from table where the field to migrate is not empty (NOT NULL and != '')
     * and also not numeric (which means that it is migrated)
     *
     * Work based on BackendLayoutIconUpdateWizard::class
     *
     * @return array|int
     * @throws \RuntimeException
     */
    protected function falGetRecordsFromTable($countOnly = false)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $allResults = [];
        $numResults = 0;
        foreach(array_keys($this->fieldsToMigrate) as $table) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            try {
                $result = $queryBuilder
                    ->select('uid', 'pid', $this->fieldsToMigrate[$table])
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->isNotNull($this->fieldsToMigrate[$table]),
                        $queryBuilder->expr()->neq(
                            $this->fieldsToMigrate[$table],
                            $queryBuilder->createNamedParameter('', Connection::PARAM_STR)
                        ),
                        $queryBuilder->expr()->comparison(
                            'CAST(CAST(' . $queryBuilder->quoteIdentifier($this->fieldsToMigrate[$table]) . ' AS DECIMAL) AS CHAR)',
                            ExpressionBuilder::NEQ,
                            'CAST(' . $queryBuilder->quoteIdentifier($this->fieldsToMigrate[$table]) . ' AS CHAR)'
                        )
                    )
                    ->orderBy('uid')
                    ->executeQuery()
                    ->fetchAllAssociative();

                if ($countOnly === true) {
                    $numResults += count($result);
                } else {
                    $allResults[$table] = $result;
                }
            } catch (DBALException $e) {
                throw new \RuntimeException(
                    'Database query failed. Error was: ' . $e->getPrevious()->getMessage(),
                    1511950673
                );
            }
        }

        if ($countOnly === true) {
            return $numResults;
        }
        return $allResults;
    }


    /**
     * Performs the database update.
     *
     * @return bool TRUE on success, FALSE on error
     */
    protected function falPerformUpdate(): bool
    {
        $result = true;

        try {
            $storages = GeneralUtility::makeInstance(StorageRepository::class)->findAll();
            $this->storage = $storages[0];

            $records = $this->falGetRecordsFromTable();
            foreach ($records as $table => $recordsInTable) {
                foreach ($recordsInTable as $record) {
                    $this->migrateField($table, $record);
                }
            }
        } catch (\Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Migrates a single field.
     *
     * @param string $table
     * @param array $row
     * @throws \Exception
     */
    protected function migrateField($table, $row)
    {
        $fieldItem = trim($row[$this->fieldsToMigrate[$table]]);

        if (empty($fieldItem) || is_numeric($fieldItem)) {
            return;
        }
        $fileadminDirectory = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') . '/';
        $i = 0;

        $storageUid = (int)$this->storage->getUid();
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $fileUid = null;
        $sourcePath = Environment::getPublicPath() . '/'  . $this->sourcePath . $fieldItem;
        $targetDirectory = Environment::getPublicPath() . '/'  . $fileadminDirectory . $this->targetPath;
        $targetPath = $targetDirectory . basename($fieldItem);

        // maybe the file was already moved, so check if the original file still exists
        if (file_exists($sourcePath)) {
            if (!is_dir($targetDirectory)) {
                GeneralUtility::mkdir_deep($targetDirectory);
            }

            // see if the file already exists in the storage
            $fileSha1 = sha1_file($sourcePath);

            $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file');
            $existingFileRecord = $queryBuilder->select('uid')->from('sys_file')->where($queryBuilder->expr()->eq(
                'missing',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            ), $queryBuilder->expr()->eq(
                'sha1',
                $queryBuilder->createNamedParameter($fileSha1, Connection::PARAM_STR)
            ), $queryBuilder->expr()->eq(
                'storage',
                $queryBuilder->createNamedParameter($storageUid, Connection::PARAM_INT)
            ))->executeQuery()->fetchAssociative();

            // the file exists, the file does not have to be moved again
            if (is_array($existingFileRecord)) {
                $fileUid = $existingFileRecord['uid'];
            } else {
                // just move the file (no duplicate)
                rename($sourcePath, $targetPath);
            }
        }

        if ($fileUid === null) {
            // get the File object if it hasn't been fetched before
            try {
                // if the source file does not exist, we should just continue, but leave a message in the docs;
                // ideally, the user would be informed after the update as well.
                /** @var File $file */
                $file = $this->storage->getFile($this->targetPath . $fieldItem);
                $fileUid = $file->getUid();
            } catch (\InvalidArgumentException $e) {
                // no file found, no reference can be set
                $this->logger->notice(
                    'File ' . $this->sourcePath . $fieldItem . ' does not exist. Reference was not migrated.',
                    [
                        'table' => $table,
                        'record' => $row,
                        'field' => $fieldItem,
                    ]
                );
                $format = 'File \'%s\' does not exist. Referencing field: %s.%d.%s. The reference was not migrated.';
                $this->output->writeln(sprintf(
                    $format,
                    $this->sourcePath . $fieldItem,
                    $table,
                    $row['uid'],
                    $fieldItem
                ));
                return;
            }
        }

        if ($fileUid > 0) {
            $fields = [
                'fieldname' => $this->fieldsToMigrate[$table],
                'table_local' => 'sys_file',
                'pid' => ($table === 'pages' ? $row['uid'] : $row['pid']),
                'uid_foreign' => $row['uid'],
                'uid_local' => $fileUid,
                'tablenames' => $table,
                'crdate' => time(),
                'tstamp' => time(),
                'sorting_foreign' => $i,
            ];

            $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->insert('sys_file_reference')->values($fields)->executeStatement();

            ++$i;
        }

        // Update referencing table's original field to now contain the count of references,
        // but only if all new references could be set
        if ($i === 1) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->update($table)->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($row['uid'], Connection::PARAM_INT)
                )
            )->set($this->fieldsToMigrate[$table], $i)->executeStatement();
        }
    }
}
