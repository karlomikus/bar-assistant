<?php

use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([__DIR__ . '/app', __DIR__ . '/tests']);

    $ecsConfig->sets([SetList::PSR_12]);

    $ecsConfig->rules([NoUnusedImportsFixer::class]);

    $ecsConfig->ruleWithConfiguration(OrderedImportsFixer::class, [
        'sort_algorithm' => 'length'
    ]);
};
