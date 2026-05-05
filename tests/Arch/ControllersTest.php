<?php

declare(strict_types=1);

arch('controllers are final')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toBeFinal()
    ->ignoring('App\Http\Controllers\Controller');
