<?php

declare(strict_types=1);

namespace Cache
{
    use Mikro\Exceptions\{PathException, MikroException};

    /**
     * Get cache path
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Cache\PATH] = 'path/to/app/storage/cache';
     * Cache\path(); // 'path/to/app/storage/cache'
     * ```
     *
     * @throws MikroException Throws an exception if 'Cache\PATH' is not defined in the global $micro array
     */
    function path(?string $key = null): string
    {
        global $mikro;

        if (! isset($mikro[PATH])) {
            throw new MikroException('Please set the cache path');
        }

        return $key === null ?
            $mikro[PATH] : \sprintf('%s/%s.cache', $mikro[PATH], \md5($key));
    }

    /**
     * Returns a defined cache item
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Cache\get('items');
     * ```
     */
    function get(string $key): mixed
    {
        if (! has($key)) {
            return null;
        }

        return \unserialize(\file_get_contents(path($key)));
    }

    /**
     * Defines a new cache item
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Cache\set('items', DB\table('items')->get());
     * ```
     *
     * @throws PathException if cache path is not writeable
     */
    function set(string $key, mixed $data): void
    {
        if (! \is_writable($dirname = \dirname(path($key)))) {
            throw new PathException(\sprintf('Cache path not writable: %s', $dirname));
        }

        \file_put_contents(path($key), \serialize($data));
    }

    /**
     * Checks whether the Cache item is defined
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Cache\has('items'); // true or false
     * ```
     */
    function has(string $key): bool
    {
        return \is_readable(path($key));
    }

    /**
     * Deletes a defined cache item from the file system

     * {@inheritDoc} **Example:**
     * ```php
     * Cache\remove('items');
     * ```
     */
    function remove(string $key): void
    {
        if (! has($key) || ! \is_writable(path())) {
            return;
        }

        \unlink(path($key));
    }

    /**
     * Deletes all defined cache items from the file system
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Cache\flush();
     * ```
     *
     * @throws PathException if cache path is not writable
     */
    function flush(): void
    {
        if (! \is_writable(path())) {
            throw new PathException(\sprintf('Cache path not writable: %s', path()));
        }

        foreach (\glob(path() . '/*.cache') as $file) {
            \unlink($file);
        }
    }

    /**
     * Cache path constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Cache\PATH] = '/path/to/cache';
     * ```
     */
    const PATH = 'Cache\PATH';
};
