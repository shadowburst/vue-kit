<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\AppLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        return $this->resolveFromCookie($request)
            ?? $this->resolveFromAcceptLanguage($request)
            ?? config('app.fallback_locale');
    }

    private function resolveFromCookie(Request $request): ?string
    {
        $locale = $request->cookie('locale');

        if (! is_string($locale)) {
            return null;
        }

        return $this->isSupported($locale) ? $locale : null;
    }

    private function resolveFromAcceptLanguage(Request $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $language = strtolower($language);

            if ($this->isSupported($language)) {
                return $language;
            }

            $primary = explode('-', $language)[0];

            if ($this->isSupported($primary)) {
                return $primary;
            }
        }

        return null;
    }

    private function isSupported(string $locale): bool
    {
        return in_array(
            $locale,
            array_map(fn (AppLocale $l): string => $l->value, AppLocale::cases()),
            strict: true,
        );
    }
}
