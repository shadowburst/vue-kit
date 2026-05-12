<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

test('every Eloquent model has a Filament Resource', function (): void {
    $models = collect(discoverPhpClasses(dirname(__DIR__, 2).'/app/Models', 'App\\Models'))
        ->filter(function (string $modelClass): bool {
            $reflection = new ReflectionClass($modelClass);

            return $reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract();
        });

    $models->each(fn (string $modelClass) => expect($modelClass)->toHaveCorrespondingResourceIn('App\\Filament\\Resources'));
});
