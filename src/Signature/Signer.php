<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Signature;

/**
 * Punto de integración para la firma XAdES-BES exigida por el SRI.
 *
 * Esta versión NO incluye un firmador XAdES-BES propio: implementarlo sin
 * certificación previa contra el SRI es riesgoso (canonicalización, namespaces
 * y SignedProperties son sensibles). Use una implementación validada, un HSM o
 * un servicio de firma, y envuélvala con CallbackSigner.
 */
interface Signer
{
    /**
     * @param string $xml Comprobante sin firma.
     * @return string Comprobante firmado.
     */
    public function sign(string $xml): string;
}
