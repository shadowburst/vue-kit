<?php

declare(strict_types=1);

arch('strict types in app')
    ->expect('App')
    ->toUseStrictTypes();

arch('strict types in tests')
    ->expect('Tests')
    ->toUseStrictTypes();

arch('strict types in database factories')
    ->expect('Database\Factories')
    ->toUseStrictTypes();

arch('strict types in database seeders')
    ->expect('Database\Seeders')
    ->toUseStrictTypes();
