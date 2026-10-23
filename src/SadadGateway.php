<?php

namespace Rohama\Koin\Sadad;

use GuzzleHttp\Client;
use Rohama\Koin\Support\Currency;
use Rohama\Koin\Support\PaymentStatus;
use Rohama\Koin\Exception\InvalidConfigException;
use Rohama\Koin\Exception\InvalidRequestException;
use Rohama\Koin\Exception\PaymentFailedException;
use Rohama\Koin\Exception\VerificationFailedException;
use Rohama\Koin\PaymentGateway;
use Rohama\Koin\Requests\Callback;
use Rohama\Koin\Responses\PaymentResponse;
use Rohama\Koin\Responses\VerificationResponse;
use Rohama\Koin\Traits\IsPayment;
use Rohama\Translator\Translator;

/**
 * SADAD (Bank Melli Iran PSP) gateway — REST WebAPI v0.
 */
class SadadGateway implements PaymentGateway
{
    use IsPayment { __construct as protected paymentConstruct; }

    public const name = 'sadad';

    private const RES_CODE_SUCCESS = '0';
    private const RES_CODE_ALREADY_VERIFIED = '100';

    public function __construct(string $apiKey, ?string $callbackUrl = null, bool $sandbox = false, array $extra = [], bool $refundable = false)
    {
        $this->paymentConstruct($apiKey, $callbackUrl, $sandbox, $extra, $refundable);

        if (($this->extra['merchantId'] ?? '') === '') {
            throw new InvalidConfigException('SADAD requires a merchant id.', gateway: self::name);
        }

        if (($this->extra['terminalKey'] ?? '') === '') {
            throw new InvalidConfigException('SADAD requires a terminal key.', gateway: self::name);
        }

        Translator::register('Koin-' . self::name, dirname(__DIR__) . '/lang');
        $this->client = new Client([
            'base_uri' => 'https://sadad.shaparak.ir',
            'timeout' => 15,
            'connect_timeout' => 10,
            'http_errors' => false,
        ]);
    }

    public static function configFields(): array
    {
        $prefix = 'Koin-' . self::name;

        return [
            [
                'key' => 'apiKey',
                'name' => Translator::trans($prefix . ':fields.apiKey'),
                'desc' => Translator::trans($prefix . ':fields.apiKey_help'),
                'type' => 'text',
                'required' => true,
                'validation' => ['required', 'string'],
                'regex' => '/^.+$/',
            ],
            [
                'key' => 'merchantId',
                'name' => Translator::trans($prefix . ':fields.merchantId'),
                'desc' => Translator::trans($prefix . ':fields.merchantId_help'),
                'type' => 'text',
                'required' => true,
                'validation' => ['required', 'string'],
                'regex' => '/^.+$/',
            ],
            [
                'key' => 'terminalKey',
                'name' => Translator::trans($prefix . ':fields.terminalKey'),
                'desc' => Translator::trans($prefix . ':fields.terminalKey_help'),
                'type' => 'password',
                'required' => true,
                'validation' => ['required', 'string'],
                'regex' => '/^.+$/',
            ],
        ];
    }

    public function request(
        int $amount,
        string $orderId = '',
        ?string $callbackUrl = null,
        string $description = '',
        ?string $mobile = null,
        ?string $email = null,
        ?string $currency = null,
        array $metadata = [],
        array $extra = [],
    ): PaymentResponse {
        if ($amount <= 0) {
            throw new InvalidRequestException('Amount must be greater than zero.', gateway: self::name);
        }

        if ($orderId === '' || !ctype_digit($orderId)) {
            throw new InvalidRequestException('SADAD requires a numeric order id.', gateway: self::name);
        }

        if ($callbackUrl === null && $this->callbackUrl === null) {
            throw new InvalidRequestException('SADAD requires a callback URL.', gateway: self::name);
        }

        $rial = Currency::toRial($currency ?? Currency::TOMAN, $amount);

        $response = $this->client()->post('/api/v0/Request/PaymentRequest', [
            'json' => array_filter(array_merge([
                'MerchantId' => $this->extra['merchantId'],
                'TerminalId' => $this->apiKey,
                'Amount' => $rial,
                'OrderId' => (int) $orderId,
                'LocalDateTime' => date('Y/m/d H:i:s'),
                'ReturnUrl' => $callbackUrl ?? $this->callbackUrl,
                'SignData' => $this->sign(sprintf('%s;%s;%d', $this->apiKey, $orderId, $rial)),
            ], $extra), fn ($value) => $value !== null && $value !== ''),
        ]);
        $data = json_decode($response->getBody()->getContents(), true);

        if ((string) ($data['ResCode'] ?? '') !== self::RES_CODE_SUCCESS || !isset($data['Token'])) {
            throw new PaymentFailedException($this->responseError($data), 0, raw: $data, gateway: self::name);
        }

        $token = (string) $data['Token'];

        return new PaymentResponse(
            $data,
            $response,
            PaymentStatus::PENDING,
            $token,
            'https://sadad.shaparak.ir/Purchase/Index?token=' . $token,
            $orderId,
            isset($data['Description']) ? (string) $data['Description'] : null,
        );
    }

