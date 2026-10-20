<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Signature;

use DateTimeImmutable;
use JulioSerpone\SriComprobantes\Exception\SignatureException;

/**
 * Carga un certificado PKCS#12 (.p12/.pfx) y expone la clave privada y el
 * certificado en PEM. No almacena la contraseña ni escribe secretos en disco.
 *
 * Trate el PEM de la clave privada como material sensible: no lo loguee, no lo
 * persista y mantenga el proceso con permisos mínimos.
 */
final readonly class P12Certificate
{
    /**
     * @param array<string, mixed> $info
     */
    private function __construct(
        private string $certificatePem,
        private string $privateKeyPem,
        private array $info,
    ) {
    }

    public static function fromFile(string $path, #[\SensitiveParameter] string $password): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new SignatureException("No se puede leer el certificado: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new SignatureException("No se pudo cargar el certificado: {$path}");
        }

        return self::fromContents($contents, $password);
    }

    public static function fromContents(string $contents, #[\SensitiveParameter] string $password): self
    {
        /** @var array{cert?: string, pkey?: string, extracerts?: list<string>} $result */
        $result = [];

        if (!openssl_pkcs12_read($contents, $result, $password)) {
            throw new SignatureException('No se pudo abrir el PKCS#12: contraseña incorrecta o archivo dañado.');
        }

        $certificatePem = $result['cert'] ?? '';
        $privateKeyPem = $result['pkey'] ?? '';

        if ($certificatePem === '' || $privateKeyPem === '') {
            throw new SignatureException('El PKCS#12 no contiene certificado y clave privada.');
        }

        $info = openssl_x509_parse($certificatePem);
        if ($info === false) {
            throw new SignatureException('No se pudo interpretar el certificado X.509.');
        }

        if (!openssl_x509_check_private_key($certificatePem, $privateKeyPem)) {
            throw new SignatureException('La clave privada no corresponde al certificado.');
        }

        return new self($certificatePem, $privateKeyPem, $info);
    }

    public function certificatePem(): string
    {
        return $this->certificatePem;
    }

    public function privateKeyPem(): string
    {
        return $this->privateKeyPem;
    }

    /** @return array<string, mixed> */
    public function info(): array
    {
        return $this->info;
    }

    public function subject(): string
    {
        return (string) ($this->info['name'] ?? '');
    }

    public function issuer(): string
    {
        return (string) ($this->info['issuer']['CN'] ?? '');
    }

    public function serialNumber(): string
    {
        return (string) ($this->info['serialNumber'] ?? '');
    }

    public function validFrom(): DateTimeImmutable
    {
        return $this->timestamp('validFrom_time_t');
    }

    public function validTo(): DateTimeImmutable
    {
        return $this->timestamp('validTo_time_t');
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        return $this->validTo() < ($now ?? new DateTimeImmutable());
    }

    public function daysUntilExpiry(?DateTimeImmutable $now = null): int
    {
        $seconds = $this->validTo()->getTimestamp() - ($now ?? new DateTimeImmutable())->getTimestamp();

        return (int) floor($seconds / 86400);
    }

    private function timestamp(string $key): DateTimeImmutable
    {
        $value = $this->info[$key] ?? null;
        if (!is_int($value) && !is_numeric($value)) {
            throw new SignatureException("El certificado no contiene la fecha {$key}.");
        }

        return (new DateTimeImmutable())->setTimestamp((int) $value);
    }
}
