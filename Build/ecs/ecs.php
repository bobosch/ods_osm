<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\OperatorLinebreakFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/../../Build',
        __DIR__ . '/../../Classes',
        __DIR__ . '/../../Configuration',
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
        DeclareStrictTypesFixer::class,
        OperatorLinebreakFixer::class,
    ])
    ->withSkip([
        DeclareStrictTypesFixer::class => [
            __DIR__ . '/../../ext_emconf.php',
        ],
        MethodChainingIndentationFixer::class,
        MethodChainingNewlineFixer::class,
        ArrayOpenerAndCloserNewlineFixer::class,
    ]);
