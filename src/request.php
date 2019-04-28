<?php
declare(strict_types=1);

namespace request;

function method(): string
{
    if (key_exists('_method', $_POST)) {
        return $_POST['_method'];
    }

    if (key_exists('REQUEST_METHOD', $_SERVER)) {
        return $_SERVER['REQUEST_METHOD'];
    }

    return 'GET';
}

function path(): string
{
    return $_SERVER['PATH_INFO'] ?? '/';
}

function all(): array
{
    return \array_merge($_REQUEST, $_FILES);
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
            if (substr($key, 0, 5) == 'HTTP_') $headers[] = $key;

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

/**
 * @param string|array $key
 * @param mixed $default
 *
 * @return mixed
 */
function session($key, $default = null)
{
    if (\is_array($key)) {
        foreach ($key as $k => $v) {
            $_SESSION[$k] = $v;
        }

        return;
    }

    return $_SESSION[$key] ?? $default;
}

function flash(string $message, string $type = 'default'): void
{
    $_SESSION['_FLASH'][$type][] = $message;
}

function get_flash(string $type = 'default'): array
{
    $messages = $_SESSION['_FLASH'][$type] ?? [];
    unset($_SESSION['_FLASH'][$type]);
    return $messages;
}

function get_csrf(): string
{
    if (empty(\session_id())) {
        throw new \Exception('Session not statrted');
    }

    return \crypt\encrypt(\session_id());
}

function check_csrf(string $token): bool
{
    return \session_id() === \crypt\decrypt($token);
}
