<?php

declare(strict_types=1);

namespace App\TypeScriptTransformer;

use Spatie\TypeScriptTransformer\Collections\TransformedCollection;
use Spatie\TypeScriptTransformer\Data\WriteableFile;
use Spatie\TypeScriptTransformer\Writers\FlatModuleWriter as BaseFlatModuleWriter;

/**
 * Spatie's FlatModuleWriter emits `= object` for empty Data classes, which
 * Vue's SFC compiler rejects in a `defineProps<T>()` generic. Rewrite those
 * aliases to `= {}` so empty-prop pages compile.
 */
class FlatModuleWriter extends BaseFlatModuleWriter
{
    public function output(array $transformed, TransformedCollection $transformedCollection): array
    {
        $files = parent::output($transformed, $transformedCollection);

        return array_map(
            fn (WriteableFile $file) => new WriteableFile(
                path    : $file->path,
                contents: preg_replace(
                    '/^((?:export )?type \w+ = )object(;)$/m',
                    '$1{}$2',
                    $file->contents,
                ) ?? $file->contents,
                changed : $file->changed,
            ),
            $files,
        );
    }
}
