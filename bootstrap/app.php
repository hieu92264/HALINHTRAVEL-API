<?php

use App\Http\Middleware\SetLocale;
use App\Shared\Helpers\LocaleHelper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Spatie Permission middleware aliases. Keep the auth middleware before
        // these aliases on routes so the current JWT user can be resolved.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->appendToGroup('api', SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (HttpResponse $response, Throwable $exception, Request $request): HttpResponse {
            LocaleHelper::apply($request);

            $apiPrefix = trim((string) config('modules.api_prefix', 'api'), '/');
            $isApiRequest = $request->is($apiPrefix) || $request->is($apiPrefix.'/*');

            if (! $isApiRequest) {
                return $response;
            }

            $statusCode = match (true) {
                $exception instanceof ValidationException => HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
                $exception instanceof AuthenticationException => HttpResponse::HTTP_UNAUTHORIZED,
                $exception instanceof AuthorizationException => HttpResponse::HTTP_FORBIDDEN,
                $exception instanceof ModelNotFoundException => HttpResponse::HTTP_NOT_FOUND,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => $response->getStatusCode() && $response->getStatusCode() !== HttpResponse::HTTP_OK
                    ? $response->getStatusCode()
                    : HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
            };

            $message = match (true) {
                $exception instanceof ValidationException => 'The submitted data is invalid.',
                $exception instanceof AuthenticationException => 'You are not authenticated.',
                $exception instanceof AuthorizationException => 'You do not have permission to access this resource.',
                $exception instanceof ModelNotFoundException => 'The requested data could not be found.',
                $statusCode === HttpResponse::HTTP_NOT_FOUND => 'Route or resource not found.',
                $statusCode === HttpResponse::HTTP_METHOD_NOT_ALLOWED => 'The request method is not allowed.',
                $statusCode >= HttpResponse::HTTP_INTERNAL_SERVER_ERROR => 'Server error.',
                default => $exception->getMessage() ?: 'An error occurred.',
            };

            $metadata = $exception instanceof ValidationException ? $exception->errors() : null;
            if (($metadata === null) && (app()->hasDebugModeEnabled() || config('app.debug') === true)) {
                $metadata = [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];
            }

            $payload = [
                'message' => $message,
                'status_code' => $statusCode,
                'metadata' => $metadata,
                'path' => $request->getPathInfo(),
                'timestamp' => now()->toISOString(),
            ];

            if (app()->hasDebugModeEnabled() || config('app.debug')) {
                $payload['stack'] = $exception->getTraceAsString();
            }

            return response()
                ->json($payload, $statusCode)
                ->header('Content-Language', app()->currentLocale());
        });
    })->create();
