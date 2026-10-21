<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Signature;

use JulioSerpone\SriComprobantes\Exception\SignatureException;

/**
 * Invoca el firmador remoto. El payload contiene el password del PKCS#12 y el
 * XML en base64; no lo registre ni lo persista.
 */
interface LambdaInvoker
{
    /**
     * @param array{tenantUuid: string, password: string, xmlBase64: string} $payload
     * @return array<string, mixed> Respuesta decodificada del firmador.
     *
     * @throws SignatureException Si la invocación o la respuesta fallan.
     */
    public function invoke(#[\SensitiveParameter] array $payload): array;
}
