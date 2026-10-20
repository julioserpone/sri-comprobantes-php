<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Exception;

use JulioSerpone\SriComprobantes\Error\SriError;

/**
 * Error de negocio devuelto por el SRI, con el código y el texto originales.
 */
final class SriException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?SriError $error = null,
        private readonly ?string $identificador = null,
        private readonly ?string $additionalInformation = null,
    ) {
        parent::__construct($message);
    }

    public static function fromMessage(
        ?string $identificador,
        ?string $mensaje,
        ?string $informacionAdicional = null,
    ): self {
        $error = SriError::fromCode($identificador);
        $message = trim(($mensaje ?? 'Error del SRI') . ($error ? " [{$error->value}]" : ''));

        return new self($message, $error, $identificador, $informacionAdicional);
    }

    public function error(): ?SriError
    {
        return $this->error;
    }

    public function identificador(): ?string
    {
        return $this->identificador;
    }

    public function additionalInformation(): ?string
    {
        return $this->additionalInformation;
    }
}
