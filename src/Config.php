<?php

declare(strict_types=1);

namespace Config
{
    use Helper;

    /**
     * Sets the configuration value
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Config\set('foo.bar', 'baz');
     * ```
     */
    function set(string $key, mixed $value): void
    {
        global $mikro;

        $mikro[COLLECTION] = Helper\arr($mikro[COLLECTION] ?? [])->put($key, $value)->all();
    }

    /**
     * Returns the configuration value
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Config\get('foo.bar');
     * Config\get('foo')['bar'];
     * ```
     */
    function get(string $key, mixed $default = null): mixed
    {
        global $mikro;

        return Helper\arr($mikro[COLLECTION] ?? [])->get($key, $default);
    }

    /**
     * Config collection constant
     *
     * @internal
     */
    const COLLECTION = 'Config\COLLECTION';
}
