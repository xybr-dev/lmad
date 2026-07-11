<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Support\Facades\Route;
use Lmad\Mcp\LmadServer;
use Lmad\Mcp\Tools\ListApiRoutes;
use Lmad\Tests\TestCase;

beforeEach(function () {
    Route::get('/api/test', fn () => response()->json(['ok' => true]))->name('api.test');
    Route::post('/api/users', fn () => response()->json(['created' => true]))->name('api.users.store');
});

it('lists all api routes', function () {
    $response = LmadServer::tool(ListApiRoutes::class, []);

    $response->assertOk();
    $response->assertSee('count');
});

it('filters routes by path', function () {
    $response = LmadServer::tool(ListApiRoutes::class, [
        'path' => 'api/users',
    ]);

    $response->assertOk();
    $response->assertSee('api/users');
});

it('filters routes by method', function () {
    $response = LmadServer::tool(ListApiRoutes::class, [
        'method' => 'GET',
    ]);

    $response->assertOk();
});

it('returns empty array when no routes match', function () {
    $response = LmadServer::tool(ListApiRoutes::class, [
        'path' => 'nonexistent',
    ]);

    $response->assertOk();
    $response->assertSee('0');
});
