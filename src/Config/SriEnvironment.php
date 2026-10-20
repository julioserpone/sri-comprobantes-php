<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Config;

/**
 * Ambientes del SRI. El código coincide con el dígito de ambiente de la
 * clave de acceso: 1 = pruebas, 2 = producción.
 */
enum SriEnvironment: string
{
    case PRUEBAS = '1';
    case PRODUCCION = '2';

    public function host(): string
    {
        return match ($this) {
            self::PRUEBAS => 'https://celcer.sri.gob.ec',
            self::PRODUCCION => 'https://cel.sri.gob.ec',
        };
    }

    public function wsdl(SriService $service): string
    {
        return $this->host() . '/comprobantes-electronicos-ws/' . $service->resource() . '?wsdl';
    }

    public function label(): string
    {
        return match ($this) {
            self::PRUEBAS => 'PRUEBAS',
            self::PRODUCCION => 'PRODUCCION',
        };
    }

    public function isProduction(): bool
    {
        return $this === self::PRODUCCION;
    }

    public static function fromCode(string $code): self
    {
        return self::tryFrom($code)
            ?? throw new \InvalidArgumentException("Código de ambiente no válido: {$code}");
    }
}
