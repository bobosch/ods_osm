<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\OperatorLinebreakFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/../../Build',
        __DIR__ . '/../../Classes',
        __DIR__ . '/../../Configuration',
        __DIR__ . '/../../config',
    ])
    // include *.php files in the root directory
    ->withRootFiles()
    ->withPreparedSets(
        psr12: true,
        arrays: true,
        comments: true,
        docblocks: true,
        spaces: true,
        namespaces: true,
    )
    ->withConfiguredRule(CastSpacesFixer::class, [
        'space' => 'single',
    ])
    ->withRules([
        NoUnusedImportsFixer::class,
        ArraySyntaxFixer::class,
        StandaloneLineInMultilineArrayFixer::class,
        ArrayOpenerAndCloserNewlineFixer::class,
        DeclareStrictTypesFixer::class,
        OperatorLinebreakFixer::class,
    ]);
