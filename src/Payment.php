<?php

namespace VarizPay;

/**
 * Result of a successful payment link creation.
 *
 * Maps the VarizPay response:
 *  - payment_url     where the customer pays
 *  - token           unique payment token
 *  - payable_amount  final amount in Rial (including suffix)
 *  - expires_at      link expiry
 *  - transaction_id  id of the created payment session
 */
final class Payment
{
    /** @var string */
    private $paymentUrl;

    /** @var string */
    private $token;

    /** @var int */
    private $payableAmount;

    /** @var string|null */
    private $transactionId;

    /** @var int|null */
    private $amount;

    /** @var string|null */
    private $date;

    /** @var string|null */
    private $depositMsg;

    /** @var string|null */
    private $expiresAt;

    public function __construct($paymentUrl, $token, $payableAmount, $transactionId = null, $amount = null, $date = null, $depositMsg = null, $expiresAt = null)
    {
        $this->paymentUrl     = (string) $paymentUrl;
        $this->token          = (string) $token;
        $this->payableAmount  = (int) $payableAmount;
        $this->transactionId  = $transactionId !== null ? (string) $transactionId : null;
        $this->amount         = $amount !== null ? (int) $amount : null;
        $this->date           = $date !== null ? (string) $date : null;
        $this->depositMsg     = $depositMsg !== null ? (string) $depositMsg : null;
        $this->expiresAt      = $expiresAt !== null ? (string) $expiresAt : null;
    }

    /**
     * @param array $body Decoded VarizPay success response.
     * @return self
     */
    public static function fromResponse(array $body)
    {
        return new self(
            isset($body['payment_url']) ? $body['payment_url'] : '',
            isset($body['token']) ? $body['token'] : '',
            isset($body['payable_amount']) ? (int) $body['payable_amount'] : 0,
            isset($body['transaction_id']) ? $body['transaction_id'] : null,
            isset($body['amount']) ? (int) $body['amount'] : null,
            isset($body['date']) ? $body['date'] : null,
            isset($body['depositMsg']) ? $body['depositMsg'] : null,
            isset($body['expires_at']) ? $body['expires_at'] : null
        );
    }

    /**
     * @return string
     */
    public function paymentUrl()
    {
        return $this->paymentUrl;
    }

    /**
     * @return string
     */
    public function token()
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function payableAmount()
    {
        return $this->payableAmount;
    }

    /**
     * @return string|null
     */
    public function transactionId()
    {
        return $this->transactionId;
    }

    /**
     * @return int|null
     */
    public function amount()
    {
        return $this->amount;
    }

    /**
     * @return string|null
     */
    public function date()
    {
        return $this->date;
    }

    /**
     * @return string|null
     */
    public function depositMsg()
    {
        return $this->depositMsg;
    }

    /**
     * @return string|null
     */
    public function expiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'payment_url'    => $this->paymentUrl,
            'token'          => $this->token,
            'payable_amount' => $this->payableAmount,
            'transaction_id' => $this->transactionId,
            'amount'         => $this->amount,
            'date'           => $this->date,
            'depositMsg'     => $this->depositMsg,
            'expires_at'     => $this->expiresAt,
        ];
    }
}
