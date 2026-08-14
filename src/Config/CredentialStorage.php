<?php

namespace VarizPay\Config;

/**
 * Persists VarizPay credentials so they can be saved once and loaded later.
 *
 * Implementations MUST store secrets encrypted at rest. The SDK ships
 * {@see EncryptedFileStorage} (AES-256-CBC + HMAC to a local file).
 */
interface CredentialStorage
{
    /**
     * Save credentials.
     *
     * @param string $apiKey
     * @param string $bankId
     * @param array $extra Additional values to persist alongside the secrets.
     * @return void
     */
    public function save($apiKey, $bankId, array $extra = []);

    /**
     * Load saved credentials.
     *
     * @return array|null Map containing at least `api_key` and `bank_id`, or
     *                    null when nothing has been saved yet.
     */
    public function load();

    /**
     * Remove saved credentials.
     *
     * @return void
     */
    public function clear();
}
