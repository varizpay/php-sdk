<?php

namespace VarizPay\Exception;

/**
 * Marker interface implemented by every exception thrown by the SDK so callers
 * can catch them all with a single `catch (VarizPayException $e)`.
 */
interface VarizPayException extends \Throwable
{
}
