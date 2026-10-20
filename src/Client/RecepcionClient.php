<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Client;

use JulioSerpone\SriComprobantes\Dto\RecepcionResult;
use JulioSerpone\SriComprobantes\Support\Arr;
use JulioSerpone\SriComprobantes\Transport\Transport;

/**
 * Cliente del servicio RecepcionComprobantesOffline.
 *
 * Operación WSDL: validarComprobante(xml: base64Binary). El XML puede ser un
 * comprobante individual o un lote (ver Xml\LoteBuilder).
 */
final readonly class RecepcionClient
{
    public function __construct(private Transport $transport)
    {
    }

    public function validar(string $xml): RecepcionResult
    {
        if (trim($xml) === '') {
            throw new \InvalidArgumentException('El XML a enviar no puede estar vacío.');
        }

        $response = $this->transport->call('validarComprobante', ['xml' => $xml]);

        return RecepcionResult::fromArray(Arr::toArray($response));
    }
}
