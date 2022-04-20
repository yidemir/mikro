<?php

declare(strict_types=1);

namespace Locale
{
    use Mikro\Exceptions\MikroException;

    /**
     * Gets localization string
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Locale\t('Hello world'); // Merhaba Dünya
     * Locale\t('Hello all!'); // Hello all!
     * ```
     * `tr.php`:
     * ```php
     * <?php return [
     *     'Hello world' => 'Merhaba Dünya'
     * ];
     * ```
     */
    function t(string $phrase): string
    {
        return data()[$phrase] ?? $phrase;
    }

    /**
     * Sets current locale
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Locale\set('tr');
     * ```
     */
    function set(string $locale): void
    {
        global $mikro;

        $mikro[CURRENT] = $locale;
    }

    /**
     * Gets current locale
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Locale\get(); // tr
     * ```
     */
    function get(): string
    {
        global $mikro;

        return $mikro[CURRENT] ?? ($mikro[FALLBACK] ?? 'en');
    }

    /**
     * Gets current localization data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Locale\data(); // array
     * ```
     *
     * @throw MikroException If not have mikro locale path
     */
    function data(): array
    {
        global $mikro;

        if (! isset($mikro[DATA][get()])) {
            if (! isset($mikro[PATH])) {
                throw new MikroException('Please specify the locale path');
            }

            $path = $mikro[PATH] . \DIRECTORY_SEPARATOR . get() . '.php';
            $mikro[DATA][get()] = \is_file($path) ? (array) require($path) : [];
        }

        return $mikro[DATA][get()];
    }

    /**
     * Localization files path constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Locale\PATH] = __DIR__ . '/languages';
     * ```
     */
    const PATH = 'Locale\PATH';

    /**
     * Current language constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Locale\CURRENT] = 'tr';
     * ```
     */
    const CURRENT = 'Locale\CURRENT';

    /**
     * Fallback language constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Locale\FALLBACK] = 'en';
     * ```
     */
    const FALLBACK = 'Locale\FALLBACK';

    /**
     * @internal
     */
    const DATA = 'Locale\DATA';
}
