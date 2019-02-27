<?php

namespace crypt;

function secret(?string $secret = null): string
{
    static $cryptSecret = '';

    if ($secret !== null) $cryptSecret = $secret;

    return $cryptSecret;
}

function encrypt(string $data): string
{
    $decodedKey = base64_decode(secret());
    $length = openssl_cipher_iv_length('AES-256-CBC');
    $iv = base64_encode(openssl_random_pseudo_bytes($length));
    $iv = substr($iv, 0,  $length);
    $encryptedData = openssl_encrypt($data, 'AES-256-CBC', $decodedKey, 0, $iv);
    return base64_encode($encryptedData . '::' . $iv);
}

function decrypt(string $data)
{
    $decodedKey = base64_decode(secret());
    $explode = explode('::', base64_decode($data), 2);
    if (count($explode) !== 2) return '';
    [$encryptedData, $iv] = $explode;
    $length = openssl_cipher_iv_length('AES-256-CBC');
    $iv = substr($iv, 0, $length);
    return openssl_decrypt($encryptedData, 'AES-256-CBC', $decodedKey, 0, $iv);
}
