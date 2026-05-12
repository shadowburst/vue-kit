<?php

declare(strict_types=1);

arch('Classes in App\Enums are enums')
    ->expect('App\Enums')
    ->toBeEnums();
