<?php

namespace VarizPay\Config;

use VarizPay\Crypto\Crypto;
use VarizPay\Exception\ConfigurationException;

/**
 * Credential storage backed by an encrypted local file.
 *
 * The whole payload (api_key, bank_id, extra fields) is sealed with
 * {@see Crypto} (AES-256-CBC + HMAC-SHA256) and written to disk as a JSON
 * document with mode 0600. The file path and the encryption key can be supplied
 * explicitly or resolved from the environment:
 *
 *  - path: VARIZPAY_CREDENTIALS_FILE (default "./.varizpay-credentials")
 *  - key:  VARIZPAY_STORAGE_KEY
 */
final class EncryptedFileStorage implements CredentialStorage
{
    public const ENVELOPE_VERSION = 1;

    /** @var string */
    private $filePath;

    /** @var string */
    private $key;

    public function __construct($filePath, $key)
    {
        if ($key === '') {
            throw new ConfigurationException('An encryption key is required. Set VARIZPAY_STORAGE_KEY.');
        }
        $this->filePath = $filePath;
        $this->key      = (string) $key;
    }

    /**
     * Build a store from environment variables.
     *
     * @param array|null $env
     * @param string|null $filePath Override the credentials file path.
     * @return self
     */
    public static function fromEnvironment($env = null, $filePath = null)
    {
        $env = $env !== null ? $env : getenv();

        $path = $filePath !== null
            ? $filePath
            : (isset($env['VARIZPAY_CREDENTIALS_FILE']) ? $env['VARIZPAY_CREDENTIALS_FILE'] : '.varizpay-credentials');

        $key = isset($env['VARIZPAY_STORAGE_KEY']) ? $env['VARIZPAY_STORAGE_KEY'] : '';

        return new self($path, $key);
    }

    /**
     * @return string
     */
    public function filePath()
    {
        return $this->filePath;
    }

    /**
     * {@inheritdoc}
     */
    public function save($apiKey, $bankId, array $extra = [])
    {
        $payload = $extra;
        $payload['api_key'] = (string) $apiKey;
        $payload['bank_id'] = (string) $bankId;

        $document = json_encode(
            [
                'v'    => self::ENVELOPE_VERSION,
                'data' => Crypto::encrypt(json_encode($payload), $this->key),
            ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $this->writeFile($document);
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        if (!is_file($this->filePath) || !is_readable($this->filePath)) {
            return null;
        }

        $document = json_decode((string) file_get_contents($this->filePath), true);
        if (!is_array($document) || !isset($document['data']) || (int) $document['v'] !== self::ENVELOPE_VERSION) {
            throw new ConfigurationException('Stored credentials file has an unsupported format.');
        }

        $payload = json_decode(Crypto::decrypt($document['data'], $this->key), true);
        if (!is_array($payload)) {
            throw new ConfigurationException('Stored credentials payload is corrupt.');
        }

        return $payload;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if (is_file($this->filePath)) {
            @unlink($this->filePath);
        }
    }

    /**
     * Write the file atomically with owner-only permissions.
     *
     * @param string $contents
     * @return void
     * @throws ConfigurationException
     */
    private function writeFile($contents)
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            throw new ConfigurationException('Credentials directory does not exist: ' . $dir);
        }

        $tmp = @tempnam($dir, '.varizpay-');
        if ($tmp === false) {
            throw new ConfigurationException('Cannot create a temporary file in: ' . $dir);
        }

        if (@file_put_contents($tmp, $contents) === false) {
            @unlink($tmp);
            throw new ConfigurationException('Cannot write credentials file: ' . $this->filePath);
        }

        @chmod($tmp, 0600);

        if (!@rename($tmp, $this->filePath)) {
            @unlink($tmp);
            throw new ConfigurationException('Cannot move credentials into place: ' . $this->filePath);
        }
    }
}
