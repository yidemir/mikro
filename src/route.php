<?php
declare(strict_types=1);

namespace route;

use Closure;
use request;

/**
 * @param array $methods
 * @param string $path
 * @param Closure|array|string $callback
 * @param array $middleware
 */
function map(array $methods, string $path, $callback, array $middleware = [])
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

    $path = \rtrim($path, '/') ?: '/';
    $path = \strtr($path, [
        ':number' => '(\d+)',
        ':id' => '(\d+)',
        ':string' => '(\w+)',
        ':slug' => '([a-zA-Z0-9\-]+)',
        ':any' => '([^/])',
        ':all' => '(.*)'
    ]);

    $methodMatch = \in_array(request\method(), $methods);
    $pathMatch = ($path === request\path()) || \preg_match("~^$path\$~ixs", request\path(), $params) >= 1;
    $params = $params ?? [];

    if ($methodMatch && $pathMatch) {
        foreach ($middleware as $mw) call($mw);

        if (!\defined('ROUTE_MATCHED')) {
            \define('ROUTE_MATCHED', true);
        }

        call($callback, \array_slice($params, 1));
    }
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
function get(string $path, $callback, array $middleware = [])
{
    return map(['GET'], $path, $callback, $middleware);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array $middleware
 */
function post(string $path, $callback, array $middleware = [])
{
    return map(['POST'], $path, $callback, $middleware);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array $middleware
 */
function put(string $path, $callback, array $middleware = [])
{
    return map(['PUT'], $path, $callback, $middleware);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array $middleware
 */
function delete(string $path, $callback, array $middleware = [])
{
    return map(['DELETE'], $path, $callback, $middleware);
}

/**
 * @param string $path
 * @param array|object|string $callback
 * @param array $middleware
 */
function any(string $path, $callback, array $middleware = [])
{
    return map(['GET', 'POST', 'PUT', 'DELETE'], $path, $callback, $middleware);
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
function resource(string $path, $class, array $middleware = [])
{
    get($path, "{$class}@index", $middleware);
    get("$path/(\d+)", "{$class}@show", $middleware);
    get("$path/create", "{$class}@create", $middleware);
    post($path, "{$class}@store", $middleware);
    get("$path/edit/(\d+)", "{$class}@edit", $middleware);
    put("$path/(\d+)", "{$class}@update", $middleware);
    delete("$path/(\d+)", "{$class}@destroy", $middleware);
}

/**
 * @param string $path
 * @param string $class
 * @param array $middleware
 */
function api_resource(string $path, $class, array $middleware = [])
{
    get($path, "{$class}@index", $middleware);
    get("$path/(\d+)", "{$class}@show", $middleware);
    post($path, "{$class}@store", $middleware);
    put("$path/(\d+)", "{$class}@update", $middleware);
    delete("$path/(\d+)", "{$class}@destroy", $middleware);
}

/**
 * @param Closure|string|array $callback
 */
function error($callback)
{
    $groups = group();

    if (\array_key_exists('namespace', $groups) && \is_string($callback)) {
        $callback = \implode('', $groups['namespace']) . $callback;
    }

    if (!\defined('ROUTE_MATCHED')) {
        \http_response_code(404);
        call($callback);
    }
}
