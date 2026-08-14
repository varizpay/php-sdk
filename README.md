# VarizPay PHP SDK

[![PHP](https://img.shields.io/badge/PHP-%3E%3D%207.4-777BB4?logo=php)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![GitHub release](https://img.shields.io/github/v/release/varizpay/php-sdk?include_prereleases\&label=release)](https://github.com/varizpay/php-sdk/releases)
[![GitHub issues](https://img.shields.io/github/issues/varizpay/php-sdk)](https://github.com/varizpay/php-sdk/issues)

A **framework-agnostic PHP SDK for the VarizPay REST API**, designed for PHP applications and REST API backends.

The SDK provides a simple and secure interface for creating VarizPay payment links, handling payment callbacks, validating transaction amounts, and securely storing merchant credentials.

It can be integrated into custom PHP applications, REST APIs, e-commerce platforms, CMS plugins, and other PHP-based systems without requiring a specific framework.

---

## ✨ Features

* 🔗 **Create VarizPay payment links**

  * Create a unique payment transaction for each order.
  * Receive the generated payment URL and transaction token.

* 🔔 **Payment callback / webhook handling**

  * Parse and validate VarizPay callback payloads.
  * Handle successful, failed, and expired transactions.

* 💰 **Payment amount validation**

  * Validate the callback amount against the expected order amount.
  * Helps prevent incorrect or manipulated payment confirmations.

* 🏦 **Bank account configuration**

  * Configure the merchant `bank_id`.
  * Automatically sends the configured value as `bank_account_id`.

* 🔐 **Secure credential storage**

  * Supports encrypted local credential storage.
  * Uses AES-256-CBC encryption.
  * Uses HMAC-SHA256 for tamper detection.
  * Credential files are written with `0600` permissions.
  * Credentials can be managed through the included CLI tool.

* 🌐 **Framework agnostic**

  * No Laravel, Symfony, WordPress, Magento, or other framework dependency.
  * Suitable for custom PHP applications and REST APIs.

* 📡 **Flexible HTTP transport**

  * Supports `ext-curl`.
  * Provides a stream-context fallback where cURL is unavailable.

* ⚙️ **Environment-based configuration**

  * API credentials can be supplied through environment variables.
  * Environment variables take precedence over encrypted file storage.

* 🧩 **Typed SDK API**

  * Dedicated configuration, client, webhook, and exception classes.
  * Designed for predictable integration into backend applications.

---

# 📋 Requirements

* PHP **7.4 or higher**
* `ext-json`
* `ext-openssl`
* `ext-curl` recommended
* Composer

The SDK can fall back to PHP stream contexts when cURL is unavailable.

---

# 📦 Installation

## Composer

The recommended installation method is Composer:

```bash
composer require varizpay/php-sdk
```

Composer will install the SDK and its dependencies into your project's `vendor/` directory.

---

## Local / Plugin Installation

If the SDK source is included as part of another project or plugin:

```bash
composer install --working-dir=plugins/varizpay-php
```

Then load Composer's autoloader:

```php
require 'vendor/autoload.php';
```

Adjust the autoloader path according to your project's directory structure.

---

# ⚙️ Configuration

The SDK requires two merchant credentials:

| Setting     | Environment Variable | API Representation              |
| ----------- | -------------------- | ------------------------------- |
| **API Key** | `VARIZPAY_API_KEY`   | `X-API-Key` HTTP header         |
| **Bank ID** | `VARIZPAY_BANK_ID`   | `bank_account_id` request field |

### Optional Configuration

| Setting          | Environment Variable        | Default                    |
| ---------------- | --------------------------- | -------------------------- |
| API Base URL     | `VARIZPAY_BASE_URL`         | `https://varizpay.com/api` |
| Request Timeout  | `VARIZPAY_TIMEOUT`          | `30` seconds               |
| Storage Key      | `VARIZPAY_STORAGE_KEY`      | —                          |
| Credentials File | `VARIZPAY_CREDENTIALS_FILE` | `./.varizpay-credentials`  |

---

# 🔐 Secure Credential Storage

The SDK supports encrypted local storage for the VarizPay API Key and Bank ID.

Instead of storing credentials directly in application source code or configuration files, credentials can be saved into an encrypted local file and loaded at runtime.

The credential file uses:

```text
AES-256-CBC
+
HMAC-SHA256
```

The encryption provides confidentiality, while the HMAC provides tamper detection.

The credentials file is written with:

```text
0600
```

file permissions.

## Credential Resolution

The SDK resolves credentials in the following order:

```text
Environment Variables
        │
        ▼
Encrypted Credential File
```

Environment variables always take precedence.

If a credential is not available through the environment, the SDK attempts to load it from the encrypted credential store.

---

# 🔑 Configure Credentials

Set a strong application storage key:

```bash
export VARIZPAY_STORAGE_KEY='a-long-random-app-key'
```

Save your VarizPay credentials:

```bash
vendor/bin/varizpay-config set \
    --api-key=your_api_key \
    --bank-id=your_bank_id
```

The credentials are encrypted before being written to the credential file.

---

# 🛠️ Credential Management CLI

The SDK includes a command-line utility:

```text
vendor/bin/varizpay-config
```

## View Stored Credentials

By default, sensitive values are masked:

```bash
vendor/bin/varizpay-config get
```

## Show Stored Secrets

To explicitly reveal stored credentials:

```bash
vendor/bin/varizpay-config get --show-secrets
```

> Use `--show-secrets` carefully and avoid exposing the output in shell history, CI logs, terminal recordings, or shared environments.

## Delete Stored Credentials

```bash
vendor/bin/varizpay-config clear
```

This removes the encrypted credential store.

---

# 💳 Create a Payment Link

Create a VarizPay payment using the SDK:

```php
<?php

use VarizPay\Config\Config;
use VarizPay\Config\EncryptedFileStorage;
use VarizPay\Client;
use VarizPay\Exception\ApiException;

$config = Config::resolve(
    null,
    EncryptedFileStorage::fromEnvironment()
);

$client = new Client($config);

try {
    $payment = $client->createPayment([
        'amount'       => 1500000,
        'order_id'     => 'order-100',
        'callback_url' => 'https://api.yoursite.com/payments/callback',
    ]);

    header('Location: ' . $payment->paymentUrl());
    exit;

} catch (ApiException $e) {

    http_response_code(422);

    echo json_encode([
        'error'   => $e->getMessage(),
        'details' => $e->getDetails(),
    ]);
}
```

The returned payment object provides access to:

```php
$payment->paymentUrl();
$payment->token();
```

For example:

```php
$response = [
    'payment_url' => $payment->paymentUrl(),
    'token'       => $payment->token(),
];
```

---

# 🏦 Bank Account ID

The SDK automatically injects the configured:

```text
VARIZPAY_BANK_ID
```

into the API request as:

```text
bank_account_id
```

You can override the configured bank account ID by explicitly providing your own value when creating the payment.

This allows applications with multiple bank accounts to select a specific account per transaction when required.

---

# 🔔 Payment Callback / Webhook

When a VarizPay payment reaches its final state, VarizPay sends the result to the `callback_url` supplied when the payment was created.

The callback body is JSON.

Example callback handler:

```php
<?php

use VarizPay\Webhook;
use VarizPay\Exception\CallbackException;

$webhook = new Webhook();

try {

    $callback = $webhook->parseBody(
        file_get_contents('php://input')
    );

    // Find your order using the VarizPay transaction ID.
    $transactionId = $callback->transactionId();

    // Retrieve the expected amount from your database.
    $expectedAmount = 1500000;

    if (!$callback->amountMatches($expectedAmount)) {

        // Amount mismatch.
        // Do not fulfil the order.
        http_response_code(400);

        echo json_encode([
            'ok'    => false,
            'error' => 'Payment amount mismatch',
        ]);

        exit;
    }

    if ($callback->isSuccess()) {

        // Payment verified.
        // Fulfil the order here.

        // $callback->depositMsg()
        // contains the full bank SMS.
    } else {

        // Payment failed or was not completed.
        //
        // $callback->error()
        // $callback->details()
        // contain additional information.
    }

    http_response_code(200);

    echo json_encode([
        'ok' => true,
    ]);

} catch (CallbackException $e) {

    http_response_code(400);

    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
    ]);
}
```

---

# 🔄 Recommended Callback Flow

A typical backend integration should follow this sequence:

```text
┌─────────────────────────┐
│ VarizPay Payment Created│
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│ Store Order + Expected  │
│ Amount + Transaction ID │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│ Customer Completes       │
│ Payment                  │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│ VarizPay Verifies Bank  │
│ Transaction              │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│ Callback POST            │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│ Parse Callback           │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│ Find Order by            │
│ transaction_id           │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│ Validate Amount          │
└────────────┬────────────┘
             │
        ┌────┴─────┐
        ▼          ▼
     MATCH       MISMATCH
        │          │
        ▼          ▼
   Check Status   Reject
        │
    ┌───┴────┐
    ▼        ▼
 SUCCESS   FAILURE
    │        │
    ▼        ▼
 Fulfil    Fail Order
```

---

# 📄 Callback Contract

The callback contains the final transaction result.

| Field            | Description                                                      |
| ---------------- | ---------------------------------------------------------------- |
| `isSuccess`      | Boolean indicating whether the payment succeeded                 |
| `transaction_id` | VarizPay transaction identifier                                  |
| `amount`         | Final payable amount in Rial, including any unique amount suffix |
| `date`           | Transaction date in Shamsi `Y-m-d H:i:s` format                  |
| `depositMsg`     | Full bank SMS received for the transaction                       |
| `error`          | Error information when the transaction fails                     |
| `details`        | Additional failure or transaction information                    |

### Successful Callback

When:

```text
isSuccess = true
```

the payment has been successfully verified.

The `depositMsg` field contains the full bank SMS:

```text
Sender: <num>
<text>
```

### Failed Callback

When:

```text
isSuccess = false
```

the callback contains failure information through:

```text
error
details
```

The order should not be fulfilled.

---

# 💰 Amount Handling

All VarizPay API amounts are expressed in **Rial**.

If your application uses Toman, convert the amount before calling `createPayment()`:

```php
$amountInRial = $amountInToman * 10;
```

Example:

```text
100,000 Toman
        ×
10
        =
1,000,000 Rial
```

Then:

```php
$payment = $client->createPayment([
    'amount'       => 1000000,
    'order_id'     => 'order-100',
    'callback_url' => 'https://api.yoursite.com/payments/callback',
]);
```

## Important: Callback Amount

The callback `amount` represents the **final payable amount in Rial**, including any unique amount suffix that may have been assigned to the transaction.

Always compare the callback amount against the expected payable amount stored when the payment was created:

```php
if (!$callback->amountMatches($expectedAmount)) {
    // Reject the payment.
}
```

Do not trust the callback amount without validating it against your own stored transaction data.

---

# 🧯 Exception Handling

The SDK provides dedicated exceptions for API and callback failures.

## API Exceptions

```php
use VarizPay\Exception\ApiException;

try {

    $payment = $client->createPayment([
        'amount'       => 1500000,
        'order_id'     => 'order-100',
        'callback_url' => 'https://api.example.com/callback',
    ]);

} catch (ApiException $e) {

    $e->getErrorCode();
    $e->getStatusCode();
    $e->getDetails();
}
```

Available information includes:

* Error message
* API error code
* HTTP status code
* Additional API details

## Callback Exceptions

```php
use VarizPay\Exception\CallbackException;

try {

    $callback = $webhook->parseBody(
        file_get_contents('php://input')
    );

} catch (CallbackException $e) {

    http_response_code(400);

    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
    ]);
}
```

Invalid or malformed callback payloads should not be treated as successful payments.

---

# 🔒 Security

The SDK is designed to minimize the exposure of merchant credentials and payment data.

## Credential Encryption

The encrypted credential store uses:

```text
AES-256-CBC
+
HMAC-SHA256
```

AES-256-CBC provides encryption of the stored credentials.

HMAC-SHA256 provides integrity protection and allows the SDK to detect tampered or corrupted credential files.

## File Permissions

The credentials file is created with:

```text
0600
```

Only the account running the application should have access to the file.

## Storage Key

The encryption/storage key is supplied through:

```text
VARIZPAY_STORAGE_KEY
```

or through the CLI:

```bash
--key
```

The storage key should be:

* Long
* Random
* Unique to the application
* Stored outside source control
* Managed through a secret manager or secure environment configuration

Never commit:

```text
VARIZPAY_STORAGE_KEY
```

to Git.

## Environment Variables

Environment variables take precedence over the encrypted credential store.

This makes it possible to use the SDK with:

* Docker secrets
* Kubernetes secrets
* CI/CD secrets
* Cloud secret managers
* `.env` files
* Traditional server environment configuration

---

# 🛡️ Payment Security Recommendations

When processing a callback:

1. Parse the raw JSON body using the SDK.
2. Retrieve your local order using `transaction_id`.
3. Retrieve the expected payment amount from your own database.
4. Call `amountMatches()`.
5. Check `isSuccess()`.
6. Fulfil the order only after all required validations pass.
7. Make order fulfilment idempotent so duplicate callbacks cannot create duplicate fulfilments.

For example:

```php
if (
    $callback->isSuccess()
    && $callback->amountMatches($expectedAmount)
) {
    // Fulfil order exactly once.
}
```

Never fulfil an order solely because the callback contains:

```text
isSuccess = true
```

without validating the associated transaction and expected amount.

---

# 🧩 Framework Compatibility

The SDK is intentionally framework-agnostic.

It can be used with:

* Plain PHP
* PHP REST APIs
* Laravel
* Symfony
* Slim
* Laminas
* Custom PHP applications
* WooCommerce integrations
* Magento integrations
* Other PHP-based backend systems

No framework-specific code is required by the SDK itself.

---

# 📁 Package Structure

A typical installation contains:

```text
varizpay/php-sdk/
├── src/
│   ├── Client.php
│   ├── Config/
│   ├── Exception/
│   └── Webhook.php
├── bin/
│   └── varizpay-config
├── composer.json
├── LICENSE
└── README.md
```

The exact structure may change between releases.

---

# 🛠️ Development

Clone the repository:

```bash
git clone https://github.com/varizpay/php-sdk.git
cd php-sdk
```

Install dependencies:

```bash
composer install
```

Run the project's test suite if available:

```bash
composer test
```

---

# 🚀 Production Checklist

Before deploying the SDK to production:

* [ ] Store the VarizPay API Key securely.
* [ ] Store the VarizPay Bank ID securely.
* [ ] Keep `VARIZPAY_STORAGE_KEY` out of source control.
* [ ] Use HTTPS for callback URLs.
* [ ] Store the expected payment amount in your database.
* [ ] Validate callback amounts using `amountMatches()`.
* [ ] Validate the transaction ID against your local order.
* [ ] Fulfil orders only after successful validation.
* [ ] Make callback processing idempotent.
* [ ] Restrict access to the encrypted credentials file.
* [ ] Ensure the credentials file has `0600` permissions.
* [ ] Never log API Keys or storage keys.
* [ ] Never expose `--show-secrets` output in CI/CD logs.
* [ ] Use a secure secret manager where available.

---

# 📚 API Overview

The primary SDK components are:

```text
VarizPay\Config\Config
VarizPay\Config\EncryptedFileStorage
VarizPay\Client
VarizPay\Webhook
VarizPay\Exception\ApiException
VarizPay\Exception\CallbackException
```

### Payment Creation

```php
$client->createPayment([...]);
```

### Payment URL

```php
$payment->paymentUrl();
```

### Payment Token

```php
$payment->token();
```

### Callback Parsing

```php
$webhook->parseBody($rawBody);
```

### Transaction ID

```php
$callback->transactionId();
```

### Payment Status

```php
$callback->isSuccess();
```

### Amount Validation

```php
$callback->amountMatches($expectedAmount);
```

### Bank SMS

```php
$callback->depositMsg();
```

### Error Information

```php
$callback->error();
$callback->details();
```

---

# 📄 License

This project is licensed under the **MIT License**.

See the [`LICENSE`](LICENSE) file for the complete license text.

---

# 🏦 About VarizPay

**VarizPay** provides automated bank-transfer payment verification infrastructure for online businesses.

Instead of requiring customers to manually upload payment receipts, VarizPay verifies eligible bank transactions and provides an automated confirmation mechanism that can be integrated into websites, REST APIs, e-commerce platforms, and custom applications.

For more information:

**https://varizpay.com**

---

# ⭐ Support

If you encounter a bug or integration issue, please open an issue in the repository:

**https://github.com/varizpay/php-sdk/issues**

When reporting an issue, include:

* PHP version
* SDK version
* Operating system
* Relevant exception message
* Relevant stack trace
* Steps to reproduce the issue

**Never include your API Key, Bank ID, storage key, customer information, bank SMS data, or other sensitive credentials in GitHub issues.**

---

## 📌 Repository

**GitHub:**
https://github.com/varizpay/php-sdk

**Website:**
https://varizpay.com

