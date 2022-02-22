<?php

declare(strict_types=1);

namespace Jwt
{
    use const Crypt\SECRET as CRYPT_SECRET;

    /**
     * Get defined secret key
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Jwt\SECRET] = 'foo';
     * Jwt\secret(); // 'foo'
     * ```
     *
     * @throws \Exception If secret key not defined on global $mikro array
     */
    function secret(): string
    {
        global $mikro;

        $secret = $mikro[SECRET] ?? $mikro[CRYPT_SECRET] ?? null;

        if ($secret === null) {
            throw new \Exception('Secret key not defined');
        }

        return $secret;
    }

    /**
     * Create Jwt token with UID, expiration and issuer
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Jwt\create('username or id', time() + 60, 'issuer name'); // Jwt token string
     * ```
     */
    function create(int|string $uid, int $expiration, string $issuer, ?int $iat = null): string
    {
        return encode([
            'uid' => $uid,
            'exp' => $expiration,
            'iss' => $issuer,
            'iat' => $iat ?? \time()
        ]);
    }

    /**
     * Decode Jwt token
     *
     * {@inheritDoc} **Example:**
     * ```php
     * try {
     *     $object = Jwt\decode('Jwt token');
     *     $uid = $object->uid;
     * } catch (Exception $e) {
     *     echo 'Jwt token invalid';
     * }
     *
     * $object = Jwt\decode('Jwt token', false);
     * ```
     *
     * @throws \Exception If invalid Jwt token string
     * @throws \Exception If invalid segments encoding
     * @throws \Exception If empty algorithm
     * @throws \Exception If signature verification fail
     */
    function decode(string $jwt, bool $verify = true): object
    {
        $pieces = \explode('.', $jwt);

        if (\count($pieces) !== 3) {
            throw new \Exception('Wrong number of segments');
        }

        [$headerB64, $payloadB64, $cryptoB64] = $pieces;

        $header = \json_decode(url_safe_base64_decode($headerB64), false, 512, \JSON_THROW_ON_ERROR);
        $payload = \json_decode(url_safe_base64_decode($payloadB64), false, 512, \JSON_THROW_ON_ERROR);

        if (empty(\get_object_vars($header))) {
            throw new \Exception('Invalid segment encoding');
        }

        if (empty(\get_object_vars($payload))) {
            throw new \Exception('Invalid segment encoding');
        }

        $sign = url_safe_base64_decode($cryptoB64);

        if ($verify) {
            if (! isset($header->alg) || empty($header->alg)) {
                throw new \Exception('Empty algorithm');
            }

            if ($sign != sign("$headerB64.$payloadB64", $header->alg)) {
                throw new \Exception('Signature verification failed');
            }
        }

        return $payload;
    }

    /**
     * Creates Jwt token with object/array
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Jwt\encode(['uid' => 1, 'iat' => time(), 'exp' => time() + 60, 'iss' => 'foo']);
     * ```
     */
    function encode(object|array $payload, string $algo = 'HS256'): string
    {
        $header = ['typ' => 'JWT', 'alg' => $algo];
        $segments = [];
        $segments[] = url_safe_base64_encode(\json_encode($header, \JSON_THROW_ON_ERROR));
        $segments[] = url_safe_base64_encode(\json_encode($payload, \JSON_THROW_ON_ERROR));
        $input = \implode('.', $segments);
        $signature = sign($input, $algo);
        $segments[] = url_safe_base64_encode($signature);

        return \implode('.', $segments);
    }

    /**
     * Signs Jwt payload
     *
     * @internal
     * @throws \Exception If algorithm not supported
     */
    function sign(string $message, string $method = 'HS256'): string
    {
        $methods = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ];

        if (! isset($methods[$method])) {
            throw new \Exception('Algorithm not supported');
        }

        return \hash_hmac($methods[$method], $message, secret(), true);
    }

    /**
     * @internal
     */
    function url_safe_base64_decode(string $input): string
    {
        $remainder = \strlen($input) % 4;

        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= \str_repeat('=', $padlen);
        }

        return \base64_decode(\strtr($input, '-_', '+/'));
    }

    /**
     * @internal
     */
    function url_safe_base64_encode(string $input): string
    {
        return \str_replace('=', '', \strtr(\base64_encode($input), '+/', '-_'));
    }

    /**
     * Validates token
     *
     * {@inheritDoc} **Example:**
     * ```php
     * if (Jwt\validate('token')) {
     *     echo 'It\'s ok!';
     * }
     * ```
     *
     * @throws \Exception If signature verification fail or invalid token
     */
    function validate(string $token): bool
    {
        try {
            decode($token);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check token is expired
     *
     * {@inheritDoc} **Example:**
     * ```php
     * if (! Jwt\expired('token')) {
     *     echo 'It\'s ok!';
     * }
     * ```
     *
     * @throws \Exception If signature verification fail or invalid token
     */
    function expired(string $token): bool
    {
        if (! validate($token)) {
            return false;
        }

        $payload = decode($token);

        if (! \property_exists($payload, 'exp')) {
            return false;
        }

        return $payload->exp < \time();
    }

    /**
     * Jwt secret constant
     *
     * {@inheritDoc} **Example:**
     * ```php
     * $mikro[Jwt\SECRET] = 'secretstring';
     * ```
     */
    const SECRET = 'Jwt\SECRET';
};
