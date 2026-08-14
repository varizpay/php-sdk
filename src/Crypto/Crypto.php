<?php

namespace VarizPay\Crypto;

use VarizPay\Exception\ConfigurationException;

/**
 * Symmetric encryption for stored credentials.
 *
 * Uses AES-256-CBC with a SHA-256 derived key, a random 128-bit IV, and an
 * HMAC-SHA256 authenticator over (IV || ciphertext) so stored values are both
 * confidential and tamper-evident.
 *
 * The sealed envelope returned by {@see encrypt()} is a single base64 string:
 *
 *     base64( iv(16 bytes) || hmac(32 bytes) || ciphertext )
 */
final class Crypto
{
    public const CIPHER = 'aes-256-cbc';

    /**
     * Derive a 32-byte AES key from any user supplied key material.
     *
     * @param string $key
     * @return string
     */
    public static function deriveKey($key)
    {
        return hash('sha256', $key, true);
    }

    /**
     * Encrypt a value into a sealed, tamper-evident envelope.
     *
     * @param string $plaintext
     * @param string $key User supplied key material (any length).
     * @return string
     */
    public static function encrypt($plaintext, $key)
    {
        $derived = self::deriveKey($key);
        $iv      = random_bytes(16);

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $derived,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new ConfigurationException('Failed to encrypt credentials: ' . openssl_error_string());
        }

        $mac = hash_hmac('sha256', $iv . $ciphertext, $derived, true);

        return base64_encode($iv . $mac . $ciphertext);
    }

    /**
     * Decrypt a sealed envelope, verifying its authenticity first.
     *
     * @param string $envelope
     * @param string $key
     * @return string
     * @throws ConfigurationException When the envelope is malformed or the key is wrong.
     */
    public static function decrypt($envelope, $key)
    {
        $derived = self::deriveKey($key);
        $raw     = base64_decode($envelope, true);

        if ($raw === false || strlen($raw) < 16 + 32) {
            throw new ConfigurationException('Stored credentials are corrupt or use an unsupported format.');
        }

        $iv         = substr($raw, 0, 16);
        $mac        = substr($raw, 16, 32);
        $ciphertext = substr($raw, 16 + 32);

        $expected = hash_hmac('sha256', $iv . $ciphertext, $derived, true);
        if (!hash_equals($expected, $mac)) {
            throw new ConfigurationException('Stored credentials failed authentication. Is VARIZPAY_STORAGE_KEY correct?');
        }

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $derived, OPENSSL_RAW_DATA, $iv);
        if ($plaintext === false) {
            throw new ConfigurationException('Failed to decrypt credentials: ' . openssl_error_string());
        }

        return $plaintext;
    }
}
