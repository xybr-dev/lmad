<?php

declare(strict_types=1);

namespace Lmad\Tests\Feature\Mcp;

use Illuminate\Foundation\Http\FormRequest;
use Lmad\Mcp\LmadServer;
use Lmad\Mcp\Tools\GetRequestRules;
use Lmad\Tests\TestCase;

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
    $response = LmadServer::tool(GetRequestRules::class, [
        'request_class' => TestFormRequest::class,
    ]);

    $response->assertOk();
    $response->assertJsonPath('class', TestFormRequest::class);
    $response->assertJsonCount(2, 'rules');
});

it('returns error for non-existent request class', function () {
    $response = LmadServer::tool(GetRequestRules::class, [
        'request_class' => 'NonExistentRequest',
    ]);

    $response->assertHasErrors();
});

it('includes authorization info', function () {
    $response = LmadServer::tool(GetRequestRules::class, [
        'request_class' => TestFormRequest::class,
    ]);

    $response->assertOk();
    $response->assertJsonPath('authorization.has_authorize', true);
    $response->assertJsonPath('authorization.authorized', true);
});
