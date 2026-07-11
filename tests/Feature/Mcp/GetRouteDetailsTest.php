<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Lmad\Mcp\Tools\GetRouteDetails;

beforeEach(function () {
    Route::get('/api/test', fn () => response()->json(['ok' => true]))->name('api.test');
});

it('gets route details', function () {
    $result = app(GetRouteDetails::class)->handle(new Request([
        'uri' => 'api/test',
        'method' => 'GET',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['route']['uri'])->toBe('api/test');
});

it('returns error for non-existent route', function () {
    $result = app(GetRouteDetails::class)->handle(new Request([
        'uri' => 'nonexistent',
        'method' => 'GET',
    ]));

    expect($result)->toBeInstanceOf(Response::class);
    expect($result->isError())->toBeTrue();
});

it('validates required parameters', function () {
    $result = app(GetRouteDetails::class)->handle(new Request([
        'method' => 'GET',
    ]));

    expect($result)->toBeInstanceOf(Response::class);
    expect($result->isError())->toBeTrue();
});
