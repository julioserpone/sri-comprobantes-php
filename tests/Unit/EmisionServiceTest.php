<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Client\AutorizacionClient;
use JulioSerpone\SriComprobantes\Client\RecepcionClient;
use JulioSerpone\SriComprobantes\Exception\SriException;
use JulioSerpone\SriComprobantes\Retry\RetryPolicy;
use JulioSerpone\SriComprobantes\Service\EmisionService;
use JulioSerpone\SriComprobantes\Tests\Support\FakeTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmisionService::class)]
final class EmisionServiceTest extends TestCase
{
    public function testSendsAndWaitsUntilAuthorized(): void
    {
        $key = str_repeat('4', 49);
        $transport = new FakeTransport([
            ['RespuestaRecepcionComprobante' => ['estado' => 'RECIBIDA']],
            ['RespuestaAutorizacionComprobante' => [
                'claveAccesoConsultada' => $key,
                'numeroComprobantes' => '0',
                'autorizaciones' => null,
            ]],
            ['RespuestaAutorizacionComprobante' => [
                'claveAccesoConsultada' => $key,
                'numeroComprobantes' => '1',
                'autorizaciones' => ['autorizacion' => [
                    'estado' => 'AUTORIZADO',
                    'numeroAutorizacion' => $key,
                    'comprobante' => '<factura autorizada="true"/>',
                ]],
            ]],
        ]);

        $service = new EmisionService(
            new RecepcionClient($transport),
            new AutorizacionClient($transport),
            new RetryPolicy(maxAttempts: 1, baseDelayMs: 0, maxDelayMs: 0),
        );

        $result = $service->emitir('<factura/>', $key, intentos: 3, segundosEntreIntentos: 0);

        self::assertTrue($result->isAutorizado());
        self::assertSame('<factura autorizada="true"/>', $result->xmlAutorizado());
        self::assertCount(3, $transport->calls);
    }

    public function testReturnsLastStateWhenStillInProcess(): void
    {
        $key = str_repeat('5', 49);
        $response = ['RespuestaAutorizacionComprobante' => [
            'claveAccesoConsultada' => $key,
            'numeroComprobantes' => '1',
            'autorizaciones' => ['autorizacion' => ['estado' => 'PPR']],
        ]];
        $transport = new FakeTransport([$response, $response]);
        $service = new EmisionService(
            new RecepcionClient($transport),
            new AutorizacionClient($transport),
            new RetryPolicy(maxAttempts: 1, baseDelayMs: 0, maxDelayMs: 0),
        );

        $result = $service->esperarAutorizacion($key, intentos: 2, segundosEntreIntentos: 0);

        self::assertSame('PPR', $result->estado());
        self::assertFalse($result->isAutorizado());
    }

    public function testThrowsWhenReceptionIsRejected(): void
    {
        $transport = new FakeTransport([[
            'RespuestaRecepcionComprobante' => [
                'estado' => 'DEVUELTA',
                'comprobantes' => ['comprobante' => [
                    'claveAcceso' => str_repeat('6', 49),
                    'mensajes' => ['mensaje' => [
                        'identificador' => '35',
                        'mensaje' => 'XML inválido',
                        'informacionAdicional' => 'detalle',
                        'tipo' => 'ERROR',
                    ]],
                ]],
            ],
        ]]);
        $service = new EmisionService(
            new RecepcionClient($transport),
            new AutorizacionClient($transport),
            new RetryPolicy(maxAttempts: 1, baseDelayMs: 0, maxDelayMs: 0),
        );

        try {
            $service->emitir('<factura/>', str_repeat('6', 49));
            self::fail('Se esperaba SriException.');
        } catch (SriException $exception) {
            self::assertSame('35', $exception->identificador());
            self::assertSame('detalle', $exception->additionalInformation());
        }
    }
}
