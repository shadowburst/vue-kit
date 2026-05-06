<?php

declare(strict_types=1);

arch('policies do not call hasRole or hasAnyRole')
    ->expect('App\Policies')
    ->not->toUse('Spatie\Permission\Traits\HasRoles');

arch('policies do not reference role names as string literals')
    ->expect('App\Policies')
    ->not->toUse('App\Enums\Role\Role');
