<?php

declare(strict_types=1);

namespace Bobosch\OdsOsm\Updates;

use Linawolf\ListTypeMigration\Upgrades\AbstractListTypeToCTypeUpdate;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

#[UpgradeWizard('odsOsmPluginMigration')]
final class BoboschOdsOsmCTypeMigration extends AbstractListTypeToCTypeUpdate
{
    public function getTitle(): string
    {
        return 'Migrate "Bobosch OdsOsm" plugins to content elements.';
    }

    public function getDescription(): string
    {
        return 'The "Bobosch OdsOsm" plugins are now registered as content element. Update migrates existing records and backend user permissions.';
    }

    /**
     * This must return an array containing the "list_type" to "CType" mapping
     *
     *  Example:
     *
     *  [
     *      'pi_plugin1' => 'pi_plugin1',
     *      'pi_plugin2' => 'new_content_element',
     *  ]
     *
     * @return array<string, string>
     */
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'ods_osm_pi1' => 'ods_osm_pi1',
        ];
    }
}
