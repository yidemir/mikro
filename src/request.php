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

    if (key_exists($key, $_GET)) {
        return $_GET[$key];
    }

    if (key_exists($key, $_POST)) {
        return $_POST[$key];
    }

    if (key_exists($key, $_FILES)) {
        return $_FILES[$key];
    }

    return $default;
}

function is_ajax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        \strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
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

function flash(string $message, string $type = 'default')
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

function check_csrf(string $token)
{
    return \session_id() === \crypt\decrypt($token);
}
