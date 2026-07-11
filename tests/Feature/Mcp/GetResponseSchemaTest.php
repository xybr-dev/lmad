<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Http\Resources\Json\JsonResource;
use Lmad\Mcp\LmadServer;
use Lmad\Mcp\Tools\GetResponseSchema;
use Lmad\Tests\TestCase;

class TestController
{
    public function index(): array
    {
        return ['data' => 'test'];
    }

    public function show(): TestResource
    {
        return new TestResource(['id' => 1]);
    }
}

class TestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource['id'],
            'name' => 'Test Name',
        ];
    }
}

it('gets response schema for array return', function () {
    $response = LmadServer::tool(GetResponseSchema::class, [
        'controller_class' => TestController::class,
        'method' => 'index',
    ]);

    $response->assertOk();
    $response->assertJsonPath('controller', TestController::class);
    $response->assertJsonPath('method', 'index');
});

it('gets response schema for json resource', function () {
    $response = LmadServer::tool(GetResponseSchema::class, [
        'controller_class' => TestController::class,
        'method' => 'show',
    ]);

    $response->assertOk();
    $response->assertJsonPath('response.type', 'json_resource');
    $response->assertJsonPath('response.class', TestResource::class);
});

it('returns error for non-existent controller', function () {
    $response = LmadServer::tool(GetResponseSchema::class, [
        'controller_class' => 'NonExistentController',
        'method' => 'index',
    ]);

    $response->assertHasErrors();
});

it('returns error for non-existent method', function () {
    $response = LmadServer::tool(GetResponseSchema::class, [
        'controller_class' => TestController::class,
        'method' => 'nonExistent',
    ]);

    $response->assertHasErrors();
});
