# Koin — SADAD

<p align="center">
  <a href="https://rohamaa.github.io/Koin-Core/">Docs site</a> ·
  <a href="https://github.com/rohamaa/Koin-Core/blob/docs/index.md">Docs on GitHub</a>
</p>

## The **SADAD** gateway driver (Bank Melli) for the Koin package — built on SADAD's REST WebAPI v0.

### Install

```bash
composer require rohama/koin-sadad
```

### Features

- Payment request — returns token + redirect URL
- Verify
- Callback parsing

> Per SADAD's official docs, this API version offers no refund/inquiry/sandbox APIs; the related capabilities are not implemented.

### Configuration

| Field | Description |
| --- | --- |
| `apiKey` | API key |
| `merchantId` | Merchant id |
| `terminalKey` | Terminal key |

### Usage

```php
use Rohama\Koin\Sadad\SadadGateway;
use Rohama\Koin\Support\PaymentStatus;

$gateway = new SadadGateway(
    'api-key',
    'https://example.com/callback',
    extra: [
        'merchantId'  => '…',
        'terminalKey' => '…',
    ],
);

$payment = $gateway->request(150000, 'ORDER-1', 'Subscription purchase');

if ($payment->isSuccessful()) {
    header('Location: ' . $payment->redirectUrl);
    exit;
}

// In the callback page — parseCallback runs verify() before reporting success
$callback = $gateway->parseCallback($_GET, $_POST);
if ($callback->status === PaymentStatus::SUCCESS) {
    // Verified
}
```

> `refund()` (in gateways that offer Refund) is available only when the developer-only 5th constructor parameter (`refundable`) is enabled.

Full documentation: <https://rohamaa.github.io/Koin-Core/>