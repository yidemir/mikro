<?php

declare(strict_types=1);

namespace Flash
{
    use Mikro\Exceptions\MikroException;

    /**
     * Creates a flash message
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Flash\set(Flash\TYPE_INFO, 'Info message');
     * Flash\set(Flash\TYPE_SUCCESS, 'Success message');
     * Flash\set(Flash\TYPE_ERROR, 'Error message');
     * ```
     */
    function set(string $type, string $message): array
    {
        if (\session_status() !== \PHP_SESSION_ACTIVE) {
            throw new MikroException('Start the PHP Session first');
        }

        $messages = $_SESSION['__flash_' . $type] ?? [];
        $messages[] = $message;

        return $_SESSION['__flash_' . $type] = $messages;
    }

    /**
     * Gets flash message
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Flash\get(Flash\TYPE_INFO); // Info messages array
     * Flash\get(Flash\TYPE_ERROR); // Error messages array
     * ```
     */
    function get(string $type): ?array
    {
        if (\session_status() !== \PHP_SESSION_ACTIVE) {
            throw new MikroException('Start the PHP Session first');
        }

        $messages = $_SESSION['__flash_' . $type] ?? null;
        unset($_SESSION['__flash_' . $type]);

        return $messages;
    }

    /**
     * Creates a error message
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Flash\error('Error message');
     * Flash\error('Error message 2');
     * ```
     */
    function error(string $message): array
    {
        return set(TYPE_ERROR, $message);
    }

    /**
     * Creates a info message
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Flash\info('Info message');
     * Flash\info('Info message 2');
     * ```
     */
    function info(string $message): array
    {
        return set(TYPE_INFO, $message);
    }

    /**
     * Creates a success message
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Flash\success('Success message');
     * Flash\success('Success message 2');
     * ```
     */
    function success(string $message): array
    {
        return set(TYPE_SUCCESS, $message);
    }

    /**
     * Creates a warning message
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Flash\warning('Warning message');
     * Flash\warning('Warning message 2');
     * ```
     */
    function warning(string $message): array
    {
        return set(TYPE_WARNING, $message);
    }

    /**
     * Constant of error type
     */
    const TYPE_ERROR = 'error';

    /**
     * Constant of info type
     */
    const TYPE_INFO = 'info';

    /**
     * Constant of success type
     */
    const TYPE_SUCCESS = 'success';

    /**
     * Constant of warning type
     */
    const TYPE_WARNING = 'warning';
};
