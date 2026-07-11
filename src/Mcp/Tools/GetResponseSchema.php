<?php

declare(strict_types=1);

namespace Lmad\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Lmad\Mcp\Schema\ResponseInspector;

/**
 * MCP Tool for analyzing controller method response schema.
 *
 * Analyzes what the method returns (JsonResource, Model, Response, etc.).
 */
final class GetResponseSchema extends Tool
{
    /** Tool name */
    protected string $name = 'get_response_schema';

    /** Tool description */
    protected string $description = 'Analyzes what an endpoint returns - JsonResource structure, Model attributes, Collection contents, or Response type.';

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
                'controller_class' => [
                    'type' => 'string',
                    'description' => 'Full controller class name (e.g., App\\Http\\Controllers\\UserController)',
                ],
                'method' => [
                    'type' => 'string',
                    'description' => 'Controller method name (e.g., index, store, show, update, destroy)',
                ],
            ],
            'required' => ['controller_class', 'method'],
        ];
    }

    /** Meta information */
    protected ?array $meta = [
        'category' => 'schema',
        'author' => '0xmergen',
    ];

    public function __construct(
        private readonly ResponseInspector $responseInspector,
    ) {}

    /**
     * Handles the MCP request.
     *
     * @param  Request  $request  MCP request
     * @return Response|ResponseFactory Response schema or error
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $controllerClass = (string) $request->get('controller_class');
        $method = (string) $request->get('method');

        if (empty($controllerClass)) {
            return Response::error('Controller class is required.');
        }

        if (empty($method)) {
            return Response::error('Method is required.');
        }

        if (! class_exists($controllerClass)) {
            return Response::error("Controller class '{$controllerClass}' does not exist.");
        }

        if (! method_exists($controllerClass, $method)) {
            return Response::error("Method '{$method}' does not exist in controller '{$controllerClass}'.");
        }

        $schema = $this->responseInspector->inspect($controllerClass, $method);

        return Response::structured([
            'controller' => $controllerClass,
            'method' => $method,
            'response' => $schema,
        ]);
    }
}
