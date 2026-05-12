<?php

declare(strict_types=1);

namespace App\Enums\Validation;

enum StringMaxLength: int
{
    case Short  = 100;
    case Medium = 255;
    case Long   = 2000;

    public function maxRule(): string
    {
        return 'max:'.$this->value;
    }
}
