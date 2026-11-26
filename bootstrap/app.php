<?php

use App\Infrastructure\Http\Controllers\HealthController;
use App\Infrastructure\Middleware\EnsureLocationScope;
use App\Infrastructure\Services\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::get('/healthz', HealthController::class);
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'location.scope' => EnsureLocationScope::class,
        ]);
        $middleware->validateCsrfTokens(except: ['api/*', 'livewire/update']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            $ditolak = $e instanceof AuthorizationException
                || ($e instanceof HttpException && $e->getStatusCode() === 403);
            if ($ditolak) {
                AuditLogger::log('AKSES_DITOLAK', null, null, null, [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
            }

            return null;
        });
    })->create();
