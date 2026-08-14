<?php

namespace VarizPay\Exception;

/**
 * Thrown when a payment callback / webhook payload is malformed or missing
 * required fields.
 */
class CallbackException extends \InvalidArgumentException implements VarizPayException
{
}