    public function parseCallback(array $query, array $body): Callback
    {
        $raw = array_merge($query, $body);
        $token = isset($raw['token']) ? (string) $raw['token'] : '';
        $orderId = isset($raw['OrderId']) ? (string) $raw['OrderId'] : '';

        if ($token === '') {
            throw new InvalidRequestException('SADAD invalid Callback payload.', gateway: self::name);
        }

        if ((string) ($raw['ResCode'] ?? '') !== self::RES_CODE_SUCCESS && (string) ($raw['ResCode'] ?? '') !== self::RES_CODE_ALREADY_VERIFIED) {
            return new Callback(PaymentStatus::FAILED, $token, $orderId, $raw);
        }

        $verified = $this->verify($token, $orderId);

        return new Callback($verified->status, $token, $orderId, $raw);
    }

    public function verify(
        string $transactionId,
        string $orderId = '',
        ?int $amount = null,
        ?string $currency = null,
        array $extra = [],
    ): VerificationResponse {
        $response = $this->client()->post('/api/v0/Advice/Verify', [
            'json' => array_filter(array_merge([
                'Token' => $transactionId,
                'SignData' => $this->sign($transactionId),
            ], $extra)),
        ]);
        $data = json_decode($response->getBody()->getContents(), true);

        $resCode = (string) ($data['ResCode'] ?? '');

        if ($resCode !== self::RES_CODE_SUCCESS && $resCode !== self::RES_CODE_ALREADY_VERIFIED) {
            return new VerificationResponse(
                $data,
                $response,
                PaymentStatus::FAILED,
                $transactionId,
                null,
                null,
                null,
                $this->responseError($data),
            );
        }

        if ($amount !== null) {
            $expected = Currency::toRial($currency ?? Currency::TOMAN, $amount);
            $actual = isset($data['Amount']) ? (int) $data['Amount'] : null;

            if ($actual === null || $actual !== $expected) {
                throw new VerificationFailedException(
                    sprintf('SADAD verified amount mismatch: expected %d, got %s.', $expected, $actual === null ? 'null' : (string) $actual),
                    0,
                    raw: ['expected' => $expected, 'actual' => $actual],
                    gateway: self::name,
                );
            }
        }

        return new VerificationResponse(
            $data,
            $response,
            PaymentStatus::SUCCESS,
            $transactionId,
            isset($data['RetrivalRefNo']) ? (string) $data['RetrivalRefNo'] : null,
            isset($data['Amount']) ? Currency::fromRial($currency ?? Currency::TOMAN, (int) $data['Amount']) : null,
            null,
            isset($data['Description']) ? (string) $data['Description'] : null,
        );
    }

    /**
     * Signs the given data with the 3DES terminal key.
     */
    private function sign(string $data): string
    {
        $key = base64_decode($this->extra['terminalKey'], true);

        if ($key === false || $key === '') {
            throw new InvalidConfigException('SADAD terminal key must be base64-encoded 3DES key data.', gateway: self::name);
        }

        $encrypted = openssl_encrypt($data, 'des-ede3', $key, OPENSSL_RAW_DATA);

        if ($encrypted === false) {
            throw new InvalidConfigException('SADAD signing failed — the terminal key is not a valid 3DES key.', gateway: self::name);
        }

        return base64_encode($encrypted);
    }

    /**
     * Builds an error message from a SADAD failure payload.
     */
    private function responseError(array $data): string
    {
        $code = isset($data['ResCode']) ? (string) $data['ResCode'] : 'unknown';
        $desc = isset($data['Description']) ? (string) $data['Description'] : 'Unknown error';
        return sprintf('SADAD error (ResCode %s): %s', $code, $desc);
    }
}
