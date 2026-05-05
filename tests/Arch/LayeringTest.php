<?php

declare(strict_types=1);

arch('models do not depend on HTTP layer')
    ->expect('App\Models')
    ->not->toUse('App\Http');

arch('models do not depend on actions layer')
    ->expect('App\Models')
    ->not->toUse('App\Actions');

arch('providers do not depend on controllers')
    ->expect('App\Providers')
    ->not->toUse('App\Http\Controllers');

arch('actions do not depend on controllers')
    ->expect('App\Actions')
    ->not->toUse('App\Http\Controllers');
