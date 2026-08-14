<?php

namespace VarizPay;

use VarizPay\Config\Config;
use VarizPay\Exception\ApiException;

/**
 * Client for the VarizPay REST API.
 *
 * Sends the configured API Key as the `X-API-Key` header and the configured
 * Bank ID as `bank_account_id` on every payment request.
 */
final class Client
{
    /** @var Config */
    private $config;

    /** @var bool */
    private $curlAvailable;

    public function __construct(Config $config)
    {
        $this->config         = $config;
        $this->curlAvailable  = function_exists('curl_init');
    }

    /**
     * Create a payment link.
     *
     * @param array $payload Fields for the VarizPay API:
     *                       amount (Rial), order_id, callback_url (optional).
     *                       bank_account_id is injected from the config unless
     *                       already present.
     * @return Payment
     * @throws ApiException
     * @throws \VarizPay\Exception\ConfigurationException
     */
    public function createPayment(array $payload)
    {
        $this->config->validate();

        if (!isset($payload['amount']) || $payload['amount'] === '' || $payload['amount'] === null) {
            throw new ApiException('Missing required payload field: amount (in Rial).');
        }
        if (!isset($payload['order_id']) || $payload['order_id'] === '') {
            throw new ApiException('Missing required payload field: order_id.');
        }

        if (!isset($payload['bank_account_id']) || $payload['bank_account_id'] === '') {
            $payload['bank_account_id'] = $this->config->bankId();
        }

        $payload['amount']   = (int) $payload['amount'];
        $payload['order_id'] = (string) $payload['order_id'];

        $url      = $this->config->baseUrl() . '/v1/payments';
        $response = $this->request($url, $payload);

        if (!isset($response['isSuccess']) || !$response['isSuccess']) {
            throw new ApiException(
                isset($response['error']) ? $response['error'] : 'VarizPay request failed.',
                0,
                isset($response['error']) ? $response['error'] : null,
                isset($response['details']) ? $response['details'] : null
            );
        }

        return Payment::fromResponse($response);
    }

    /**
     * POST JSON to the VarizPay API and decode the response.
     *
     * @param string $url
     * @param array $payload
     * @return array
     * @throws ApiException
     */
    private function request($url, array $payload)
    {
        $body     = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $headers  = [
            'X-API-Key'    => $this->config->apiKey(),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        [$statusCode, $responseBody] = $this->curlAvailable
            ? $this->requestWithCurl($url, $body, $headers)
            : $this->requestWithStreams($url, $body, $headers);

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            throw new ApiException(
                sprintf('VarizPay returned an unexpected response (HTTP %s).', $statusCode),
                $statusCode
            );
        }

        if ($statusCode >= 400) {
            throw new ApiException(
                isset($decoded['error']) ? $decoded['error'] : sprintf('VarizPay request failed (HTTP %d).', $statusCode),
                $statusCode,
                isset($decoded['error']) ? $decoded['error'] : null,
                isset($decoded['details']) ? $decoded['details'] : null
            );
        }

        return $decoded;
    }

    /**
     * @param string $url
     * @param string $body
     * @param array $headers
     * @return array [int $statusCode, string $body]
     * @throws ApiException
     */
    private function requestWithCurl($url, $body, array $headers)
    {
        $curl = curl_init($url);

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->config->timeout(),
            CURLOPT_TIMEOUT        => $this->config->timeout(),
        ]);

        $responseBody = curl_exec($curl);
        $statusCode   = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error        = curl_error($curl);

        curl_close($curl);

        if ($responseBody === false) {
            throw new ApiException('Could not reach the VarizPay API: ' . $error);
        }

        return [$statusCode, (string) $responseBody];
    }

    /**
     * @param string $url
     * @param string $body
     * @param array $headers
     * @return array [int $statusCode, string $body]
     * @throws ApiException
     */
    private function requestWithStreams($url, $body, array $headers)
    {
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", $headerLines) . "\r\n",
                'content' => $body,
                'timeout' => $this->config->timeout(),
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            $error = error_get_last();
            throw new ApiException(
                'Could not reach the VarizPay API' . (isset($error['message']) ? ': ' . $error['message'] : '.')
            );
        }

        $statusCode = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                    $statusCode = (int) $m[1];
                }
            }
        }

        return [$statusCode, (string) $responseBody];
    }
}
