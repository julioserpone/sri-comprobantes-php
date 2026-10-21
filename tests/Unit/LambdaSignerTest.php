<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Exception\SignatureException;
use JulioSerpone\SriComprobantes\Signature\CallableLambdaInvoker;
use JulioSerpone\SriComprobantes\Signature\LambdaSigner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LambdaSigner::class)]
#[CoversClass(CallableLambdaInvoker::class)]
final class LambdaSignerTest extends TestCase
{
    private const TENANT_UUID = '123e4567-e89b-12d3-a456-426614174000';

    public function testSendsTenantPasswordAndXmlToTheInvoker(): void
    {
        $captured = [];
        $invoker = new CallableLambdaInvoker(
            static function (array $payload) use (&$captured): array {
                $captured = $payload;

                return [
                    'signedXmlBase64' => base64_encode(
                        '<factura><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#"/></factura>'
                    ),
                ];
            }
        );
        $signer = new LambdaSigner($invoker, self::TENANT_UUID, 'secret');

        $signed = $signer->sign('<factura id="comprobante"/>');

        self::assertStringContainsString('ds:Signature', $signed);
        self::assertSame(self::TENANT_UUID, $captured['tenantUuid']);
        self::assertSame('secret', $captured['password']);
        self::assertSame('<factura id="comprobante"/>', base64_decode($captured['xmlBase64'], true));
    }

    public function testMapsKnownRemoteErrorsToSafeMessages(): void
    {
        $invoker = new CallableLambdaInvoker(
            static fn (array $payload): array => ['error' => 'certificate_unavailable']
        );
        $signer = new LambdaSigner($invoker, self::TENANT_UUID, 'secret');

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('certificado');

        $signer->sign('<factura/>');
    }

    public function testRejectsAResponseWithoutSignedXml(): void
    {
        $invoker = new CallableLambdaInvoker(static fn (array $payload): array => []);
        $signer = new LambdaSigner($invoker, self::TENANT_UUID, 'secret');

        $this->expectException(SignatureException::class);

        $signer->sign('<factura/>');
    }

    public function testRejectsInvalidBase64InTheResponse(): void
    {
        $invoker = new CallableLambdaInvoker(
            static fn (array $payload): array => ['signedXmlBase64' => 'not base64!']
        );
        $signer = new LambdaSigner($invoker, self::TENANT_UUID, 'secret');

        $this->expectException(SignatureException::class);

        $signer->sign('<factura/>');
    }

    public function testRejectsAnUnsignedXmlResponse(): void
    {
        $invoker = new CallableLambdaInvoker(
            static fn (array $payload): array => [
                'signedXmlBase64' => base64_encode('<factura/>'),
            ]
        );
        $signer = new LambdaSigner($invoker, self::TENANT_UUID, 'secret');

        $this->expectException(SignatureException::class);

        $signer->sign('<factura/>');
    }

    public function testRejectsInvalidConfiguration(): void
    {
        $invoker = new CallableLambdaInvoker(static fn (array $payload): array => []);

        $this->expectException(\InvalidArgumentException::class);
        new LambdaSigner($invoker, 'not-a-uuid', 'secret');
    }

    public function testRejectsAnEmptyPassword(): void
    {
        $invoker = new CallableLambdaInvoker(static fn (array $payload): array => []);

        $this->expectException(\InvalidArgumentException::class);
        new LambdaSigner($invoker, self::TENANT_UUID, '');
    }

    public function testRejectsEmptyXml(): void
    {
        $invoker = new CallableLambdaInvoker(static fn (array $payload): array => []);
        $signer = new LambdaSigner($invoker, self::TENANT_UUID, 'secret');

        $this->expectException(SignatureException::class);

        $signer->sign('  ');
    }

    public function testDebugInfoDoesNotExposeThePassword(): void
    {
        $invoker = new CallableLambdaInvoker(static fn (array $payload): array => []);
        $signer = new LambdaSigner($invoker, self::TENANT_UUID, 'super-secret-password');

        $debug = print_r($signer, true);

        self::assertStringNotContainsString('super-secret-password', $debug);
        self::assertStringContainsString('[redacted]', $debug);
    }

    public function testCannotBeSerializedWithThePassword(): void
    {
        $invoker = new CallableLambdaInvoker(static fn (array $payload): array => []);
        $signer = new LambdaSigner($invoker, self::TENANT_UUID, 'super-secret-password');

        $this->expectException(\LogicException::class);

        serialize($signer);
    }
}
