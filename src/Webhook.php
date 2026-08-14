<?php

namespace VarizPay;

use VarizPay\Exception\CallbackException;

/**
 * Validates and parses payment callbacks delivered by VarizPay.
 *
 * The merchant application exposes its callback_url to the API; when the
 * payment reaches a final state VarizPay POSTs the result there. This class
 * turns the raw payload into a typed {@see Callback} object and verifies the
 * reported amount against the merchant's expected value.
 */
final class Webhook
{
    /**
     * Parse and validate a callback payload.
     *
     * @param array $payload Decoded JSON body of the callback request.
     * @return Callback
     * @throws CallbackException When required fields are missing or malformed.
     */
    public function parse(array $payload)
    {
        if (!isset($payload['transaction_id']) || $payload['transaction_id'] === '') {
            throw new CallbackException('Callback is missing required field: transaction_id.');
        }
        if (!isset($payload['amount'])) {
            throw new CallbackException('Callback is missing required field: amount.');
        }
        if (!isset($payload['isSuccess'])) {
            throw new CallbackException('Callback is missing required field: isSuccess.');
        }

        return new Callback(
            $payload['isSuccess'],
            $payload['transaction_id'],
            $payload['amount'],
            isset($payload['date']) ? $payload['date'] : '',
            isset($payload['depositMsg']) ? $payload['depositMsg'] : '',
            isset($payload['error']) ? $payload['error'] : null,
            isset($payload['details']) ? $payload['details'] : null
        );
    }

    /**
     * Parse a callback from the raw HTTP request body (the request is always
     * JSON regardless of Content-Type, so the raw body is decoded directly).
     *
     * @param string $rawBody
     * @return Callback
     * @throws CallbackException
     */
    public function parseBody($rawBody)
    {
        if ($rawBody === '') {
            throw new CallbackException('Callback request body is empty.');
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            throw new CallbackException('Callback request body is not valid JSON.');
        }

        return $this->parse($payload);
    }
}
