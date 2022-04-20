<?php

declare(strict_types=1);

namespace Container
{
    use Mikro\Exceptions\ContainerException;

    /**
     * Defines a new container item
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Container\set('variable', 'string');
     * Container\set('closure', fn() => new stdClass())
     * ```
     */
    function set(string $name, mixed $value): void
    {
        global $mikro;

        $mikro[COLLECTION][$name] = $value;
    }

    /**
     * Returns the defined container item
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Container\get('variable'); // 'string'
     * Container\get('closure'); // Closure
     * ```
     */
    function get(string $name): mixed
    {
        if (! has($name)) {
            throw new ContainerException('Container item not exists');
        }

        global $mikro;

        return $mikro[COLLECTION][$name];
    }

    /**
     * Returns the value of the defined container item
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Container\value('closure'); // stdClass
     * ```
     *
     * @throws ContainerException If the value is not callable
     */
    function value(string $name, array $args = []): mixed
    {
        if (! \is_callable($value = get($name))) {
            throw new ContainerException('Value is not callable');
        }

        return \call_user_func_array($value, $args);
    }

    /**
     * Defines a new container item with singleton pattern
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Container\set('closure', fn() => new stdClass());
     * Container\value('closure') === Container\value('closure'); // true
     * ```
     */
    function singleton(string $name, callable $callback): void
    {
        set($name, function () use ($callback) {
            static $object;

            if ($object === null) {
                $object = $callback();
            }

            return $object;
        });
    }

    /**
     * Checks whether the container item is defined
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Container\has('item'); // true or false
     * ```
     */
    function has(string $name): bool
    {
        global $mikro;

        return \array_key_exists($name, $mikro[COLLECTION] ?? []);
    }

    /**
     * Container collection constant
     *
     * @internal
     */
    const COLLECTION = 'Container\COLLECTION';
}
