<?php

declare(strict_types=1);

arch('concerns contain only traits')
    ->expect('App\Concerns')
    ->toBeTraits();
