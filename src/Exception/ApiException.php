<?php

namespace VarizPay\Exception;

/**
 * Thrown when a call to the VarizPay API fails: transport errors, HTTP errors,
 * or the API's error envelope ({isSuccess:false, error, details}).
 */
class ApiException extends \RuntimeException implements VarizPayException
{
    /** @var int|null HTTP status code received from the API, if any. */
    private $statusCode;

    /** @var string|null Machine-readable `error` code from the API envelope. */
    private $errorCode;

    /** @var string|null `details` from the API error envelope. */
    private $details;

    public function __construct($message, $statusCode = null, $errorCode = null, $details = null, $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->errorCode  = $errorCode;
        $this->details    = $details;
    }

    /**
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string|null
     */
    public function getDetails()
    {
        return $this->details;
    }
}
