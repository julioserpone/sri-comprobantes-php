<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Dto\ConsultaResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConsultaResult::class)]
final class ConsultaResultTest extends TestCase
{
    public function testParsesDateAndMessages(): void
    {
        $result = ConsultaResult::fromArray([
            'EstadoAutorizacionComprobante' => [
                'claveAcceso' => str_repeat('1', 49),
                'estadoAutorizacion' => 'AUTORIZADO',
                'fechaAutorizacion' => '2026-10-20T10:00:00.000-05:00',
                'mensajes' => ['mensaje' => [
                    'identificador' => '60',
                    'mensaje' => 'Ambiente de pruebas',
                    'tipo' => 'ADVERTENCIA',
                ]],
            ],
        ]);

        self::assertTrue($result->isAutorizado());
        self::assertNotNull($result->fechaAutorizacion);
        self::assertCount(1, $result->mensajes);
    }

    public function testInvalidDateDoesNotBreakParsing(): void
    {
        $result = ConsultaResult::fromArray([
            'EstadoAutorizacionComprobante' => [
                'claveAcceso' => str_repeat('2', 49),
                'estadoConsulta' => 'RECHAZADA',
                'fechaAutorizacion' => 'fecha-invalida',
            ],
        ]);

        self::assertTrue($result->isRechazada());
        self::assertNull($result->fechaAutorizacion);
    }
}
