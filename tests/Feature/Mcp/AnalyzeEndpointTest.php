<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Lmad\Mcp\LmadServer;
use Lmad\Mcp\Tools\AnalyzeEndpoint;
use Lmad\Tests\TestCase;

class AnalyzeTestFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

class AnalyzeTestController
{
    public function store(AnalyzeTestFormRequest $request): AnalyzeTestResource
    {
        return new AnalyzeTestResource(['id' => 1]);
    }
}

class AnalyzeTestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource['id'],
            'name' => 'Test',
        ];
    }
}

beforeEach(function () {
    Route::post('/api/analyze-test', [AnalyzeTestController::class, 'store'])
        ->name('api.analyze-test.store');
});

it('analyzes endpoint completely', function () {
    $response = LmadServer::tool(AnalyzeEndpoint::class, [
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]);

    $response->assertOk();
    $response->assertJsonPath('endpoint.uri', 'api/analyze-test');
    $response->assertJsonPath('endpoint.method', 'POST');
    $response->assertJsonPath('route.uri', 'api/analyze-test');
});

it('includes controller information', function () {
    $response = LmadServer::tool(AnalyzeEndpoint::class, [
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]);

    $response->assertOk();
    $response->assertJsonPath('controller.class', AnalyzeTestController::class);
    $response->assertJsonPath('controller.method', 'store');
});

it('includes request validation', function () {
    $response = LmadServer::tool(AnalyzeEndpoint::class, [
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]);

    $response->assertOk();
    $response->assertJsonPath('request.class', AnalyzeTestFormRequest::class);
    $response->assertJsonCount(2, 'request.rules');
});

it('includes response schema', function () {
    $response = LmadServer::tool(AnalyzeEndpoint::class, [
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]);

    $response->assertOk();
    $response->assertJsonPath('response.type', 'json_resource');
    $response->assertJsonPath('response.class', AnalyzeTestResource::class);
});

it('includes example usage', function () {
    $response = LmadServer::tool(AnalyzeEndpoint::class, [
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]);

    $response->assertOk();
    $response->assertJsonPath('example.http_method', 'POST');
    $response->assertJsonPath('example.uri', 'api/analyze-test');
});

it('returns error for non-existent route', function () {
    $response = LmadServer::tool(AnalyzeEndpoint::class, [
        'uri' => 'nonexistent',
        'method' => 'GET',
    ]);

    $response->assertHasErrors();
});
