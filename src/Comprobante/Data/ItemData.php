<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Comprobante\Data;

/**
 * Línea de detalle.
 *
 * `descripcionNormalizada` (sin acentos, mayúsculas y espacios colapsados) se
 * calcula aquí porque es la clave de las reglas de clasificación y de la
 * caché de IA de los consumidores.
 */
final class ItemData
{
    public function __construct(
        public readonly int $numeroLinea,
        public readonly string $descripcion,
        public readonly string $descripcionNormalizada,
        public readonly string $cantidad,
        public readonly string $precioUnitario,
        public readonly string $descuento,
        public readonly int $subtotalCentavos,
        public readonly int $totalCentavos,
        public readonly ?string $codigoPrincipal = null,
        public readonly ?string $codigoAuxiliar = null,
    ) {
    }
}
