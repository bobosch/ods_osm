<?php
/***************************************************************
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
 ***************************************************************/

namespace Bobosch\OdsOsm\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Migrate flexform settings to keep existing configuration valid.
 */
class MigrateSettings implements UpgradeWizardInterface
{

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::class;
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Migrate settings in EXT:ods_osm plugins';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'This wizard migrates some flexform settings which has changed in ods_osm' .
            ' extension. This makes the full reconfiguration of all used plugins obsolete.';
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
        // Get all tt_content data of ods_osm and update their flexforms settings
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $statement = $queryBuilder->select('uid')
            ->addSelect('pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->like('list_type', $queryBuilder->createNamedParameter('ods_osm_%'))
            )
            ->execute();

        // Update the found record sets
        while ($record = $statement->fetch()) {
            $queryBuilder = $connection->createQueryBuilder();
            $updateResult = $queryBuilder->update('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($record['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('pi_flexform', $this->migrateFlexformSettings($record['pi_flexform']))
                ->execute();

            // exit if at least one update statement is not successful
            if (!((bool) $updateResult)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is an update necessary?
     *
     * Looks for fe plugins in tt_content table to be migrated
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $oldSettingsFound = false;

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $statement = $queryBuilder->select('uid')
            ->addSelect('pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->like('list_type', $queryBuilder->createNamedParameter('ods_osm_%'))
            )
            ->execute();

        // Update the found record sets
        while ($record = $statement->fetch()) {
            $oldSettingsFound = $this->checkForOldSettings($record['pi_flexform']);
            if ($oldSettingsFound === true) {
                // We found at least one field to be updated --> break here
                break;
            }
        }

        return $oldSettingsFound;
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
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }


    /**
     * @param string $oldValue
     * @return string
     */
    protected function migrateFlexformSettings(string $oldValue): string
    {
        $xml = simplexml_load_string($oldValue);
        // get all field elements
        $library = $xml->xpath("//field[@index='library'][1]");

        // get all field elements
        $fields = $xml->xpath("//field");

        foreach ($fields as $field) {
            if ($library[0]->value != 'staticmap' && $field['index'] == $library[0]->value . '_layer') {
                // rename base layer field to base_layer
                $field['index'] = 'base_layer';
                // Copy all layers into new 'overlays' field. This is easier here, doesn't hurt the
                // frontend and will be filtered to only real 'overlays' on next saving the plugin flexform.
                $overlays = $xml->data->sheet->language->addChild('field');
                $overlays->addAttribute('index', 'overlays');
                $overlays->addChild('value', $field->value)->addAttribute('index', 'vDEF');
            } else if ($field['index'] != $library[0]->value . '_layer' && ($field['index'] == 'layer' ||
                    $field['index'] == 'openlayers_layer' ||
                    $field['index'] == 'openlayers3_layer' || $field['index'] == 'leaflet_layer')) {
                // remove all other, unused layer fields from flexform xml
                unset($field[0]);
            }
        }

        return $xml->asXML();
    }

    /**
     * @param string $flexFormXml
     * @return bool
     */
    protected function checkForOldSettings(string $flexFormXml): bool
    {
        $xml = simplexml_load_string($flexFormXml);

        // check for existing values of attribute "index"
        // * openlayers_layer
        // * leaflet_layer
        // * layer

        $fields = $xml->xpath("//field[@index='openlayers_layer'] | //field[@index='leaflet_layer'] | //field[@index='layer']");

        return (bool) $fields;
    }

}
