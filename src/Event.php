<?php

declare(strict_types=1);

namespace Event
{
    /**
     * Executes events to be triggered
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Event\listen('order.created', fn($order) => Logger\debug('Order created', $order));
     * Event\listen('order.created', function ($order) {
     *     if ($order->status === 'foo') {
     *         //
     *     }
     * });
     * ```
     */
    function listen(string $name, callable $callback): void
    {
        global $mikro;

        $mikro[COLLECTION][$name][] = $callback;
    }

    /**
     * Emits events
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Event\emit('order.created', [$order]);
     * ```
     */
    function emit(string $name, array $arguments = []): void
    {
        global $mikro;

        if (! \array_key_exists($name, $mikro[COLLECTION] ?? [])) {
            return;
        }

        foreach ($mikro[COLLECTION][$name] as $event) {
            if (\is_callable($event)) {
                \call_user_func_array($event, $arguments);
            }
        }
    }

    /**
     * Sync listeners with files. Files must be returns callable/Closure
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Event\sync(__DIR__ . '/app/events');
     * ```
     */
    function sync(string $path): void
    {
        foreach (\glob("{$path}/*.php") as $file) {
            if (\is_callable($event = require_once($file))) {
                listen(\basename($file, '.php'), $event);
            }
        }
    }

    /**
     * Event collection constant
     *
     * @internal
     */
    const COLLECTION = 'Event\COLLECTION';
};
