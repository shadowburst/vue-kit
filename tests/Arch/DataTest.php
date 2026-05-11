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

        $realPath = realpath($file->getPathname());
        $relativePath = ltrim(
            str_replace([$dataDir, '.php'], ['', ''], $realPath === false ? '' : $realPath),
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

        $realPath = realpath($file->getPathname());
        $relativePath = ltrim(
            str_replace([$dataDir, '.php'], ['', ''], $realPath === false ? '' : $realPath),
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

test('no Data class lives at the root of app/Data/ (ADR-0017: subgrouping by noun required)', function (): void {
    $dataDir = realpath(__DIR__.'/../../app/Data');

    if ($dataDir === false) {
        return;
    }

    $violations = [];

    /** @var DirectoryIterator $file */
    foreach (new DirectoryIterator($dataDir) as $file) {
        if ($file->isDot() || $file->isDir()) {
            continue;
        }

        if ($file->getExtension() === 'php') {
            $violations[] =
                $file->getFilename()
                .': must live in a noun subdirectory (e.g. app/Data/User/), not directly in app/Data/';
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});

// String `max:` rules in Data classes must reference App\Enums\Validation\StringMaxLength
// rather than a literal integer, so the cap is one of three deliberate tiers (ADR-0018).
test('string max rules in Data classes go through StringMaxLength enum', function (): void {
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

        $contents = file_get_contents($realPath);

        if ($contents === false) {
            continue;
        }

        if (preg_match_all('/[\'"]max:\d+[\'"]/', $contents, $matches) > 0) {
            foreach ($matches[0] as $match) {
                $violations[] = "{$realPath}: literal {$match} — use StringMaxLength::*->maxRule() instead (ADR-0018)";
            }
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
