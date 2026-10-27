<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Comprobante\Data;

/**
 * Retención practicada (comprobante de retención, tipo 07).
 */
final class RetencionData
{
    public function __construct(
        public readonly string $codigo,
        public readonly string $codigoRetencion,
        public readonly string $baseImponible,
        public readonly string $porcentajeRetener,
        public readonly int $valorRetenidoCentavos,
        public readonly string $tipoDocumento,
        public readonly string $numeroDocumento,
        public readonly string $fechaEmisionDocumento,
        public readonly int $baseCentavos,
    ) {
    }
}
