<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom middleware aliases
        $middleware->alias([
            'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'application' => \App\Http\Middleware\ApplicationMiddleware::class,
            'user.auth' => \App\Http\Middleware\UserAuthMiddleware::class,
        ]);

        // Exclude payment callback routes from CSRF verification
        // These routes are called by PayU gateway and don't have CSRF tokens
        $middleware->validateCsrfTokens(except: [
            'user/applications/ix/payment-success',
            'user/applications/ix/payment-failure',
            'user/applications/irin/payment-success',
            'user/applications/irin/payment-failure',
            'user/wallet/add-money/success',
            'user/wallet/add-money/failure',
            'payu/webhook',
            'user/login-from-cookie',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle database connection errors
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            // Check if it's a connection error
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            // Common database connection error codes and messages
            $connectionErrors = [
                '2002', // Connection refused
                '1045', // Access denied
                '2006', // MySQL server has gone away
                'HY000', // General error
            ];

            $isConnectionError = false;
            foreach ($connectionErrors as $code) {
                if (strpos($errorMessage, $code) !== false || strpos($errorMessage, 'Connection') !== false) {
                    $isConnectionError = true;
                    break;
                }
            }

            if ($isConnectionError || strpos($errorMessage, 'No connection could be made') !== false) {
                \Illuminate\Support\Facades\Log::error('Database Connection Error: '.$errorMessage, [
                    'exception' => $e,
                    'url' => $request->url(),
                    'method' => $request->method(),
                ]);

                // Return 503 Service Unavailable for database connection errors
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Database connection unavailable. Please try again later.',
                        'error' => 'Service Unavailable',
                    ], 503);
                }

                return response()->view('errors.503', [
                    'message' => 'Database connection unavailable. Please try again later.',
                ], 503);
            }

            // For other database errors, log and show 500 error
            \Illuminate\Support\Facades\Log::error('Database Query Error: '.$errorMessage, [
                'exception' => $e,
                'url' => $request->url(),
                'method' => $request->method(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'A database error occurred. Please try again later.',
                    'error' => 'Internal Server Error',
                ], 500);
            }

            return response()->view('errors.500', [
                'message' => 'A database error occurred. Please try again later.',
            ], 500);
        });

        // Handle PDO exceptions (database driver errors)
        $exceptions->render(function (\PDOException $e, $request) {
            $errorMessage = $e->getMessage();

            if (strpos($errorMessage, 'Connection') !== false ||
                strpos($errorMessage, 'No connection could be made') !== false ||
                strpos($errorMessage, 'SQLSTATE[HY000]') !== false) {

                \Illuminate\Support\Facades\Log::error('PDO Connection Error: '.$errorMessage, [
                    'exception' => $e,
                    'url' => $request->url(),
                    'method' => $request->method(),
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Database connection unavailable. Please try again later.',
                        'error' => 'Service Unavailable',
                    ], 503);
                }

                return response()->view('errors.503', [
                    'message' => 'Database connection unavailable. Please try again later.',
                ], 503);
            }
        });

        // Handle CSRF token mismatch (419 errors) for payment callbacks
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            $url = $request->url();

            \Illuminate\Support\Facades\Log::warning('CSRF Token Mismatch', [
                'url' => $url,
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Check if it's a payment callback URL
            if (str_contains($url, '/payment-success') ||
                str_contains($url, '/payment-failure') ||
                str_contains($url, '/add-money/success') ||
                str_contains($url, '/add-money/failure') ||
                str_contains($url, '/payu/webhook')) {

                // Redirect to login-from-cookie to restore session
                return redirect()->route('user.login-from-cookie', [
                    'redirect' => $url.($request->getQueryString() ? '?'.$request->getQueryString() : ''),
                    'error' => urlencode('Session expired. Restoring session...'),
                ]);
            }

            // For AJAX requests, return JSON response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page.',
                    'error' => 'Session Expired',
                ], 419);
            }

            // Default 419 handling - show error page
            return response()->view('errors.419', [], 419);
        });

        // Handle all other exceptions
        $exceptions->render(function (Throwable $e, $request) {
            // Log all exceptions
            \Illuminate\Support\Facades\Log::error('Exception: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
                'url' => $request->url(),
                'method' => $request->method(),
            ]);
        });
    })->create();
