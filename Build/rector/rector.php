<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\PostRector\Rector\NameImportingPostRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\ConvertImplicitVariablesToExplicitGlobalsRector;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__.'/../..']);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::STRICT_BOOLEANS,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        Typo3LevelSetList::UP_TO_TYPO3_13,
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    $rectorConfig->ruleWithConfiguration(
        ExtEmConfRector::class,
        [
            ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.1.0-8.4.99',
            ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '12.4.0-13.4.99',
            ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => [],
        ]
    );
    $rectorConfig->rule(ConvertImplicitVariablesToExplicitGlobalsRector::class);

    $rectorConfig->phpstanConfig(Typo3Option::PHPSTAN_FOR_RECTOR_PATH);
    $rectorConfig->phpstanConfig(__DIR__.'/../phpstan/phpstan.neon');

    $rectorConfig->skip([
        // makes double-quoted strings, we don't want this at the moment.
        Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector::class,
    ]);
};
