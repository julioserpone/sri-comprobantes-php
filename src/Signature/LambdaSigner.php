<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Signature;

use JulioSerpone\SriComprobantes\Exception\SignatureException;

/**
 * Firma un XML con el firmador XAdES-BES desplegado como AWS Lambda.
 *
 * Cada instancia representa a un inquilino: recibe su UUID y el password de su
 * PKCS#12. El password se envía al firmador por la invocación IAM/TLS y no se
 * registra. No reutilice una instancia para otro inquilino.
 */
final readonly class LambdaSigner implements Signer
{
    private const ERROR_MESSAGES = [
        'invalid_request' => 'El firmador rechazó la solicitud por datos inválidos.',
        'payload_too_large' => 'El XML supera el tamaño máximo del firmador.',
        'certificate_unavailable' => 'El certificado del inquilino no está disponible o el password es incorrecto.',
        'signing_failed' => 'El firmador no pudo completar la firma.',
        'internal_error' => 'El firmador remoto tuvo un error interno.',
    ];

    private string $password;

    public function __construct(
        private LambdaInvoker $invoker,
        private string $tenantUuid,
        #[\SensitiveParameter]
        string $password,
    ) {
        $this->password = $password;

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tenantUuid) !== 1) {
            throw new \InvalidArgumentException('El UUID del inquilino no es válido.');
        }

        if ($password === '') {
            throw new \InvalidArgumentException('El password del PKCS#12 es obligatorio.');
        }
    }

    public function sign(string $xml): string
    {
        if (trim($xml) === '') {
            throw new SignatureException('No se puede firmar un XML vacío.');
        }

        $response = $this->invoker->invoke([
            'tenantUuid' => $this->tenantUuid,
            'password' => $this->password,
            'xmlBase64' => base64_encode($xml),
        ]);

        $error = $response['error'] ?? null;
        if (is_string($error) && $error !== '') {
            throw new SignatureException(
                self::ERROR_MESSAGES[$error] ?? 'El firmador remoto rechazó la solicitud.'
            );
        }

        $encoded = $response['signedXmlBase64'] ?? null;
        if (!is_string($encoded) || trim($encoded) === '') {
            throw new SignatureException('El firmador no devolvió un XML firmado.');
        }

        $signed = base64_decode(trim($encoded), true);
        if ($signed === false || trim($signed) === '') {
            throw new SignatureException('El firmador devolvió un XML firmado con base64 inválido.');
        }

        if (preg_match('/<(?:[\w.-]+:)?Signature\b/', $signed) !== 1) {
            throw new SignatureException('El XML devuelto no contiene un elemento Signature.');
        }

        return $signed;
    }

    /**
     * Evita que un var_dump o un logger de depuración muestre el password.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'tenantUuid' => $this->tenantUuid,
            'password' => '[redacted]',
        ];
    }

    /**
     * Evita serializar el objeto con el password en una cola, caché o log.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        throw new \LogicException('No serialice LambdaSigner: contiene el password del PKCS#12.');
    }
}
