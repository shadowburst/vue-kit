<?php

declare(strict_types=1);

// `final` applies to every action, Fortify or not. The Fortify carve-out only exists
// for the shape rules below (suffix, trait, execute method).
arch('actions are final')
    ->expect('App\Actions')
    ->classes()
    ->toBeFinal();

// Non-Fortify actions must: not end with 'Action', be final, and use QueueableAction.
arch('non-Fortify actions do not end with the Action suffix')
    ->expect('App\Actions')
    ->not
    ->toHaveSuffix('Action')
    ->ignoring('App\Actions\Fortify');

arch('non-Fortify actions use the QueueableAction trait')
    ->expect('App\Actions')
    ->toUseTrait('Spatie\QueueableAction\QueueableAction')
    ->ignoring('App\Actions\Fortify');

// The "exactly one public execute" and "no protected own methods" rules require
// ReflectionClass inspection of each class's own declared methods — not expressible
// via a single arch() matcher — so they live in a plain test loop.
test('non-Fortify actions declare exactly one public execute method and no protected methods', function (): void {
    $actionsDir = realpath(__DIR__.'/../../app/Actions');

    if ($actionsDir === false) {
        return;
    }

    $violations = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $actionsDir,
        FilesystemIterator::SKIP_DOTS,
    )) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $realPath = realpath($file->getPathname());

        if ($realPath === false) {
            continue;
        }

        $relativePath = ltrim(str_replace([$actionsDir, '.php'], ['', ''], $realPath), DIRECTORY_SEPARATOR);
        $className    = 'App\\Actions\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        if (str_starts_with($className, 'App\\Actions\\Fortify\\')) {
            continue;
        }

        if (! class_exists($className)) {
            continue;
        }

        $ref = new ReflectionClass($className);

        // PHP flattens trait methods into the using class, so $m->class and getDeclaringClass()
        // both return the using class — not the trait. Build an exclusion set from all traits
        // (direct and transitive) so they are not counted as the class's own declarations.
        $traitMethodNames = [];
        foreach (class_uses_recursive($ref->getName()) as $traitName) {
            if (! trait_exists($traitName)) {
                continue;
            }

            foreach ((new ReflectionClass($traitName))->getMethods() as $traitMethod) {
                $traitMethodNames[$traitMethod->getName()] = true;
            }
        }

        // Own-declared methods only: on this class, and not sourced from any trait.
        $ownMethods = array_filter(
            $ref->getMethods(),
            fn (ReflectionMethod $m): bool => (
                $m->class === $ref->getName()
                && ! array_key_exists($m->getName(), $traitMethodNames)
            ),
        );

        $publicNonConstructor = array_values(array_filter(
            $ownMethods,
            fn (ReflectionMethod $m): bool => $m->isPublic() && $m->getName() !== '__construct',
        ));

        $publicCount = count($publicNonConstructor);

        if ($publicCount !== 1) {
            $violations[] = "{$className}: must declare exactly one public method (execute), found {$publicCount}";
        }

        if ($publicCount === 1 && $publicNonConstructor[0]->getName() !== 'execute') {
            $violations[] = "{$className}: public method must be named execute, found '{$publicNonConstructor[0]->getName()}'";
        }

        $protectedMethods = array_filter(
            $ownMethods,
            fn (ReflectionMethod $m): bool => $m->isProtected(),
        );

        if (count($protectedMethods) > 0) {
            $names = implode(', ', array_map(
                fn (ReflectionMethod $m): string => $m->getName(),
                $protectedMethods,
            ));
            $violations[] = "{$className}: must not declare protected methods (found: {$names})";
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});
