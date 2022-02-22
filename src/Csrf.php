<?php

declare(strict_types=1);

namespace Csrf
{
    use function Html\tag;
    use function Request\get as input;

    /**
     * Generate a random string
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Csrf\generate_random();
     * ```
     */
    function generate_random(int $strength): string
    {
        if (\extension_loaded('openssl')) {
            return \hash('sha512', \openssl_random_pseudo_bytes($strength));
        }

        return \hash('sha512', \random_bytes($strength));
    }

    /**
     * Validates CSRF token
     *
     * {@inheritDoc} **Example:**
     * ```php
     * if (! Csrf\validate(Request\get('__CSRF_TOKEN'))) {
     *     throw new Exception('CSRF token does not match');
     * }
     * ```
     */
    function validate(?string $value = null): bool
    {
        if (! isset($_SESSION['__csrf'])) {
            return false;
        }

        if ($value === null) {
            $value = input('__CSRF_TOKEN');
        }

        return \hash_equals($value, $_SESSION['__csrf']);
    }

    /**
     * Generate CSRF token
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $token = Csrf\get();
     * ```
     */
    function get(): string
    {
        if (\session_status() !== \PHP_SESSION_ACTIVE) {
            throw new \Exception('Start the PHP Session first');
        }

        if (isset($_SESSION['__csrf'])) {
            return $_SESSION['__csrf'];
        }

        return $_SESSION['__csrf'] = generate_random(32);
    }

    /**
     * Generate CSRF input in HTML
     *
     * {@inheritDoc} **Example:**
     * ```php
     * echo Csrf\field();
     * ```
     */
    function field(): string
    {
        return (string) tag('input', '')
            ->name('__CSRF_TOKEN')
            ->type('hidden')
            ->value(get());
    }
};
