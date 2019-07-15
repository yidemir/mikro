<?php
declare(strict_types=1);

namespace route;

use Closure;
use request;
use stdClass;

function collection(?stdClass $route = null)
{
    static $collection;

    if ($collection === null) {
        $collection = [];
    }

    if ($route === null) {
        return $collection;
    }

    $collection[implode('|', $route->methods) . ' ' . $route->path] = $route;
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
 * @param array $middleware
 */
function map(
    array $methods,
    string $path,
    $callback,
    ?string $name = null,
    array $middleware = []
)
{
    $groups = group();

    if (\array_key_exists('namespace', $groups) && \is_string($callback)) {
        $callback = \implode('', $groups['namespace']) . $callback;
    }

    if (\array_key_exists('path', $groups)) {
        $path = \implode('', $groups['path']) . $path;
    }

    if (\array_key_exists('middleware', $groups)) {
        $mws = isset($groups['middleware'][0]) ? $groups['middleware'][0] : [];
        $middleware = \array_merge($mws, $middleware);
    }

    if (\array_key_exists('name', $groups) && !is_null($name)) {
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

    $route = (object) \compact(
        'methods', 'path', 'callback', 'name', 'middleware'
    );

    if ($name !== null) {
        names([$route->name => $route->path]);
    }

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
 * @param array $middleware
 */
function get(string $path, $callback, ?string $name = null, array $middleware = [])
{
    return map(['GET'], $path, $callback, $name, $middleware);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array $middleware
 */
function post(string $path, $callback, ?string $name = null, array $middleware = [])
{
    return map(['POST'], $path, $callback, $name, $middleware);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array $middleware
 */
function put(string $path, $callback, ?string $name = null, array $middleware = [])
{
    return map(['PUT'], $path, $callback, $name, $middleware);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array $middleware
 */
function delete(string $path, $callback, ?string $name = null, array $middleware = [])
{
    return map(['DELETE'], $path, $callback, $name, $middleware);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array $middleware
 */
function any(string $path, $callback, ?string $name = null, array $middleware = [])
{
    return map(['GET', 'POST', 'PUT', 'DELETE'], $path, $callback, $name, $middleware);
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
 * @param array $middleware
 */
function resource(string $path, $class, ?string $name = null, array $middleware = [])
{
    if ($name === null) {
        $name = str_replace('/', '.', trim($path, '/'));
    }

    get($path, "{$class}@index", $name, $middleware);
    get("$path/(\d+)", "{$class}@show", $name, $middleware);
    get("$path/create", "{$class}@create", $name, $middleware);
    post($path, "{$class}@store", $name, $middleware);
    get("$path/edit/(\d+)", "{$class}@edit", $name, $middleware);
    put("$path/(\d+)", "{$class}@update", $name, $middleware);
    delete("$path/(\d+)", "{$class}@destroy", $name, $middleware);
}

/**
 * @param string $path
 * @param string $class
 * @param array $middleware
 */
function api_resource(string $path, $class, ?string $name = null, array $middleware = [])
{
    if ($name === null) {
        $name = str_replace('/', '.', trim($path, '/'));
    }

    get($path, "{$class}@index", $name, $middleware);
    get("$path/(\d+)", "{$class}@show", $name, $middleware);
    post($path, "{$class}@store", $name, $middleware);
    put("$path/(\d+)", "{$class}@update", $name, $middleware);
    delete("$path/(\d+)", "{$class}@destroy", $name, $middleware);
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
    $method = request\method();
    $path = request\path();

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
        foreach ($route->middleware as $mw) call($mw);
        return call($route->callback, $params);
    }

    \http_response_code(404);
    $error = error() ?? function() {};
    return call($error);
}

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
}
