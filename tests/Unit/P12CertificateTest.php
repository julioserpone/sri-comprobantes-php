<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Exception\SignatureException;
use JulioSerpone\SriComprobantes\Signature\P12Certificate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(P12Certificate::class)]
final class P12CertificateTest extends TestCase
{
    public function testLoadsAValidPkcs12(): void
    {
        $p12 = $this->createP12('secret');

        $certificate = P12Certificate::fromContents($p12, 'secret');

        self::assertStringContainsString('BEGIN CERTIFICATE', $certificate->certificatePem());
        self::assertStringContainsString('BEGIN PRIVATE KEY', $certificate->privateKeyPem());
        self::assertFalse($certificate->isExpired());
        self::assertGreaterThan(0, $certificate->daysUntilExpiry());
        self::assertStringContainsString('SRI Test', $certificate->subject());
    }

    public function testRejectsWrongPassword(): void
    {
        $this->expectException(SignatureException::class);

        P12Certificate::fromContents($this->createP12('secret'), 'incorrecta');
    }

    public function testRejectsMissingFile(): void
    {
        $this->expectException(SignatureException::class);

        P12Certificate::fromFile('/ruta/inexistente.p12', 'secret');
    }

    private function createP12(string $password): string
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        self::assertNotFalse($key, 'No se pudo generar la clave de prueba.');

        $csr = openssl_csr_new(['commonName' => 'SRI Test'], $key, ['digest_alg' => 'sha256']);
        if (!$csr instanceof \OpenSSLCertificateSigningRequest) {
            self::fail('No se pudo generar el CSR de prueba.');
        }

        $cert = openssl_csr_sign($csr, null, $key, 30, ['digest_alg' => 'sha256']);
        self::assertNotFalse($cert, 'No se pudo firmar el certificado de prueba.');

        $p12 = '';
        self::assertTrue(openssl_pkcs12_export($cert, $p12, $key, $password));

        return $p12;
    }
}
