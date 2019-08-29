<?php
declare(strict_types=1);

namespace route;

use Closure;
use stdClass;
use Exception;
use InvalidArgumentException;
use function request\{method, path};
use function response\redirect as response_redirect;

function collection(?stdClass $route = null)
{
    static $collection;

    if ($collection === null) {
        $collection = [];
    }

    if ($route === null) {
        return $collection;
    }

    $collection[\implode('|', $route->methods) . ' ' . $route->path] = $route;
}

function names(?array $name = null)
{
    static $names;

    if ($names === null) {
        $names = [];
    }

    if ($name === null) {
        return $names;
    }

    $names = \array_merge($names, $name);
}

/**
 * @param array $methods
 * @param string $path
 * @param Closure|array|string $callback
 * @param array|string $options
 */
function map(array $methods, string $path, $callback, $options = [])
{
    $groups = group();

    if (
        \is_array($options) &&
        \array_key_exists('middleware', $options) && 
        !\is_array($options['middleware'])
    ) {
        throw new InvalidArgumentException(
            'Route middleware can only be array type'
        );
    }

    if (\is_string($options)) {
        $options = ['name' => $options];
    }

    if (\array_key_exists('namespace', $groups) && \is_string($callback)) {
        $callback = \implode('', $groups['namespace']) . $callback;
    }

    if (\array_key_exists('path', $groups)) {
        $path = \implode('', $groups['path']) . $path;
    }

    $middleware = $options['middleware'] ?? [];
    
    if (\array_key_exists('middleware', $groups)) {
        $groups['middleware'] = array_map(function($item) {
            return isset($item[0]) ? $item[0] : $item;
        }, $groups['middleware']);

        $middleware = \array_merge($groups['middleware'], $middleware);
    }

    $name = $options['name'] ?? null;

    if (\array_key_exists('name', $groups) && $name !== null) {
        $name = \implode('', $groups['name']) . $name;
    }

    $path = \rtrim($path, '/') ?: '/';

    $path = \strtr($path, [
        ':number' => '(\d+)',
        ':id' => '(\d+)',
        ':string' => '(\w+)',
        ':slug' => '([a-zA-Z0-9\-]+)',
        ':any' => '([^/])',
        ':all' => '(.*)'
    ]);

    if ($name !== null) {
        names([$name => $path]);
    }

    $route = (object) \compact(
        'methods', 'path', 'callback', 'name', 'middleware'
    );

    collection($route);
}

/**
 * @param Closure|string|array $callback
 * @param array $params
 */
function call($callback, array $params = [])
{
    $pattern = '!^([^\:]+)\@([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    if (\is_string($callback) && \preg_match($pattern, $callback) >= 1) {
        $callback = \explode('@', $callback, 2);
    }

    if (
        \is_array($callback) && 
        isset($callback[0]) && 
        \is_string($callback[0])
    ) {
        [$class, $method] = $callback;
        $callback = [new $class, $method];
    } elseif (\is_string($callback) && \class_exists($callback)) {
        $callback = new $callback;
    }

    \call_user_func_array($callback, $params);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array|string $options
 */
