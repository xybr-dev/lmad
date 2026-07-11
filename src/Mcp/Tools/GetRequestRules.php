<?php

declare(strict_types=1);

namespace Lmad\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Lmad\Mcp\Schema\RequestInspector;

/**
 * MCP Tool for analyzing FormRequest validation rules.
 *
 * Returns validation rules, custom error messages, attribute names,
 * and authorization logic.
 */
final class GetRequestRules extends Tool
{
    /** Tool name */
    protected string $name = 'get_request_rules';

    /** Tool description */
    protected string $description = 'Analyzes a FormRequest validation rules, custom error messages, attribute names, and authorization logic.';

    /**
     * JSON Schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'request_class' => [
                    'type' => 'string',
                    'description' => 'Full FormRequest class name (e.g., App\\Http\\Requests\\StoreUserRequest)',
                ],
            ],
            'required' => ['request_class'],
        ];
    }

    /** Meta information */
    protected ?array $meta = [
        'category' => 'validation',
        'author' => '0xmergen',
    ];

    public function __construct(
        private readonly RequestInspector $requestInspector,
    ) {}

    /**
     * Handles the MCP request.
     *
     * @param  Request  $request  MCP request
     * @return Response|ResponseFactory Validation rules or error
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $requestClass = (string) $request->get('request_class');

        if (empty($requestClass)) {
            return Response::error('Request class is required.');
        }

        $inspection = $this->requestInspector->inspect($requestClass);

        if (isset($inspection['error'])) {
            return Response::error($inspection['error']);
        }

        return Response::structured($inspection);
    }
}
