<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Lmad\Mcp\LmadServer;
use Lmad\Mcp\Resources\ApiRoutesResource;
use Lmad\Mcp\Resources\ControllerResource;
use Lmad\Mcp\Tools\AnalyzeEndpoint;
use Lmad\Mcp\Tools\GetRequestRules;
use Lmad\Mcp\Tools\GetResponseSchema;
use Lmad\Mcp\Tools\GetRouteDetails;
use Lmad\Mcp\Tools\ListApiRoutes;

it('has correct server name', function () {
    $defaults = (new \ReflectionClass(LmadServer::class))->getDefaultProperties();

    expect($defaults['name'])->toBe('LMAD API Discovery');
});

it('has correct version', function () {
    $defaults = (new \ReflectionClass(LmadServer::class))->getDefaultProperties();

    expect($defaults['version'])->toBe('1.0.0');
});

it('has description', function () {
    $defaults = (new \ReflectionClass(LmadServer::class))->getDefaultProperties();

    expect($defaults['description'])->not->toBeEmpty();
});

it('has instructions', function () {
    $defaults = (new \ReflectionClass(LmadServer::class))->getDefaultProperties();

    expect($defaults['instructions'])->not->toBeEmpty();
});

it('registers all tools', function () {
    $defaults = (new \ReflectionClass(LmadServer::class))->getDefaultProperties();

    expect($defaults['tools'])->toHaveCount(5);
    expect($defaults['tools'])->toContain(
        ListApiRoutes::class,
        GetRouteDetails::class,
        GetRequestRules::class,
        GetResponseSchema::class,
        AnalyzeEndpoint::class,
    );
});

it('registers all resources', function () {
    $defaults = (new \ReflectionClass(LmadServer::class))->getDefaultProperties();

    expect($defaults['resources'])->toHaveCount(2);
    expect($defaults['resources'])->toContain(
        ApiRoutesResource::class,
        ControllerResource::class,
    );
});
