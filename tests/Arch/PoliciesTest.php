<?php

declare(strict_types=1);

use App\Enums\Role\Role;

arch('policies do not import the Role enum')
    ->expect('App\Policies')
    ->not->toUse(Role::class);

arch('policies do not use the HasRoles trait')
    ->expect('App\Policies')
    ->not->toUse('Spatie\Permission\Traits\HasRoles');

// arch() cannot detect method calls on typed parameters (e.g. $user->hasRole()),
// so these prohibitions require a file-content scan.
test('policies do not call hasRole family methods or reference role-name string literals', function (): void {
    $policiesDir = realpath(__DIR__.'/../../app/Policies');

    if ($policiesDir === false) {
        return;
    }

    $forbiddenCalls = ['hasRole(', 'hasAnyRole(', 'hasAllRoles(', 'hasExactRoles('];

    /** @var array<string> $forbiddenLiterals */
    $forbiddenLiterals = array_map(
        static fn (Role $role): string => $role->value,
        Role::cases(),
    );

    $violations = [];

    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $policiesDir,
        FilesystemIterator::SKIP_DOTS,
    )) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $realPath = realpath($file->getPathname());

        if ($realPath === false) {
            continue;
        }

        $content = file_get_contents($realPath);

        if ($content === false) {
            continue;
        }

        foreach ($forbiddenCalls as $call) {
            if (! str_contains($content, $call)) {
                continue;
            }

            $violations[] = "{$file->getBasename()}: forbidden call '{$call}'";
        }

        foreach ($forbiddenLiterals as $literal) {
            if (preg_match('/([\'"])'.preg_quote($literal, '/').'\\1/', $content) !== 1) {
                continue;
            }

            $violations[] = "{$file->getBasename()}: role-name string literal '{$literal}'";
        }
    }

    expect($violations)->toBeEmpty(implode("\n", $violations));
});
