<?php

use Rohama\Koin\Exception\InvalidConfigException;
use Rohama\Koin\Exception\InvalidRequestException;
use Rohama\Koin\Exception\VerificationFailedException;
use Rohama\Koin\Sadad\SadadGateway;
use Rohama\Translator\Translator;

final class SadadGatewayTest extends TestCase
{
    private static function sadadGateway(array $extra = [], ?string $callbackUrl = null): SadadGateway
    {
        $key = base64_encode(str_repeat('k', 24));

        return new SadadGateway('TERMINAL', $callbackUrl, false, array_merge([
            'merchantId' => 'MERCHANT',
            'terminalKey' => $key,
        ], $extra));
    }

    public function testParseCallbackThrowsWithoutAToken(): void
    {
        $this->expectException(InvalidRequestException::class);
        self::sadadGateway()->parseCallback([], []);
    }

    public function testParseCallbackMapsResCode0ToSuccess(): void
    {
        $gateway = self::sadadGateway();
        $gateway->setClient($this->mockHttp([
            [200, ['ResCode' => '0', 'Amount' => 10000, 'RetrivalRefNo' => 'ref1']],
        ]));
        $result = $gateway->parseCallback([], ['token' => 'tk1', 'OrderId' => '123', 'ResCode' => '0']);

        $this->assertSame('success', $result->status);
        $this->assertSame('tk1', $result->transactionId);
        $this->assertSame('123', $result->orderId);
    }

    public function testParseCallbackVerifiesTheCallbackResult(): void
    {
        $gateway = self::sadadGateway();
        $gateway->setClient($this->mockHttp([
            [200, ['ResCode' => '-1', 'Description' => 'unsuccessful transaction']],
        ]));
        $result = $gateway->parseCallback([], ['token' => 'tk1', 'OrderId' => '123', 'ResCode' => '0']);

        $this->assertSame('failed', $result->status);
    }

    public function testParseCallbackMapsANonZeroResCodeToFailed(): void
    {
        $gateway = self::sadadGateway();
        $result = $gateway->parseCallback([], ['token' => 'tk1', 'OrderId' => '123', 'ResCode' => '1']);

        $this->assertSame('failed', $result->status);
    }

    public function testVerifyReturnsSuccessWithConvertedAmount(): void
    {
        $gateway = self::sadadGateway();
        $gateway->setClient($this->mockHttp([
            [200, ['ResCode' => '0', 'Amount' => 10000, 'RetrivalRefNo' => 'ref1']],
        ]));

        $result = $gateway->verify('tk1', amount: 1000);

        $this->assertSame('success', $result->status);
        $this->assertSame('ref1', $result->referenceId);
        $this->assertSame(1000, $result->amount);
    }

    public function testVerifyThrowsOnAmountMismatch(): void
    {
        $gateway = self::sadadGateway();
        $gateway->setClient($this->mockHttp([
            [200, ['ResCode' => '0', 'Amount' => 9999]],
        ]));

        $this->expectException(VerificationFailedException::class);
        $this->expectExceptionMessage('amount mismatch');
        $gateway->verify('tk1', amount: 1000);
    }

    public function testVerifyMapsANonZeroResCodeToAFailedResult(): void
    {
        $gateway = self::sadadGateway();
        $gateway->setClient($this->mockHttp([
            [200, ['ResCode' => '31', 'Description' => 'invalid token']],
        ]));

        $result = $gateway->verify('tk1');

        $this->assertSame('failed', $result->status);
        $this->assertStringContainsString('31', $result->message);
    }

    public function testSignProducesTheKnownSadad3desEcbPkcs7Signature(): void
    {
        $gateway = new SadadGateway('TERMINAL', null, false, [
            'merchantId' => 'MERCHANT',
            'terminalKey' => 'MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=',
        ]);
        $method = new ReflectionMethod(SadadGateway::class, 'sign');
        $method->setAccessible(true);

        $this->assertSame('NG3BJGK7R6ZPrcTfkW9FFATWF8dxL98A', $method->invoke($gateway, 'TERMINAL;ORDER;100000'));
    }

    public function testConstructorRejectsMissingMerchantId(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('merchant id');
        new SadadGateway('TERMINAL', null, false, ['terminalKey' => base64_encode(str_repeat('k', 24))]);
    }

    public function testConfigFieldsListsTheSadadInputs(): void
    {
        $keys = array_map(fn ($f) => $f['key'], SadadGateway::configFields());

        $this->assertSame(['apiKey', 'merchantId', 'terminalKey'], $keys);
    }

    public function testLangFilesCoverTheSadadName(): void
    {
        $this->assertSame('SADAD (Bank Melli)', Translator::trans('Koin-sadad:driver.name'));
    }
}
