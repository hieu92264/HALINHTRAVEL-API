<?php

namespace App\Http\Middleware;

use App\Shared\Helpers\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = LocaleHelper::apply($request);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
