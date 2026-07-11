<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Lmad\Mcp\Tools\GetResponseSchema;

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
    $result = app(GetResponseSchema::class)->handle(new Request([
        'controller_class' => TestController::class,
        'method' => 'index',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['controller'])->toBe(TestController::class);
    expect($content['method'])->toBe('index');
});

it('gets response schema for json resource', function () {
    $result = app(GetResponseSchema::class)->handle(new Request([
        'controller_class' => TestController::class,
        'method' => 'show',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['response']['return_type'])->toBe('\\'.TestResource::class);
});

it('returns error for non-existent controller', function () {
    $result = app(GetResponseSchema::class)->handle(new Request([
        'controller_class' => 'NonExistentController',
        'method' => 'index',
    ]));

    expect($result)->toBeInstanceOf(Response::class);
    expect($result->isError())->toBeTrue();
});

it('returns error for non-existent method', function () {
    $result = app(GetResponseSchema::class)->handle(new Request([
        'controller_class' => TestController::class,
        'method' => 'nonExistent',
    ]));

    expect($result)->toBeInstanceOf(Response::class);
    expect($result->isError())->toBeTrue();
});
