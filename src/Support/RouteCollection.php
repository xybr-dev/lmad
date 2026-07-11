<?php

declare(strict_types=1);

namespace Lmad\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * Helper static methods for route collection operations.
 *
 * Used for listing all routes with filters, and finding routes by URI or name.
 */
final class RouteCollection
{
    /**
     * Returns all routes with optional filters.
     *
     * Supports filtering by path, method, domain, and vendor routes.
     *
     * @param  array{path?: string, method?: string, domain?: string, except_vendor?: bool, only_vendor?: bool}  $filters  Filter criteria
     * @return array<array{uri: string, methods: array, name: string|null, domain: string, controller: string|null, action: array, middleware: array, wheres: array, parameters: array}>
     */
    public static function getAll(array $filters = []): array
    {
        $routes = collect(RouteFacade::getRoutes()->getRoutes())->flatten();

        $allRoutes = $routes
            ->filter(fn (Route $route) => self::matchesFilters($route, $filters))
            ->map(fn (Route $route) => self::serializeRoute($route))
            ->values()
            ->all();

        return $allRoutes;
    }

    /**
     * Finds a route by URI and HTTP method.
     *
     * @param  string  $uri  Route URI (e.g., "api/users")
     * @param  string  $method  HTTP method (e.g., "GET", "POST")
     * @return Route|null Route object or null if not found
     */
    public static function findByUriAndMethod(string $uri, string $method): ?Route
    {
        $routes = collect(RouteFacade::getRoutes()->getRoutes())->flatten();

        return $routes->first(fn (Route $route) => $route->uri === $uri && in_array($method, $route->methods, true)) ?: null;
    }

    /**
     * Finds a route by name.
     *
     * @param  string  $name  Route name (named route)
     * @return Route|null Route object or null if not found
     */
    public static function findByName(string $name): ?Route
    {
        return RouteFacade::getRoutes()->getByName($name);
    }

    /**
     * Checks if a route matches the filter criteria.
     *
     * @param  Route  $route  Route to check
     * @param  array{path?: string, method?: string, domain?: string, except_vendor?: bool, only_vendor?: bool}  $filters  Filter criteria
     * @return bool True if matches filters
     */
    private static function matchesFilters(Route $route, array $filters): bool
    {
        if (isset($filters['path']) && ! self::pathMatches($route->uri, (string) $filters['path'])) {
            return false;
        }

        if (isset($filters['method']) && ! in_array(strtoupper((string) $filters['method']), $route->methods, true)) {
            return false;
        }

        if (isset($filters['domain']) && $route->getDomain() !== (string) $filters['domain']) {
            return false;
        }

        if (! empty($filters['except_vendor']) && self::isVendorRoute($route)) {
            return false;
        }

        if (! empty($filters['only_vendor']) && ! self::isVendorRoute($route)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a route URI matches a filter pattern.
     *
     * The wildcard (*) character is converted to regex ".*".
     *
     * @param  string  $routeUri  Route URI
     * @param  string  $filterPath  Filter path (supports wildcards)
     * @return bool True if matches
     */
    private static function pathMatches(string $routeUri, string $filterPath): bool
    {
        $pattern = str_replace('*', '.*', preg_quote($filterPath, '/'));

        return (bool) preg_match("/^{$pattern}/", $routeUri);
    }

    /**
     * Checks if a route is a vendor route.
     *
     * Considered a vendor route if the controller namespace contains
     * "Vendor", "Laravel\", or "Illuminate\".
     *
     * @param  Route  $route  Route to check
     * @return bool True if vendor route
     */
    private static function isVendorRoute(Route $route): bool
    {
        $action = $route->getAction();

        if (isset($action['controller'])) {
            $controller = $action['controller'];

            return Str::contains($controller, ['Vendor', 'Laravel\\', 'Illuminate\\']);
        }

        if (isset($action['uses']) && is_string($action['uses'])) {
            return Str::contains($action['uses'], ['Vendor', 'Laravel\\', 'Illuminate\\']);
        }

        return false;
    }

    /**
     * Serializes a route object.
     *
     * Converts route information to array format.
     *
     * @param  Route  $route  Route object
     * @return array{uri: string, methods: array, name: string|null, domain: string, controller: string|null, action: array, middleware: array, wheres: array, parameters: array}
     */
    private static function serializeRoute(Route $route): array
    {
        $action = $route->getAction();

        return [
            'uri' => $route->uri,
            'methods' => $route->methods,
            'name' => $route->getName(),
            'domain' => $route->getDomain(),
            'controller' => $action['controller'] ?? null,
            'action' => self::parseAction($action),
            'middleware' => array_values($route->middleware()),
            'wheres' => $route->wheres,
            'parameters' => $route->parameterNames(),
        ];
    }

    /**
     * Parses a route action array.
     *
     * Separates the controller@class@method format into class and method.
     *
     * @param  array  $action  Route action array
     * @return array{controller?: string, class?: string|null, method?: string|null, uses?: string}
     */
    private static function parseAction(array $action): array
    {
        $parsed = [];

        if (isset($action['controller'])) {
            $parsed['controller'] = $action['controller'];
            $parts = explode('@', $action['controller']);
            $parsed['class'] = $parts[0] ?? null;
            $parsed['method'] = $parts[1] ?? null;
        }

        if (isset($action['uses'])) {
            $parsed['uses'] = $action['uses'];

            if (is_string($action['uses']) && str_contains($action['uses'], '@')) {
                $parts = explode('@', $action['uses']);
                $parsed['class'] = $parts[0] ?? null;
                $parsed['method'] = $parts[1] ?? null;
            } elseif (is_callable($action['uses'])) {
                $parsed['uses'] = 'Closure';
            }
        }

        return $parsed;
    }
}
