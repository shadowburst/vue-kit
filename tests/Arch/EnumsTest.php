<?php

declare(strict_types=1);

arch('Enum namespace contains only enum declarations')
    ->expect('App\Enums')
    ->toBeEnums();

// All enums must be backed by string or int — arch() has no single "isBacked" matcher,
// so a test() loop uses ReflectionEnum directly.
test('Enums are backed by string or int', function (): void {
    $enumsDir = realpath(__DIR__.'/../../app/Enums');

    if ($enumsDir === false) {
        return;
    }

    $violations = [];

    /** @var \SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $enumsDir,
        FilesystemIterator::SKIP_DOTS,
    )) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $realPath = realpath($file->getPathname());

        if ($realPath === false) {
            continue;
        }

        $relativePath = ltrim(str_replace([$enumsDir, '.php'], ['', ''], $realPath), DIRECTORY_SEPARATOR);
        $className    = 'App\\Enums\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        $ref = new ReflectionEnum($className);

        if (! $ref->isBacked()) {
            $violations[] = "{$className}: enum must be backed by string or int";
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});
