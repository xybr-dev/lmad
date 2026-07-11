<?php

declare(strict_types=1);

namespace Lmad\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Resource;
use Lmad\Support\RouteCollection;

/**
 * MCP Resource for API Routes.
 *
 * Provides dynamic access via route://{uri} URI template.
 * Returns details if URI is provided, otherwise returns the full route list.
 */
final class ApiRoutesResource extends Resource
{
    /** Resource name */
    protected string $name = 'api_routes';

    /** Resource description */
    protected string $description = 'Dynamic access to API routes information via route://{uri} URI template.';

    /** URI template (route://{uri}) */
    protected ?string $uriTemplate = 'route://{uri}';

    /** MIME type */
    protected ?string $mime = 'application/json';

    /** Meta information */
    protected ?array $meta = [
        'category' => 'discovery',
        'author' => '0xmergen',
    ];

    /**
     * Handles the MCP request.
     *
     * Returns the route if URI is provided, otherwise returns the full route list.
     *
     * @param  Request  $request  MCP request
     * @return Response|ResponseFactory Route information or error
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $uri = $request->get('uri');

        if (empty($uri)) {
            $routes = RouteCollection::getAll();

            return Response::structured([
                'description' => 'List all API routes. Provide a specific URI via route://{uri} for details.',
                'count' => count($routes),
                'routes' => $routes,
            ]);
        }

        $method = $request->get('method', 'GET');

        $route = RouteCollection::findByUriAndMethod($uri, $method);

        if (! $route) {
            return Response::error("Route not found for URI '{$uri}' with method '{$method}'.");
        }

        return Response::structured([
            'uri' => $uri,
            'method' => $method,
            'route' => $route,
        ]);
    }
}
