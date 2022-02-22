<?php

declare(strict_types=1);

namespace Logger
{
    /**
     * Get logger path
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro['Logger\PATH'] = 'path/to/app/storage/logs';
     * Logger\path(); // 'path/to/app/storage/logs'
     * ```
     *
     * @throws \Exception Throws an exception if 'Logger\PATH' is not defined in the global $micro array
     */
    function path(): string
    {
        global $mikro;

        if (! isset($mikro[PATH])) {
            throw new \Exception('Please set the logger path');
        }

        return $mikro[PATH];
    }

    /**
     * Create log file
     *
     * @internal
     * @throws \Exception If log path not writable
     */
    function create_file(): string
    {
        global $mikro;

        switch ($mikro[TYPE] ?? 'single') {
            case 'daily':
                $path = path() . '/mikro-' . date('Y-m-d') . '.log';
                break;

            case 'single':
            default:
                $path = path() . '/mikro.log';
                break;
        }

        if (! \is_file($path)) {
            if (! \touch($path)) {
                throw new \Exception('The log file could not be created');
            }
        }

        if (! \is_writable($path)) {
            throw new \Exception('The log file could not writable');
        }

        return $path;
    }

    /**
     * Writes to log file
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Logger\log(Logger\LEVEL_DEBUG, 'debug message', ['debug data']);
     * ```
     *
     * @throws \Exception If log path not writable
     */
    function log(string $level, string $message, array|object|null $context = null): void
    {
        \file_put_contents(create_file(), format($level, $message, $context), \FILE_APPEND | \LOCK_EX);
    }

    /**
     * @internal
     */
    function format(string $level, string $message, array|object|null $context = null): string
    {
        $context = $context ? \json_encode($context) : '';

        return '[' . \date('Y-m-d H:i:s') . '] ' . \mb_strtoupper($level) . ': ' . $message . ' ' . $context . \PHP_EOL;
    }

    /**
     * Writes to log file with 'emergency' level
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Logger\emergency('log message', ['log' => 'data']);
     * ```
     *
     * @throws \Exception If log path not writable
     */
    function emergency(string $message, array|object|null $context = null): void
    {
        log(LEVEL_EMERGENCY, $message, $context);
    }

    /**
     * Writes to log file with 'alert' level
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Logger\alert('log message', ['log' => 'data']);
     * ```
     *
     * @throws \Exception If log path not writable
     */
    function alert(string $message, array|object|null $context = null): void
    {
        log(LEVEL_ALERT, $message, $context);
    }

    /**
     * Writes to log file with 'critical' level
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Logger\critical('log message', ['log' => 'data']);
     * ```
     *
     * @throws \Exception If log path not writable
     */
    function critical(string $message, array|object|null $context = null): void
    {
        log(LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Writes to log file with 'error' level
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Logger\error('log message', ['log' => 'data']);
     * ```
     *
     * @throws \Exception If log path not writable
     */
    function error(string $message, array|object|null $context = null): void
    {
        log(LEVEL_ERROR, $message, $context);
    }

    /**
     * Writes to log file with 'warning' level
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Logger\warning('log message', ['log' => 'data']);
     * ```
     *
     * @throws \Exception If log path not writable
     */
    function warning(string $message, array|object|null $context = null): void
    {
        log(LEVEL_WARNING, $message, $context);
    }

    /**
     * Writes to log file with 'notice' level
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Logger\notice('log message', ['log' => 'data']);
     * ```
     *
     * @throws \Exception If log path not writable
     */
    function notice(string $message, array|object|null $context = null): void
    {
        log(LEVEL_NOTICE, $message, $context);
    }

    /**
     * Writes to log file with 'info' level
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Logger\info('log message', ['log' => 'data']);
     * ```
     *
     * @throws \Exception If log path not writable
     */
    function info(string $message, array|object|null $context = null): void
    {
        log(LEVEL_INFO, $message, $context);
    }

    /**
     * Writes to log file with 'debug' level
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Logger\debug('log message', ['log' => 'data']);
     * ```
     *
     * @throws \Exception If log path not writable
     */
    function debug(string $message, array|object|null $context = null): void
    {
        log(LEVEL_DEBUG, $message, $context);
    }

    /**
     * Constant of emergency level
     */
    const LEVEL_EMERGENCY = 'emergency';

    /**
     * Constant of alert level
     */
    const LEVEL_ALERT = 'alert';

    /**
     * Constant of critical level
     */
    const LEVEL_CRITICAL = 'critical';

    /**
     * Constant of error level
     */
    const LEVEL_ERROR = 'error';

    /**
     * Constant of warning level
     */
    const LEVEL_WARNING = 'warning';

    /**
     * Constant of notice level
     */
    const LEVEL_NOTICE = 'notice';

    /**
     * Constant of info level
     */
    const LEVEL_INFO = 'info';

    /**
     * Constant of debug level
     */
    const LEVEL_DEBUG = 'debug';

    /**
     * Logger path constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Logger\PATH] = '/path/to/logs';
     * ```
     */
    const PATH = 'Logger\PATH';

    /**
     * Logger type constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Logger\TYPE] = 'daily';
     * $mikro[Logger\TYPE] = 'single';
     * ```
     */
    const TYPE = 'Logger\TYPE';
};
