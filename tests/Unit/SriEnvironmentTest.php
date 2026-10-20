<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Config\SriEnvironment;
use JulioSerpone\SriComprobantes\Config\SriService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SriEnvironment::class)]
#[CoversClass(SriService::class)]
final class SriEnvironmentTest extends TestCase
{
    public function testPruebasEndpoints(): void
    {
        $env = SriEnvironment::PRUEBAS;

        self::assertSame('https://celcer.sri.gob.ec', $env->host());
        self::assertSame(
            'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            $env->wsdl(SriService::RECEPCION),
        );
        self::assertSame(
            'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
            $env->wsdl(SriService::AUTORIZACION),
        );
        self::assertSame(
            'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/ConsultaComprobante?wsdl',
            $env->wsdl(SriService::CONSULTA),
        );
    }

    public function testProduccionEndpoints(): void
    {
        $env = SriEnvironment::PRODUCCION;

        self::assertTrue($env->isProduction());
        self::assertSame('https://cel.sri.gob.ec', $env->host());
        self::assertSame(
            'https://cel.sri.gob.ec/comprobantes-electronicos-ws/ConsultaFactura?wsdl',
            $env->wsdl(SriService::FACTURA_NEGOCIABLE),
        );
    }

    public function testInvalidCodeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SriEnvironment::fromCode('9');
    }
}
