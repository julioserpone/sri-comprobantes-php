<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Dto\RecepcionResult;
use JulioSerpone\SriComprobantes\Error\SriError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecepcionResult::class)]
final class RecepcionResultTest extends TestCase
{
    public function testParsesSingleMessageAsObject(): void
    {
        $result = RecepcionResult::fromArray([
            'RespuestaRecepcionComprobante' => [
                'estado' => 'DEVUELTA',
                'comprobantes' => [
                    'comprobante' => [
                        'claveAcceso' => str_repeat('1', 49),
                        'mensajes' => [
                            'mensaje' => [
                                'identificador' => '35',
                                'mensaje' => 'XML no válido',
                                'informacionAdicional' => 'ERROR',
                                'tipo' => 'ERROR',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertTrue($result->isDevuelta());
        self::assertFalse($result->isRecibida());
        self::assertCount(1, $result->comprobantes);
        self::assertSame(str_repeat('1', 49), $result->comprobantes[0]->claveAcceso);
        self::assertSame(SriError::DOCUMENTO_INVALIDO, $result->firstError()?->error());
    }

    public function testParsesMultipleMessagesAsList(): void
    {
        $result = RecepcionResult::fromArray([
            'RespuestaRecepcionComprobante' => [
                'estado' => 'RECIBIDA',
                'comprobantes' => null,
            ],
        ]);

        self::assertTrue($result->isRecibida());
        self::assertSame([], $result->mensajes());
        self::assertNull($result->firstError());
    }
}
