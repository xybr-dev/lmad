<?php

declare(strict_types=1);

namespace Lmad\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Lmad\Support\RouteCollection;

/**
 * MCP Tool for listing all API routes.
 *
 * Lists routes with optional filtering options.
 */
final class ListApiRoutes extends Tool
{
    /** Tool name */
    protected string $name = 'list_api_routes';

    /** Tool description */
    protected string $description = 'Lists all API routes with optional filters for path pattern, HTTP method, domain, and vendor routes.';

    /**
     * JSON Schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'path' => $schema->string()->description('Filter routes by URI pattern (supports wildcards, e.g., \'api/users*\')'),
            'method' => $schema->string()->description('Filter by HTTP method (GET, POST, PUT, PATCH, DELETE)')->enum(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS']),
            'domain' => $schema->string()->description('Filter by domain'),
            'except_vendor' => $schema->boolean()->description('Exclude vendor/Laravel framework routes'),
            'only_vendor' => $schema->boolean()->description('Only show vendor/Laravel framework routes'),
        ];
    }

    /** Meta information */
    protected ?array $meta = [
        'category' => 'discovery',
        'author' => '0xmergen',
    ];

    /**
     * Handles the MCP request.
     *
     * @param  Request  $request  MCP request
     * @return Response|ResponseFactory Route list
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $filters = $this->parseFilters($request);
        $routes = RouteCollection::getAll($filters);

        return Response::structured([
            'count' => count($routes),
            'filters' => $filters,
            'routes' => $routes,
        ]);
    }

    /**
     * Extracts filters from the request.
     *
     * Filters out null or empty values.
     *
     * @param  Request  $request  MCP request
     * @return array{path?: string, method?: string, domain?: string, except_vendor?: bool, only_vendor?: bool}
     */
    private function parseFilters(Request $request): array
    {
        return array_filter([
            'path' => (string) $request->get('path'),
            'method' => (string) $request->get('method'),
            'domain' => (string) $request->get('domain'),
            'except_vendor' => (bool) $request->get('except_vendor', false),
            'only_vendor' => (bool) $request->get('only_vendor', false),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
