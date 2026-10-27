<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Comprobante\Data;

/**
 * Impuesto de cabecera o de detalle.
 *
 * `codigoPorcentaje` es distinto de la tarifa: el formulario 104 se declara
 * por código, no por tarifa, así que ambos se conservan.
 */
final class ImpuestoData
{
    public function __construct(
        public readonly string $codigo,
        public readonly string $codigoPorcentaje,
        public readonly string $tarifa,
        public readonly int $baseCentavos,
        public readonly int $valorCentavos,
    ) {
    }
}
