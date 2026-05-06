<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings\Team;

use App\Enums\Subscription\SubscriptionInterval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CheckoutRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'interval' => ['required', new Enum(SubscriptionInterval::class)],
        ];
    }
}
