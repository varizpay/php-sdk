<?php

namespace VarizPay\Config;

use VarizPay\Exception\ConfigurationException;

/**
 * Immutable configuration for a VarizPay client.
 *
 * API Key and Bank ID (bank_id) are sent to the VarizPay API as the
 * `X-API-Key` header and the `bank_account_id` request field respectively.
 *
 * Credentials can come from environment variables, an encrypted credentials
 * file (see {@see EncryptedFileStorage}), or both via {@see resolve()}.
 */
final class Config
{
    public const DEFAULT_BASE_URL = 'https://varizpay.com/api';

    /** @var string */
    private $apiKey;

    /** @var string */
    private $bankId;

    /** @var string */
    private $baseUrl;

    /** @var int */
    private $timeout;

    public function __construct($apiKey, $bankId, $baseUrl = self::DEFAULT_BASE_URL, $timeout = 30)
    {
        $this->apiKey  = (string) $apiKey;
        $this->bankId  = (string) $bankId;
        $this->baseUrl = rtrim((string) $baseUrl, '/');
        $this->timeout = max(1, (int) $timeout);
    }

    /**
     * Build a Config from environment variables.
     *
     * Recognised variables:
     *  - VARIZPAY_API_KEY
     *  - VARIZPAY_BANK_ID
     *  - VARIZPAY_BASE_URL   (default https://varizpay.com/api)
     *  - VARIZPAY_TIMEOUT    (default 30)
     *
     * @param array|null $env Alternative env map (for testing). Defaults to getenv().
     * @return self
     */
    public static function fromEnvironment($env = null)
    {
        $env = $env !== null ? $env : getenv();

        return new self(
            isset($env['VARIZPAY_API_KEY']) ? $env['VARIZPAY_API_KEY'] : '',
            isset($env['VARIZPAY_BANK_ID']) ? $env['VARIZPAY_BANK_ID'] : '',
            isset($env['VARIZPAY_BASE_URL']) ? $env['VARIZPAY_BASE_URL'] : self::DEFAULT_BASE_URL,
            isset($env['VARIZPAY_TIMEOUT']) ? (int) $env['VARIZPAY_TIMEOUT'] : 30
        );
    }

    /**
     * Resolve configuration by combining the environment with an encrypted
     * credentials store. Environment values take precedence; missing ones are
     * filled from the store.
     *
     * @param array|null $env
     * @param CredentialStorage|null $storage
     * @return self
     */
    public static function resolve($env = null, $storage = null)
    {
        $config = self::fromEnvironment($env);

        $apiKey = $config->apiKey;
        $bankId = $config->bankId;

        if (($apiKey === '' || $bankId === '') && $storage !== null) {
            $saved = $storage->load();
            if ($saved !== null) {
                if ($apiKey === '' && isset($saved['api_key'])) {
                    $apiKey = (string) $saved['api_key'];
                }
                if ($bankId === '' && isset($saved['bank_id'])) {
                    $bankId = (string) $saved['bank_id'];
                }
            }
        }

        return new self($apiKey, $bankId, $config->baseUrl, $config->timeout);
    }

    /**
     * @return string
     */
    public function apiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function bankId()
    {
        return $this->bankId;
    }

    /**
     * @return string
     */
    public function baseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return int
     */
    public function timeout()
    {
        return $this->timeout;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return $this->apiKey !== '' && $this->bankId !== '';
    }

    /**
     * Throw if the API Key or Bank ID is missing.
     *
     * @return void
     * @throws ConfigurationException
     */
    public function validate()
    {
        if ($this->apiKey === '') {
            throw new ConfigurationException(
                'VarizPay API Key is not configured. Set VARIZPAY_API_KEY or save credentials via bin/varizpay-config.'
            );
        }
        if ($this->bankId === '') {
            throw new ConfigurationException(
                'VarizPay Bank ID is not configured. Set VARIZPAY_BANK_ID or save credentials via bin/varizpay-config.'
            );
        }
    }
}
