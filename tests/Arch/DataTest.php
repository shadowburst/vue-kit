<?php

declare(strict_types=1);

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Resource;

arch('no class in app/ extends JsonResource (ADR-0017 D1: JsonResource is replaced by Spatie Data)')
    ->expect('App')
    ->not->toExtend('Illuminate\Http\Resources\Json\JsonResource');

arch('no class in app/ extends FormRequest (ADR-0017 D6: FormRequest is replaced by Spatie Data)')
    ->expect('App')
    ->not->toExtend('Illuminate\Foundation\Http\FormRequest');

/**
 * ADR-0017 D2 four-stub taxonomy:
 *   *Data / *Request  → extends Data   (input+output DTOs and request objects)
 *   *Resource / *Props → extends Resource (output-only serialisation + page props)
 */
test('Data classes have allowed suffixes (Data, Request, Resource, Props)', function (): void {
    $allowed = ['Data', 'Request', 'Resource', 'Props'];

    $dataDir = realpath(__DIR__.'/../../app/Data');
    $violations = [];

    if ($dataDir === false) {
        return;
    }

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $dataDir,
        FilesystemIterator::SKIP_DOTS,
    )) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relativePath = ltrim(
            str_replace([$dataDir, '.php'], ['', ''], realpath($file->getPathname()) ?: ''),
            DIRECTORY_SEPARATOR,
        );
        $className = 'App\\Data\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        if (! class_exists($className)) {
            continue;
        }

        $shortName = (new ReflectionClass($className))->getShortName();
        $hasSuffix = collect($allowed)->contains(fn (string $s) => str_ends_with($shortName, $s));

        if (! $hasSuffix) {
            $violations[] = "{$className}: must end with one of ".implode(', ', $allowed);
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});

test('Data classes extend the correct Spatie LaravelData base class', function (): void {
    $dataDir = realpath(__DIR__.'/../../app/Data');
    $violations = [];

    if ($dataDir === false) {
        return;
    }

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $dataDir,
        FilesystemIterator::SKIP_DOTS,
    )) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relativePath = ltrim(
            str_replace([$dataDir, '.php'], ['', ''], realpath($file->getPathname()) ?: ''),
            DIRECTORY_SEPARATOR,
        );
        $className = 'App\\Data\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        if (! class_exists($className)) {
            continue;
        }

        $ref = new ReflectionClass($className);
        $shortName = $ref->getShortName();

        $expectsResource = str_ends_with($shortName, 'Resource') || str_ends_with($shortName, 'Props');

        if ($expectsResource && ! $ref->isSubclassOf(Resource::class)) {
            $violations[] = "{$className}: *Resource/*Props must extend ".Resource::class;
        }

        // SharedData (ADR-0017 D8) extends Resource by design — accept both base classes for *Data/*Request.
        $isSpatieDataClass = $ref->isSubclassOf(Data::class) || $ref->isSubclassOf(Resource::class);

        if (! $expectsResource && ! $isSpatieDataClass) {
            $violations[] = "{$className}: *Data/*Request must extend ".Data::class.' or '.Resource::class;
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});

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
        $className = 'App\\Data\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

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
