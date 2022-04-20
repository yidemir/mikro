<?php

declare(strict_types=1);

namespace Crypt
{
    use Mikro\Exceptions\MikroException;

    /**
     * Get defined secret key
     *
      * {@inheritDoc} **Example:**
     * ```php
     * $mikro['Crypt\SECRET'] = 'foo';
     * Crypt\secret(); // 'foo'
     * ```
     *
     * @throws MikroException If secret key not defined on global $mikro array
     */
    function secret(): string
    {
        global $mikro;

        if (! \extension_loaded('openssl')) {
            throw new MikroException('Openssl extension is not available, please install');
        }

        if (! isset($mikro[SECRET])) {
            throw new MikroException('Please define secret key');
        }

        return $mikro[SECRET];
    }

    /**
     * Encrypts the specified string
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Crypt\encrypt('secret data'); // 'secretstring'
     * ```
     */
    function encrypt(string $data, ?string $secret = null): string
    {
        $decodedKey = \base64_decode($secret ?? secret());
        $length = \openssl_cipher_iv_length('AES-256-CBC');
        $iv = \base64_encode(\openssl_random_pseudo_bytes($length));
        $iv = \substr($iv, 0, $length);
        $encryptedData = \openssl_encrypt($data, 'AES-256-CBC', $decodedKey, 0, $iv);

        return \base64_encode($encryptedData . '::' . $iv);
    }

    /**
     * Decrypts the specified string
     *
      * {@inheritDoc} **Example:**
     * ```php
     * Crypt\decrypt('secretstring'); // 'secret data'
     * ```
     */
    function decrypt(string $data, ?string $secret = null): bool|string|null
    {
        $decodedKey = \base64_decode($secret ?? secret());
        $explode = \explode('::', \base64_decode($data), 2);

        if (\count($explode) !== 2) {
            return null;
        }

        [$encryptedData, $iv] = $explode;
        $length = \openssl_cipher_iv_length('AES-256-CBC');
        $iv = \substr($iv, 0, $length);

        return \openssl_decrypt($encryptedData, 'AES-256-CBC', $decodedKey, 0, $iv);
    }

    /**
     * Bcrypt password string
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Crypt\bcrypt('password');
     * ```
     */
    function bcrypt(string $data): string
    {
        return \password_hash($data, \PASSWORD_BCRYPT);
    }

    /**
     * Crypt secret constant
     *
      * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Crypt\SECRET] = 'secretstring';
     * ```
     */
    const SECRET = 'Crypt\SECRET';
}
