<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPreparedSets(
        psr12: true,
        cleanCode: true,
        arrays: true,
        comments: true,
        docblocks: true,
        namespaces: true,
        spaces: true,
        controlStructures: true,
        strict: true,
    );
