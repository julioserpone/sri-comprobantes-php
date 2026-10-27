<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Comprobante\Data;

/**
 * Comprobante recibido ya interpretado.
 *
 * Todos los montos están en centavos (`int`). `totalsMismatch()` indica que
 * el emisor publicó totales que no cuadran: se informan tal cual, sin
 * corregirlos, porque la corrección silenciosa oculta errores fiscales.
 */
final class ComprobanteData
{
    /**
     * @param list<ItemData> $items
     * @param list<ImpuestoData> $impuestos
     * @param list<RetencionData> $retenciones
     * @param list<string> $clavesRelacionadas claves de los documentos modificados
     */
    public function __construct(
        public readonly string $claveAcceso,
        public readonly string $tipo,
        public readonly string $ambiente,
        public readonly string $fechaEmision,
        public readonly string $rucEmisor,
        public readonly string $razonSocialEmisor,
        public readonly string $establecimiento,
        public readonly string $puntoEmision,
        public readonly string $secuencial,
        public readonly int $subtotalCentavos,
        public readonly int $descuentoCentavos,
        public readonly int $impuestoCentavos,
        public readonly int $totalCentavos,
        public readonly array $items = [],
        public readonly array $impuestos = [],
        public readonly array $retenciones = [],
        public readonly array $clavesRelacionadas = [],
        public readonly ?string $rucReceptor = null,
        public readonly ?string $razonSocialReceptor = null,
        public readonly int $iceCentavos = 0,
        public readonly int $irbpnrCentavos = 0,
        public readonly int $propinaCentavos = 0,
        public readonly string $moneda = 'USD',
        public readonly ?string $motivo = null,
        public readonly ?string $numeroAutorizacion = null,
        public readonly ?string $fechaAutorizacion = null,
    ) {
    }
    /**
     * Monto que el comprobante declara como total.
     *
     * Una factura declara `importeTotal`; una nota de crédito o de débito
     * declara únicamente el valor del ajuste en `importeTotal`, así que su
     * cabecera (`totalSinImpuestos`) puede no cuadrar con la fórmula. Antes de
     * marcar una inconsistencia se compara contra lo que el documento declara.
     *
     * Rango válido: total = subtotal − descuento + impuestos + ICE + IRBPNR +
     * propina (los dos extremos admite el SRI cuando hay descuento global).
     */
    public function declaradoEsperadoCentavos(): int
    {
        return $this->subtotalCentavos
            - $this->descuentoCentavos
            + $this->impuestoCentavos
            + $this->iceCentavos
            + $this->irbpnrCentavos
            + $this->propinaCentavos;
    }

    /** total = subtotal − descuento + impuestos + ICE + IRBPNR + propina */
    public function totalEsperadoCentavos(): int
    {
        return $this->declaradoEsperadoCentavos();
    }
    public function totalsMismatch(): bool
    {
        if ($this->totalCentavos === $this->declaradoEsperadoCentavos()) {
            return false;
        }

        // Nota de crédito/débito: `importeTotal` declara solo el ajuste y la
        // cabecera no siempre trae `totalSinImpuestos`. Se exige al menos que
        // el total coincida con alguno de los dos importes del documento.
        if (in_array($this->tipo, ['04', '05'], true)) {
            return $this->totalCentavos !== $this->impuestoCentavos
                && $this->totalCentavos !== ($this->subtotalCentavos + $this->impuestoCentavos);
        }

        return true;
    }
    public function itemsSubtotalCentavos(): int
    {
        return array_sum(array_map(static fn (ItemData $item): int => $item->subtotalCentavos, $this->items));
    }
    public function impuestosTotalCentavos(): int
    {
        return array_sum(array_map(static fn (ImpuestoData $impuesto): int => $impuesto->valorCentavos, $this->impuestos));
    }
}