function get(string $path, $callback, $options = [])
{
    return map(['GET'], $path, $callback, $options);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array|string $options
 */
function post(string $path, $callback, $options = [])
{
    return map(['POST'], $path, $callback, $options);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array|string $options
 */
function put(string $path, $callback, $options = [])
{
    return map(['PUT'], $path, $callback, $options);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array|string $options
 */
function delete(string $path, $callback, $options = [])
{
    return map(['DELETE'], $path, $callback, $options);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array|string $options
 */
function any(string $path, $callback, $options = [])
{
    return map(['GET', 'POST', 'PUT', 'DELETE'], $path, $callback, $options);
}

function group($options = null, ?Closure $callback = null)
{
    static $groups;

    if ($groups === null) {
        $groups = [];
    }

    if ($options === null && $callback === null) {
        return $groups;
    }

    if (\is_string($options)) {
        $options = ['path' => $options];
    }

    if (
        \array_key_exists('middleware', $options) && 
        !\is_array($options['middleware'])
    ) {
        throw new InvalidArgumentException(
            'Route middleware can only be array type'
        );
    }

    foreach ($options as $name => $option) {
        $groups[$name][] = $option;
    }

    $callback();

    foreach ($groups as $name => $option) {
        \array_pop($groups[$name]);
    }
}

/**
 * @param string $path
 * @param string $class
 * @param array $options
 */
function resource(string $path, $class, $options = [])
{
    if (\is_string($options)) {
        $options = ['name' => $options];
    }

    if (
        \array_key_exists('middleware', $options) && 
        !\is_array($options['middleware'])
    ) {
        throw new InvalidArgumentException(
            'Route middleware can only be array type'
        );
    }

    $name = $options['name'] ?? \str_replace('/', '.', \trim($path, '/'));

    $middleware = $options['middleware'] ?? [];

    $only = $options['only'] ?? [
        'index', 'show', 'create', 'store', 'edit', 'update', 'destroy'
    ];

    $only = \is_string($only) ? \explode('|', $only) : $only;

    $methods = [
        'index' => 'get',
        'show' => 'get',
        'create' => 'get',
        'store' => 'post',
        'edit' => 'get',
        'update' => 'put',
        'destroy' => 'delete'
    ];

    if (\is_object($class)) {
        $only = \get_class_methods($class);
    } elseif (\is_array($class)) {
        $only = \array_keys($class);
    }

    foreach ($only as $method) {
        if (!\array_key_exists($method, $methods)) {
            return false;
        }

        $ownPath = $path;

        switch ($method) {
            case 'show':
            case 'update': 
            case 'destroy': 
                $ownPath .= '/(\d+)';
                break;
            case 'create':
                $ownPath .=  '/create';
                break;
            case 'edit':
                $ownPath .= '/(\d+)/edit';
                break;
        }

        if (\is_string($class)) {
            \call_user_func_array(
                '\route\\' . $methods[$method],
                [$ownPath, $class . '@' . $method, [
                    'name' => $name . '.' . $method,
                    'middleware' => $middleware
                ]]
            );
        } elseif (\is_object($class)) {
            \call_user_func_array(
                '\route\\' . $methods[$method],
                [$ownPath, [$class, $method], [
                    'name' => $name . '.' . $method,
                    'middleware' => $middleware
                ]]
            );
        } elseif (\is_array($class)) {
            \call_user_func_array(
                '\route\\' . $methods[$method],
                [$ownPath, $class[$method], [
                    'name' => $name . '.' . $method,
                    'middleware' => $middleware
                ]]
            );
        }
    }
}

/**
 * @param string $path
 * @param string $class
 * @param array $middleware
 */
function api_resource(string $path, $class, $options = [])
{
    if (\is_string($options)) {
        $options = ['name' => $options];
    }

    $options['only'] = $options['only'] ?? [
        'index', 'show', 'store', 'update', 'destroy'
    ];

    return resource($path, $class, $options);    
}

/**
 * @param Closure|string|array|null $callback
 */
function error($callback = null)
{
    static $cb;

    if ($callback === null) {
        return $cb;
    }

    $cb = $callback;
}

/**
 * @return stdClass|boolean
 */
function resolve()
{
    $routes = collection();
    $method = method();
    $path = path();

    if (\array_key_exists($key = $method . ' ' . $path, $routes)) {
        return $routes[$key];
    }

    foreach ($routes as $route) {
        $methodMatch = \in_array($method, $route->methods);
        $pathMatch = $route->path == $path || \preg_match(
            "~^{$route->path}$~ixs", $path, $params
        ) >= 1;
        $params = $params ?? [];
        \array_shift($params);
        $route->params = $params;

        if ($methodMatch && $pathMatch) {
            return $route;
        }
    }

    return false;
}

/**
 * @return mixed
 */
function run()
{
    $route = resolve();
    $params = $route->params ?? [];

    if ($route !== false ) {
        foreach ($route->middleware as $middleware) call($middleware);
        return call($route->callback, $params);
    }

    \http_response_code(404);
    $error = error() ?? function() {};
    return call($error);
}

/**
 * @throws Exception
 */
function url(string $name, ...$args) {
    if (isset($args[0]) && \is_array($args[0])) {
        $args = $args[0];
    }

    if (\array_key_exists($name, $names = names())) {
        $pattern = $names[$name];

        foreach ($args as $arg) {
            $pattern = \preg_replace('/\(.*?\)/', $arg, $pattern, 1);
        }

        return $pattern;
    }

    throw new Exception(\sprintf('Named route not exists "%s"', $name));
}

function redirect(string $name, ...$args)
{
    return response_redirect(url($name, ...$args));
}
