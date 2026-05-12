<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * Enforces docs/adr/0006-controller-method-conventions.md.
 */

/**
 * @return list<class-string>
 */
function controllerClasses(): array
{
    $base    = app_path('Http/Controllers');
    $classes = [];

    foreach ((new Finder)
        ->in($base)
        ->files()
        ->name('*.php') as $file) {
        $relative = str_replace(
            [$base.DIRECTORY_SEPARATOR, '.php', DIRECTORY_SEPARATOR],
            ['', '', '\\'],
            $file->getPathname(),
        );
        $class = 'App\\Http\\Controllers\\'.$relative;

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            continue;
        }

        $classes[] = $class;
    }

    return $classes;
}

it('only exposes resource verbs as public instance methods', function (): void {
    $allowed    = ['__construct', 'index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
    $violations = [];

    foreach (controllerClasses() as $class) {
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            if ($method->isStatic()) {
                continue;
            }

            if (! in_array($method->getName(), $allowed, true)) {
                $violations[] = "{$class}::{$method->getName()}()";
            }
        }
    }

    expect($violations)->toBeEmpty();
});

it('does not use __invoke', function (): void {
    $violations = [];

    foreach (controllerClasses() as $class) {
        if (! (new ReflectionClass($class))->hasMethod('__invoke')) {
            continue;
        }

        $violations[] = $class;
    }

    expect($violations)->toBeEmpty();
});

it('declares non-public methods as private, never protected', function (): void {
    $violations = [];

    foreach (controllerClasses() as $class) {
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PROTECTED) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $violations[] = "{$class}::{$method->getName()}()";
        }
    }

    expect($violations)->toBeEmpty();
});
