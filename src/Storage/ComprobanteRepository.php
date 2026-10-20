<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Storage;

/**
 * Persistencia mínima para trazabilidad de envíos. La implementación concreta
 * puede ser filesystem, base de datos o un servicio externo.
 */
interface ComprobanteRepository
{
    public function guardar(string $claveAcceso, string $xml, string $estado): void;

    public function obtenerXml(string $claveAcceso): ?string;

    public function obtenerEstado(string $claveAcceso): ?string;
}
