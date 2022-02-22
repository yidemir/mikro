<?php

declare(strict_types=1);

namespace Config
{
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

        $replace = \array_reduce(
            \array_reverse(\explode('.', $key)),
            fn($value, $key) => [$key => $value],
            $value
        );

        $mikro[COLLECTION] = \array_replace_recursive($mikro[COLLECTION] ?? [], $replace);
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

        if (\array_key_exists($key, $mikro[COLLECTION] ?? [])) {
            return $mikro[COLLECTION][$key];
        }

        return \array_reduce(
            \explode('.', $key),
            fn($config, $key) => $config[$key] ?? $default,
            $mikro[COLLECTION] ?? []
        ) ?? $default;
    }

    /**
     * Config collection constant
     *
     * @internal
     */
    const COLLECTION = 'Config\COLLECTION';
};
