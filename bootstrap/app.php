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
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
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
            $isApiRequest = $request->is($apiPrefix) || $request->is($apiPrefix . '/*');

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
                $exception instanceof ValidationException => 'Dữ liệu gửi lên không hợp lệ.',
                $exception instanceof AuthenticationException => 'Bạn chưa được xác thực.',
                $exception instanceof AuthorizationException => 'Bạn không có quyền truy cập tài nguyên này.',
                $exception instanceof ModelNotFoundException => 'Không tìm thấy dữ liệu được yêu cầu.',
                $statusCode === HttpResponse::HTTP_BAD_REQUEST => 'Yêu cầu không hợp lệ.',
                $statusCode === HttpResponse::HTTP_UNAUTHORIZED => 'Bạn chưa được xác thực.',
                $statusCode === HttpResponse::HTTP_FORBIDDEN => 'Bạn không có quyền truy cập tài nguyên này.',
                $statusCode === HttpResponse::HTTP_NOT_FOUND => 'Không tìm thấy đường dẫn hoặc tài nguyên.',
                $statusCode === HttpResponse::HTTP_METHOD_NOT_ALLOWED => 'Phương thức gửi yêu cầu không được hỗ trợ.',
                $statusCode === HttpResponse::HTTP_TOO_MANY_REQUESTS => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.',
                $statusCode >= HttpResponse::HTTP_INTERNAL_SERVER_ERROR => 'Đã xảy ra lỗi máy chủ.',
                default => $exception->getMessage() ?: 'Đã xảy ra lỗi.',
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
