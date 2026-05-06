<?php

declare(strict_types=1);

use Spatie\LaravelData\Data;

arch('Data classes extend Spatie\LaravelData\Data')
    ->expect('App\Data')
    ->toExtend(Data::class);

arch('Data classes end with the Data suffix')
    ->expect('App\Data')
    ->toHaveSuffix('Data');

// Non-abstract Data classes must be final — arch() has no "non-abstract" filter,
// so a test() loop inspects each concrete class directly.
test('non-abstract Data classes are final', function (): void {
    $dataDir = realpath(__DIR__.'/../../app/Data');

    if ($dataDir === false) {
        return;
    }

    $violations = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $dataDir,
        FilesystemIterator::SKIP_DOTS,
    )) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $realPath = realpath($file->getPathname());

        if ($realPath === false) {
            continue;
        }

        $relativePath = ltrim(str_replace([$dataDir, '.php'], ['', ''], $realPath), DIRECTORY_SEPARATOR);
        $className    = 'App\\Data\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        if (! class_exists($className)) {
            continue;
        }

        $ref = new ReflectionClass($className);

        if ($ref->isAbstract()) {
            continue;
        }

        if (! $ref->isFinal()) {
            $violations[] = "{$className}: non-abstract Data class must be final";
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});
