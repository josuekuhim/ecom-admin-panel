<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add CORS headers
        $middleware->group('api', [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\App\Exceptions\Domain\OrderNotFoundException $e, \Illuminate\Http\Request $request) {
            return response()->json(['message' => 'Not found'], \Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
        });

        $exceptions->renderable(function (\App\Exceptions\Domain\OrderAlreadyProcessedException $e, \Illuminate\Http\Request $request) {
            return response()->json(['message' => 'This order has already been processed.'], \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        });
    })->create();
