<?php

declare(strict_types=1);

namespace Cache
{
    use Container;
    use Mikro\Exceptions\{PathException, MikroException};

    /**
     * Get defined cache path
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Cache\PATH] = 'path/to/app/storage/cache'; // define mikro config
     * Cache\path(); // 'path/to/app/storage/cache'
     * ```
     *
     * @throws MikroException Throws an exception if 'Cache\PATH' is not defined in the global $micro array
     */
    function path(?string $key = null): string
    {
        global $mikro;

        if (! isset($mikro[PATH])) {
            throw new MikroException('Please set the cache path first');
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
        if ($memcached = memcached()) {
            $data = $memcached->get($key);

            return \Memcached::RES_NOTFOUND === $memcached->getResultCode() ?
                null : $data;
        }

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
     * Cache\set('items', $itemsData);
     * ```
     *
     * @throws PathException if cache path is not writeable
     */
    function set(string $key, mixed $data, int $ttl = 0): void
    {
        if ($memcached = memcached()) {
            $memcached->set($key, $data, $ttl);

            return;
        }

        if (! \is_writable($dirname = \dirname(path($key)))) {
            throw new PathException(\sprintf('Cache path not writable: %s', $dirname));
        }

        \file_put_contents(path($key), \serialize($data));
    }

    /**
     * Checks whether the cache item is defined
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Cache\has('items'); // returns bool
     * ```
     */
    function has(string $key): bool
    {
        if ($memcached = memcached()) {
            $memcached->get($key);

            return \Memcached::RES_NOTFOUND !== $memcached->getResultCode();
        }

        return \is_readable(path($key));
    }

    /**
     * Deletes a defined cache item

     * {@inheritDoc} **Example:**
     * ```php
     * Cache\remove('items');
     * ```
     */
    function remove(string $key): void
    {
        if ($memcached = memcached()) {
            $memcached->delete($key);

            return;
        }

        if (! \is_writable(path())) {
            throw new PathException(\sprintf('Cache path not writable: %s', path()));
        }

        if (! has($key)) {
            return;
        }

        \unlink(path($key));
    }

    /**
     * Deletes all defined cache items
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
        if ($memcached = memcached()) {
            $memcached->flush();

            return;
        }

        if (! \is_writable(path())) {
            throw new PathException(\sprintf('Cache path not writable: %s', path()));
        }

        foreach (\glob(path() . '/*.cache') as $file) {
            \unlink($file);
        }
    }

    /**
     * Returns memcached instance before checks
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Cache\DRIVER] = 'memcached';
     * $memcached = new Memcached();
     * $memcached->addServer('localhost', 11211);
     * Container\set(Memcached::class, $memcached);
     *
     * Cache\memcached(); // Memcached instance
     * ```
     *
     * @throws MikroException if memcached not loaded
     */
    function memcached(): \Memcached|bool
    {
        global $mikro;

        $driver = $mikro[DRIVER] ?? null;

        if ($driver !== 'memcached') {
            return false;
        }

        if (! \extension_loaded('memcached')) {
            throw new MikroException('Memcached extension is not available, please install');
        }

        if (! Container\has(\Memcached::class)) {
            throw new MikroException(
                'In order to use the Memcached driver, you must define the Memcache driver in the Container'
            );
        }

        return Container\get(\Memcached::class);
    }

    /**
     * Remember cache with callback
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $data = Cache\remember('posts', fn() => DB\query('...')->fetchAll(), 60);
     * ```
     */
    function remember(string $key, \Closure $callback, int $ttl = 0): mixed
    {
        if (has($key)) {
            return get($key);
        } else {
            set($key, $cache = $callback(), $ttl);

            return $cache;
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

    /**
     * Cache path constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Cache\DRIVER] = 'file';
     * // default driver is 'file'
     * // available drivers: file, memcached
     * ```
     */
    const DRIVER = 'Cache\DRIVER';
}
