<?php
declare(strict_types=1);

namespace cookie;

use function crypt\{encrypt, decrypt};

function disable_encryption(?bool $is = null): bool
{
    static $disabled;

    if ($disabled === null) {
        $disabled = false;
    }

    if ($is !== null) {
        $disabled = $is;
    }

    return $disabled;
}

/**
 * @param mixed $default
 */
function get(string $key, $default = null)
{
    if (disable_encryption()) {
        return $_COOKIE[$key] ?? $default;
    } else {
        return \array_key_exists($key, $_COOKIE) ?
            decrypt($_COOKIE[$key]) : $default;
    }
}

function set(
    $key,
    $value,
    $expires = 0,
    string $path = '',
    string $domain = '',
    bool $secure = false,
    bool $httpOnly = false
): bool
{
    if (!disable_encryption()) {
        $value = encrypt($value);
    }

    if (\is_string($expires)) {
        $expires = \strtotime($expires);
    }

    return \setcookie(
        $key, $value, $expires, $path, $domain, $secure, $httpOnly
    );
}
