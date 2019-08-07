<?php
declare(strict_types=1);

namespace session;

use LogicException;

/**
 * @param  mixed $default
 * @throws Exception
 * @return mixed
 */
function get(string $key, $default = null)
{
    $data = all();

    return $data[$key] ?? $default;
}

/**
 * @param array|string $key
 * @throws Exception
 * @param mixed $value
 */
function set($key, $value = null)
{
    if (empty(\session_id())) {
        throw new LogicException('Session is not started');
    }

    if (\is_array($key)) {
        foreach ($key as $k => $v) $_SESSION[$k] = $v;
    } else {
        $_SESSION[$key] = $value;
    }
}

/**
 * @throws Exception
 */
function all(): array
{
    if (empty(\session_id())) {
        throw new LogicException('Session is not started');
    }

    return $_SESSION;
}
