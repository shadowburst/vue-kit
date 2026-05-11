<?php

declare(strict_types=1);

// Enforced type folder roots under app/: every PHP file here must live in a
// noun subfolder (domain, integration boundary, or cohesive feature).
// ADR 0009 Decision 5 exempts Models/, Policies/, Observers/ (1-to-1 keyed
// by model) and Providers/, Concerns/, Listeners/ (small, stable,
// cross-cutting). Subgrouping those would either repeat the noun or break
// framework auto-discovery.
$enforcedTypeFolders = [
    'Actions',
    'Data',
    'Enums',
    'Http/Controllers',
    'Http/Middleware',
    'Http/Requests',
    'Http/Resources',
    'Services',
];

test('no PHP class lives at the root of a non-exempt type folder under app/', function () use (
    $enforcedTypeFolders,
): void {
    $appDir = realpath(__DIR__.'/../../app');

    if ($appDir === false) {
        throw new RuntimeException('Failed to resolve app directory');
    }

    // Files permitted at the root of an enforced type folder (ADR 0009).
    // The abstract base Controller is the documented sole exception.
    $appExemptFiles = [
        realpath($appDir.'/Http/Controllers/Controller.php'),
    ];

    $violations = [];

    foreach ($enforcedTypeFolders as $relativeFolder) {
        $typeFolder = $appDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeFolder);

        if (! is_dir($typeFolder)) {
            continue;
        }

        foreach (new DirectoryIterator($typeFolder) as $item) {
            if (! $item->isFile() || $item->getExtension() !== 'php') {
                continue;
            }

            $realPath = realpath($item->getPathname());

            if ($realPath === false || in_array($realPath, $appExemptFiles, strict: true)) {
                continue;
            }

            $violations[] = ltrim(str_replace($appDir, '', $item->getPathname()), DIRECTORY_SEPARATOR);
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});

test('no PHP test lives at the root of tests/Feature/', function (): void {
    $featurePath = realpath(__DIR__.'/../Feature');

    if ($featurePath === false) {
        throw new RuntimeException('Failed to resolve tests/Feature directory');
    }

    $violations = [];

    foreach (new DirectoryIterator($featurePath) as $item) {
        if (! ($item->isFile() && $item->getExtension() === 'php')) {
            continue;
        }

        $violations[] = $item->getFilename();
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});
