<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Client;

use JulioSerpone\SriComprobantes\Dto\AutorizacionResult;
use JulioSerpone\SriComprobantes\Dto\LoteAutorizacionResult;
use JulioSerpone\SriComprobantes\Support\Arr;
use JulioSerpone\SriComprobantes\Transport\Transport;

/**
 * Cliente del servicio AutorizacionComprobantesOffline.
 */
final readonly class AutorizacionClient
{
    public function __construct(private Transport $transport)
    {
    }

    public function consultar(string $claveAcceso): AutorizacionResult
    {
        $this->assertAccessKey($claveAcceso);

        $response = $this->transport->call('autorizacionComprobante', [
            'claveAccesoComprobante' => $claveAcceso,
        ]);

        return AutorizacionResult::fromArray(Arr::toArray($response));
    }

    public function consultarLote(string $claveAccesoLote): LoteAutorizacionResult
    {
        $this->assertAccessKey($claveAccesoLote);

        $response = $this->transport->call('autorizacionComprobanteLote', [
            'claveAccesoLote' => $claveAccesoLote,
        ]);

        return LoteAutorizacionResult::fromArray(Arr::toArray($response));
    }

    private function assertAccessKey(string $claveAcceso): void
    {
        if (preg_match('/^\d{49}$/', $claveAcceso) !== 1) {
            throw new \InvalidArgumentException('La clave de acceso debe contener exactamente 49 dígitos.');
        }
    }
}
