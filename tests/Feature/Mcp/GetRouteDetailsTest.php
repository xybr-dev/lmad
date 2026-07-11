<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Support\Facades\Route;
use Lmad\Mcp\LmadServer;
use Lmad\Mcp\Tools\GetRouteDetails;
use Lmad\Tests\TestCase;

beforeEach(function () {
    Route::get('/api/test', fn () => response()->json(['ok' => true]))->name('api.test');
});

it('gets route details', function () {
    $response = LmadServer::tool(GetRouteDetails::class, [
        'uri' => 'api/test',
        'method' => 'GET',
    ]);

    $response->assertOk();
    $response->assertJsonPath('route.uri', 'api/test');
});

it('returns error for non-existent route', function () {
    $response = LmadServer::tool(GetRouteDetails::class, [
        'uri' => 'nonexistent',
        'method' => 'GET',
    ]);

    $response->assertHasErrors();
});

it('validates required parameters', function () {
    $response = LmadServer::tool(GetRouteDetails::class, [
        'method' => 'GET',
    ]);

    $response->assertHasErrors();
});
