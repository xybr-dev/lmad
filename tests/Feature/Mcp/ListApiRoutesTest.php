<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Lmad\Mcp\Tools\ListApiRoutes;

beforeEach(function () {
    Route::get('/api/test', fn () => response()->json(['ok' => true]))->name('api.test');
    Route::post('/api/users', fn () => response()->json(['created' => true]))->name('api.users.store');
});

it('lists all api routes', function () {
    $result = app(ListApiRoutes::class)->handle(new Request([]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content)->toHaveKey('count');
});

it('filters routes by path', function () {
    $result = app(ListApiRoutes::class)->handle(new Request([
        'path' => 'api/users',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['count'])->toBeGreaterThanOrEqual(1);
    expect(collect($content['routes'])->pluck('uri'))->toContain('api/users');
});

it('filters routes by method', function () {
    $result = app(ListApiRoutes::class)->handle(new Request([
        'method' => 'GET',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['count'])->toBeGreaterThanOrEqual(1);
});

it('returns empty array when no routes match', function () {
    $result = app(ListApiRoutes::class)->handle(new Request([
        'path' => 'nonexistent',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['count'])->toBe(0);
});
