<?php

declare(strict_types=1);

test('test files end in Test', function () {
    $testDir = realpath(__DIR__.'/..');
    $archDir = realpath(__DIR__);

    if ($testDir === false || $archDir === false) {
        throw new \RuntimeException('Failed to resolve test directories');
    }

    $violations = [];

    /** @var \SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $testDir,
        FilesystemIterator::SKIP_DOTS,
    )) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $realPath = realpath($file->getPathname());

        if ($realPath === false) {
            continue;
        }

        if (str_starts_with($realPath, $archDir.DIRECTORY_SEPARATOR)) {
            continue;
        }

        if (in_array($file->getBasename(), ['Pest.php', 'TestCase.php'], true)) {
            continue;
        }

        if (! str_ends_with($file->getBasename('.php'), 'Test')) {
            $violations[] = ltrim(str_replace($testDir, '', $realPath), DIRECTORY_SEPARATOR);
        }
    }

    expect($violations)->toBeEmpty('Files without Test suffix: '.implode(', ', $violations));
});
