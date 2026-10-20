<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Transport;

/**
 * Abstracción del transporte SOAP. Permite sustituirlo en pruebas o por
 * otro cliente HTTP sin acoplar los clientes de negocio a ext-soap.
 */
interface Transport
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function call(string $operation, array $arguments): mixed;

    public function lastRequest(): ?string;

    public function lastResponse(): ?string;
}
