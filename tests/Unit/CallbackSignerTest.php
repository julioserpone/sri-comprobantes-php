<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Exception\SignatureException;
use JulioSerpone\SriComprobantes\Signature\CallbackSigner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CallbackSigner::class)]
final class CallbackSignerTest extends TestCase
{
    public function testAcceptsNamespacedSignature(): void
    {
        $signed = (new CallbackSigner(
            static fn (string $xml): string => '<factura><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#"/></factura>'
        ))->sign('<factura/>');

        self::assertStringContainsString('ds:Signature', $signed);
    }

    public function testRejectsASignerThatDoesNotSign(): void
    {
        $this->expectException(SignatureException::class);

        (new CallbackSigner(static fn (string $xml): string => $xml))->sign('<factura/>');
    }

    public function testRejectsEmptyInput(): void
    {
        $this->expectException(SignatureException::class);

        (new CallbackSigner(static fn (string $xml): string => $xml))->sign('  ');
    }
}
