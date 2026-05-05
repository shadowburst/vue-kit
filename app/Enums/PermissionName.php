<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionName: string
{
    case Admin       = 'admin';
    case Test        = 'test';
    case UserViewAny = 'user.viewAny';
    case UserView    = 'user.view';
    case UserCreate  = 'user.create';
    case UserUpdate  = 'user.update';
    case UserDelete  = 'user.delete';
    case TeamView    = 'team.view';
    case TeamUpdate  = 'team.update';
    case TeamDelete  = 'team.delete';
}
