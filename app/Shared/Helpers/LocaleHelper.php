<?php

namespace App\Shared\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocaleHelper
{
    public static function apply(?Request $request = null): string
    {
        $locale = static::resolve($request);

        app()->setLocale($locale);

        return $locale;
    }

    public static function resolve(?Request $request = null): string
    {
        $supportedLocales = static::supportedLocales();

        foreach (static::requestCandidates($request) as $candidate) {
            $locale = static::normalize($candidate);

            if ($locale !== null && in_array($locale, $supportedLocales, true)) {
                return $locale;
            }
        }

        $defaultLocale = static::normalize(config('app.locale'));

        if ($defaultLocale !== null && in_array($defaultLocale, $supportedLocales, true)) {
            return $defaultLocale;
        }

        return static::normalize(config('app.fallback_locale')) ?? 'en';
    }

    public static function requestCandidates(?Request $request = null): array
    {
        if (!$request) {
            return [];
        }

        return [
            $request->query('locale'),
            $request->header('X-Locale'),
            $request->header('Accept-Language'),
            $request->query('X-Lang')
        ];
    }

    public static function supportedLocales(): array
    {
        $configuredLocales = config('app.supported_locales', ['en', 'vi']);

        if (!is_array($configuredLocales)) {
            $configuredLocales = ['en', 'vi'];
        }

        $locales = array_values(array_unique(array_filter(array_map(
            static fn(mixed $locale): ?string => static::normalize($locale),
            $configuredLocales
        ))));

        return $locales !== [] ? $locales : ['en', 'vi'];
    }

    protected static function normalize(mixed $locale): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        $locale = Str::of($locale)
            ->trim()
            ->lower()
            ->replace('_', '-')
            ->before('-')
            ->value();

        return $locale !== '' ? $locale : null;
    }
}
