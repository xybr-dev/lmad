<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Lmad\Mcp\Tools\GetRequestRules;

class TestFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

it('gets request rules', function () {
    $result = app(GetRequestRules::class)->handle(new Request([
        'request_class' => TestFormRequest::class,
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['class'])->toBe(TestFormRequest::class);
    expect($content['rules'])->toHaveCount(2);
});

it('returns error for non-existent request class', function () {
    $result = app(GetRequestRules::class)->handle(new Request([
        'request_class' => 'NonExistentRequest',
    ]));

    expect($result)->toBeInstanceOf(Response::class);
    expect($result->isError())->toBeTrue();
});

it('includes authorization info', function () {
    $result = app(GetRequestRules::class)->handle(new Request([
        'request_class' => TestFormRequest::class,
    ]));

    expect($result)->toBeInstanceOf(ResponseFactory::class);
    $content = $result->getStructuredContent();
    expect($content['authorization']['has_authorize'])->toBeTrue();
    expect($content['authorization']['authorized'])->toBeTrue();
});
