<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Signature;

use JulioSerpone\SriComprobantes\Exception\SignatureException;

/**
 * Adapta cualquier firmador externo (HSM, servicio, librería certificada) a
 * la interfaz Signer.
 */
final readonly class CallbackSigner implements Signer
{
    private \Closure $signer;

    /**
     * @param callable(string): mixed $signer Recibe XML y devuelve XML firmado.
     */
    public function __construct(callable $signer)
    {
        $this->signer = $signer(...);
    }

    public function sign(string $xml): string
    {
        if (trim($xml) === '') {
            throw new SignatureException('No se puede firmar un XML vacío.');
        }

        $signed = ($this->signer)($xml);

        if (!is_string($signed) || trim($signed) === '') {
            throw new SignatureException('El firmador no devolvió un XML firmado.');
        }

        if (preg_match('/<(?:[\w.-]+:)?Signature\b/', $signed) !== 1) {
            throw new SignatureException('El XML devuelto no contiene un elemento Signature.');
        }

        return $signed;
    }
}
