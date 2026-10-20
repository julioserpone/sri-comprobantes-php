<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Config;

/**
 * Servicios SOAP publicados por el SRI para el esquema off-line.
 *
 * Los nombres de recurso provienen de la Ficha Técnica de Comprobantes
 * Electrónicos Esquema Off-line v2.34 (julio 2026), secciones 7 y 8.
 */
enum SriService: string
{
    case RECEPCION = 'recepcion';
    case AUTORIZACION = 'autorizacion';
    case CONSULTA = 'consulta';
    case FACTURA_NEGOCIABLE = 'factura_negociable';

    public function resource(): string
    {
        return match ($this) {
            self::RECEPCION => 'RecepcionComprobantesOffline',
            self::AUTORIZACION => 'AutorizacionComprobantesOffline',
            self::CONSULTA => 'ConsultaComprobante',
            self::FACTURA_NEGOCIABLE => 'ConsultaFactura',
        };
    }
}
