<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Client;

use JulioSerpone\SriComprobantes\Dto\ConsultaResult;
use JulioSerpone\SriComprobantes\Dto\FacturaNegociableResult;
use JulioSerpone\SriComprobantes\Support\Arr;
use JulioSerpone\SriComprobantes\Transport\Transport;

/**
 * Cliente de los servicios de consulta del SRI: estado de autorización y
 * estado de confirmación de factura comercial negociable.
 */
final readonly class ConsultaClient
{
    public function __construct(private Transport $transport)
    {
    }

    public function estado(string $claveAcceso): ConsultaResult
    {
        $this->assertAccessKey($claveAcceso);

        $response = $this->transport->call('consultarEstadoAutorizacionComprobante', [
            'claveAcceso' => $claveAcceso,
        ]);

        return ConsultaResult::fromArray(Arr::toArray($response));
    }

    public function facturaNegociable(string $claveAcceso): FacturaNegociableResult
    {
        $this->assertAccessKey($claveAcceso);

        $response = $this->transport->call('consultarEstadoConfirmacionFacturaComercialNegociable', [
            'claveAcceso' => $claveAcceso,
        ]);

        return FacturaNegociableResult::fromArray(Arr::toArray($response));
    }

    private function assertAccessKey(string $claveAcceso): void
    {
        if (preg_match('/^\d{49}$/', $claveAcceso) !== 1) {
            throw new \InvalidArgumentException('La clave de acceso debe contener exactamente 49 dígitos.');
        }
    }
}
