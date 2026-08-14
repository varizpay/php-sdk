<?php

namespace VarizPay\Exception;

/**
 * Thrown when credentials are missing/empty or a stored credentials file cannot
 * be read/decrypted (e.g. wrong storage key).
 */
class ConfigurationException extends \InvalidArgumentException implements VarizPayException
{
}
