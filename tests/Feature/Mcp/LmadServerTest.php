<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Lmad\Mcp\LmadServer;
use Lmad\Tests\TestCase;

it('has correct server name', function () {
    expect(LmadServer::name())->toBe('LMAD API Discovery');
});

it('has correct version', function () {
    expect(LmadServer::version())->toBe('1.0.0');
});

it('has description', function () {
    expect(LmadServer::description())->not->toBeEmpty();
});

it('has instructions', function () {
    expect(LmadServer::instructions())->not->toBeEmpty();
});

it('registers all tools', function () {
    $tools = LmadServer::tools();

    expect($tools)->toHaveCount(5);
    expect($tools)->toContain(
        'Lmad\\Mcp\\Tools\\ListApiRoutes',
        'Lmad\\Mcp\\Tools\\GetRouteDetails',
        'Lmad\\Mcp\\Tools\\GetRequestRules',
        'Lmad\\Mcp\\Tools\\GetResponseSchema',
        'Lmad\\Mcp\\Tools\\AnalyzeEndpoint',
    );
});

it('registers all resources', function () {
    $resources = LmadServer::resources();

    expect($resources)->toHaveCount(2);
    expect($resources)->toContain(
        'Lmad\\Mcp\\Resources\\ApiRoutesResource',
        'Lmad\\Mcp\\Resources\\ControllerResource',
    );
});
