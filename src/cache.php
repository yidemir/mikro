<?php
declare(strict_types=1);

namespace cache;

use Closure;

function path(?string $path = null): string
{
    static $cachePath = '';

    if ($path !== null) {
        $cachePath = $path;
    }

    return $cachePath;
}

/**
 * @param mixed $default
 *
 * @return mixed
 */
function get(string $key, $default = null)
{
    if (!has($key)) {
        return $default;
    }

    $filename = \sprintf('%s/%s.cache', path(), \md5($key));
    $data = @\file_get_contents($filename);
    @[$value, $ttl] = @\unserialize($data);

    if (\is_numeric($ttl)) {
        if ($ttl != 0 && \time() >= $ttl) {
            remove($key);
            return $default;
        } else {
            return $value;
        }
    }

    return $default;
}

/**
 * @param mixed $value
 * @param string|int $ttl
 */
function set(string $key, $value, $ttl = 0): void
{
    $time = \time();
    $filename = \sprintf('%s/%s.cache', path(), \md5($key));

    if (\is_string($ttl)) {
        $ttl = strtotime($ttl);
    }

    if ($ttl !== 0 && $ttl < $time) {
        $ttl += $time;
    }

    $ttl = ($ttl === 0 || $ttl === false) ? 0 : $ttl;

    @\file_put_contents($filename, @\serialize([$value, $ttl]));
}

function remove(string $key): void
{
    @\unlink(\sprintf('%s/%s.cache', path(), \md5($key)));
}

function has(string $key): bool
{
    return \is_file(\sprintf('%s/%s.cache', path(), \md5($key)));
}

function flush(): void
{
    foreach (\glob(path() . '/*.cache') as $file) {
        @\unlink($file);
    }
}

/**
 * @param string|int $ttl
 */
function remember(string $key, Closure $callback, $ttl = 0)
{
    if (has($key)) {
        return get($key);
    }

    set($key, $callback(), $ttl);
    return $callback();
}
