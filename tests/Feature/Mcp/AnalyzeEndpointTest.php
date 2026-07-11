<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Lmad\Mcp\Tools\AnalyzeEndpoint;

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
    $result = app(AnalyzeEndpoint::class)->handle(new Request([
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['endpoint']['uri'])->toBe('api/analyze-test');
    expect($content['endpoint']['method'])->toBe('POST');
    expect($content['route']['uri'])->toBe('api/analyze-test');
});

it('includes controller information', function () {
    $result = app(AnalyzeEndpoint::class)->handle(new Request([
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['controller']['class'])->toBe(AnalyzeTestController::class);
    expect($content['controller']['method'])->toBe('store');
});

it('includes request validation', function () {
    $result = app(AnalyzeEndpoint::class)->handle(new Request([
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['request']['class'])->toBe('\\'.AnalyzeTestFormRequest::class);
    expect($content['request']['rules'])->toHaveCount(2);
});

it('includes response schema', function () {
    $result = app(AnalyzeEndpoint::class)->handle(new Request([
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['response']['return_type'])->toBe('\\'.AnalyzeTestResource::class);
});

it('includes example usage', function () {
    $result = app(AnalyzeEndpoint::class)->handle(new Request([
        'uri' => 'api/analyze-test',
        'method' => 'POST',
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['example']['http_method'])->toBe('POST');
    expect($content['example']['uri'])->toBe('api/analyze-test');
});

it('returns error for non-existent route', function () {
    $result = app(AnalyzeEndpoint::class)->handle(new Request([
        'uri' => 'nonexistent',
        'method' => 'GET',
    ]));

    expect($result)->toBeInstanceOf(Response::class);
    expect($result->isError())->toBeTrue();
});
