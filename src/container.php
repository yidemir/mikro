<?php
declare(strict_types=1);

namespace container;

use Closure;
use Exception;

function collection(array $items = []): array
{
    static $collection;

    if ($collection === null) {
        $collection = [];
    }

    if ($items !== []) {
        $collection = \array_merge($collection, $items);
    }

    return $collection;
}

function has(string $name): bool
{
    return \array_key_exists($name, collection());
}

function get(string $name, array $args = [])
{
    $items = collection();

    if (\array_key_exists($name, $items)) {
        if ($items[$name] instanceof Closure) {
            return \call_user_func_array($items[$name], $args);
        } else {
            return $items[$name];
        }
    }

    throw new Exception(\sprintf('Container item not exists: "%s"', $name));
}

/**
 * @param mixed $data
 */
function set(string $name, $data)
{
    collection([$name => $data]);
}

function singleton(string $name, Closure $callback)
{
    collection([$name => function() use ($callback) {
        static $object;

        if ($object === null) {
            $object = $callback();
        }

        return $object;
    }]);
}
