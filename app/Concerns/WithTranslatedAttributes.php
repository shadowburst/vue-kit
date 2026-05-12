<?php

declare(strict_types=1);

namespace App\Concerns;

use ReflectionClass;
use ReflectionProperty;

trait WithTranslatedAttributes
{
    /**
     * Maps every public property to a translation key derived from the
     * class's `App\Data\{Noun}\...` namespace: each `$prop` resolves to
     * `{lowercase-noun}.attributes.{prop}`. See ADR-0018.
     *
     * @return array<string, string>
     */
    public static function attributes(mixed ...$args): array
    {
        $parts = explode('\\', static::class);
        $noun  = strtolower($parts[2]);

        $attributes = [];

        foreach ((new ReflectionClass(static::class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name              = $property->getName();
            $attributes[$name] = (string) __("{$noun}.attributes.{$name}");
        }

        return $attributes;
    }
}
