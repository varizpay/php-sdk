<?php

namespace VarizPay;

/**
 * A parsed payment callback delivered by VarizPay to the merchant's
 * callback_url.
 *
 * Contract: HTTP POST with a JSON body:
 *  - isSuccess      true/false; false implies error + details
 *  - transaction_id payment session id
 *  - amount         final amount in Rial (including suffix)
 *  - date           Shamsi timestamp "Y-m-d H:i:s"
 *  - depositMsg     full bank SMS
 *  - error/details  present only on failure
 */
final class Callback
{
    /** @var bool */
    private $isSuccess;

    /** @var string */
    private $transactionId;

    /** @var int */
    private $amount;

    /** @var string */
    private $date;

    /** @var string */
    private $depositMsg;

    /** @var string|null */
    private $error;

    /** @var string|null */
    private $details;

    public function __construct($isSuccess, $transactionId, $amount, $date, $depositMsg = '', $error = null, $details = null)
    {
        $this->isSuccess     = (bool) $isSuccess;
        $this->transactionId = (string) $transactionId;
        $this->amount        = (int) $amount;
        $this->date          = (string) $date;
        $this->depositMsg    = (string) $depositMsg;
        $this->error         = $error !== null ? (string) $error : null;
        $this->details       = $details !== null ? (string) $details : null;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isSuccess;
    }

    /**
     * @return string
     */
    public function transactionId()
    {
        return $this->transactionId;
    }

    /**
     * @return int
     */
    public function amount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function date()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function depositMsg()
    {
        return $this->depositMsg;
    }

    /**
     * @return string|null
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * @return string|null
     */
    public function details()
    {
        return $this->details;
    }

    /**
     * True when the reported amount matches the value expected for the order.
     *
     * @param int $expected Amount in Rial the merchant stored for this payment.
     * @return bool
     */
    public function amountMatches($expected)
    {
        return $this->amount === (int) $expected;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'isSuccess'     => $this->isSuccess,
            'transaction_id' => $this->transactionId,
            'amount'        => $this->amount,
            'date'          => $this->date,
            'depositMsg'    => $this->depositMsg,
            'error'         => $this->error,
            'details'       => $this->details,
        ];
    }
}
