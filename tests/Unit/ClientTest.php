<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Client\AutorizacionClient;
use JulioSerpone\SriComprobantes\Client\ConsultaClient;
use JulioSerpone\SriComprobantes\Client\RecepcionClient;
use JulioSerpone\SriComprobantes\Tests\Support\FakeTransport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecepcionClient::class)]
#[CoversClass(AutorizacionClient::class)]
#[CoversClass(ConsultaClient::class)]
final class ClientTest extends TestCase
{
    public function testRecepcionSendsXmlAndParsesResponse(): void
    {
        $transport = new FakeTransport([[
            'RespuestaRecepcionComprobante' => [
                'estado' => 'RECIBIDA',
                'comprobantes' => null,
            ],
        ]]);

        $result = (new RecepcionClient($transport))->validar('<factura/>');

        self::assertTrue($result->isRecibida());
        self::assertSame('validarComprobante', $transport->calls[0]['operation']);
        self::assertSame(['xml' => '<factura/>'], $transport->calls[0]['arguments']);
    }

    public function testRecepcionRejectsEmptyXml(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new RecepcionClient(new FakeTransport()))->validar('   ');
    }

    public function testAutorizacionParsesSingleAuthorization(): void
    {
        $key = str_repeat('1', 49);
        $transport = new FakeTransport([[
            'RespuestaAutorizacionComprobante' => [
                'claveAccesoConsultada' => $key,
                'numeroComprobantes' => '1',
                'autorizaciones' => [
                    'autorizacion' => [
                        'estado' => 'AUTORIZADO',
                        'numeroAutorizacion' => $key,
                        'fechaAutorizacion' => '2026-10-20T10:00:00.000-05:00',
                        'ambiente' => 'PRUEBAS',
                        'comprobante' => '<factura/>',
                        'mensajes' => null,
                    ],
                ],
            ],
        ]]);

        $result = (new AutorizacionClient($transport))->consultar($key);

        self::assertTrue($result->isAutorizado());
        self::assertSame('<factura/>', $result->xmlAutorizado());
        self::assertSame('autorizacionComprobante', $transport->calls[0]['operation']);
        self::assertSame(['claveAccesoComprobante' => $key], $transport->calls[0]['arguments']);
        self::assertNotNull($result->first()?->fechaAutorizacion);
    }

    public function testAutorizacionRejectsInvalidKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new AutorizacionClient(new FakeTransport()))->consultar('123');
    }

    public function testLoteUsesTheBatchRootElement(): void
    {
        $key = str_repeat('2', 49);
        $transport = new FakeTransport([[
            'RespuestaAutorizacionLote' => [
                'claveAccesoLoteConsultada' => $key,
                'numeroComprobantesLote' => '1',
                'autorizaciones' => [
                    'autorizacion' => [
                        'estado' => 'AUTORIZADO',
                        'comprobante' => '<factura/>',
                    ],
                ],
            ],
        ]]);

        $result = (new AutorizacionClient($transport))->consultarLote($key);

        self::assertSame($key, $result->claveAccesoLoteConsultada);
        self::assertSame(1, $result->numeroComprobantesLote);
        self::assertCount(1, $result->autorizaciones);
        self::assertSame('autorizacionComprobanteLote', $transport->calls[0]['operation']);
    }

    public function testConsultasUseTheirWsdlOperationNames(): void
    {
        $key = str_repeat('3', 49);
        $transport = new FakeTransport([
            [
                'EstadoAutorizacionComprobante' => [
                    'claveAcceso' => $key,
                    'estadoAutorizacion' => 'AUTORIZADO',
                    'estadoConsulta' => 'AUTORIZADO',
                    'mensajes' => null,
                ],
            ],
            [
                'EstadoConfirmacionFacturaComercialNegociable' => [
                    'claveAcceso' => $key,
                    'estadoConfirmacion' => 'SI',
                    'estadoConsulta' => 'CONFIRMADA',
                    'mensajes' => null,
                ],
            ],
        ]);

        $client = new ConsultaClient($transport);
        $estado = $client->estado($key);
        $factura = $client->facturaNegociable($key);

        self::assertTrue($estado->isAutorizado());
        self::assertTrue($factura->isConfirmada());
        self::assertSame('consultarEstadoAutorizacionComprobante', $transport->calls[0]['operation']);
        self::assertSame('consultarEstadoConfirmacionFacturaComercialNegociable', $transport->calls[1]['operation']);
        self::assertSame(['claveAcceso' => $key], $transport->calls[1]['arguments']);
    }
}
