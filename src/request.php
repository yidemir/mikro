<?php
declare(strict_types=1);

namespace request;

use Exception;

function method(): string
{
    if (\array_key_exists('_method', $_POST)) {
        return $_POST['_method'];
    }

    if (\array_key_exists('REQUEST_METHOD', $_SERVER)) {
        return $_SERVER['REQUEST_METHOD'];
    }

    return 'GET';
}

function path(): string
{
    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $path = \explode('?', $path, 2);
    $path = \rtrim($path[0], '/');
    $path = $path === '' ? '/' : $path;

    return $path;
}

function query_string(): string
{
    return $_SERVER['QUERY_STRING'] ?? '';
}

function query(): array
{
    \parse_str(query_string(), $query);

    return $query;
}

function all(): array
{
    return \array_merge($_REQUEST, $_FILES, content());
}

/**
 * @param array|string $key
 * @param mixed $default
 *
 * @return mixed
 */
function input($key, $default = null)
{
    if (\is_array($key)) {
        $collect = [];
        foreach ($key as $k) {
            if (input($k) && !\is_array($k)) {
                $collect[$k] = input($k);
            }
        }
        return $collect;
    }

    if (\array_key_exists($key, $_GET)) {
        return $_GET[$key];
    }

    if (\array_key_exists($key, $_POST)) {
        return $_POST[$key];
    }

    if (\array_key_exists($key, $content = content())) {
        return $content[$key];
    }

    if (\array_key_exists($key, $_FILES)) {
        return $_FILES[$key];
    }

    return $default;
}

function content(): array
{
    $content = \file_get_contents('php://input');
    $content = @\json_decode($content, true);
    return (array) $content;
}

function headers(): array
{
    $serverKeys = \array_keys($_SERVER);

    $httpHeaders = \array_reduce(
        $serverKeys,
        function (array $headers, $key): array {
            if ($key == 'CONTENT_TYPE') $headers[] = $key;
            if ($key == 'CONTENT_LENGTH') $headers[] = $key;
            if (\substr($key, 0, 5) == 'HTTP_') $headers[] = $key;

            return $headers;
        },
        []
    );

    $values = \array_map(function (string $header) {
        return $_SERVER[$header];
    }, $httpHeaders);

    $headers = \array_map(function (string $header) {
        if (\substr($header, 0, 5) == 'HTTP_') {
            $header = \substr($header, 5);
            if (false === $header) {
                $header = 'HTTP_';
            }
        }

        return \str_replace(
            ' ', '-', \ucwords(\strtolower(\str_replace('_', ' ', $header)))
        );
    }, $httpHeaders);

    return \array_combine($headers, $values);
}

/**
 * @param mixed $default
 */
function get_header(string $key, $default = null)
{
    $headers = headers();
    
    return $headers[$key] ?? $default;
}

function is_ajax(): bool
{
    return isset($_SERVER['X-Requested-With']) &&
        \strtolower($_SERVER['X-Requested-With']) == 'xmlhttprequest';
}
