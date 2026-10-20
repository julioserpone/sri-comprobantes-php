<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Dto;

use JulioSerpone\SriComprobantes\Error\SriError;
use JulioSerpone\SriComprobantes\Support\Arr;

final readonly class Mensaje
{
    public function __construct(
        public ?string $identificador = null,
        public ?string $mensaje = null,
        public ?string $informacionAdicional = null,
        public ?string $tipo = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            Arr::string($data, 'identificador'),
            Arr::string($data, 'mensaje'),
            Arr::string($data, 'informacionAdicional'),
            Arr::string($data, 'tipo'),
        );
    }

    public function isError(): bool
    {
        return strtoupper((string) $this->tipo) === 'ERROR';
    }

    public function isWarning(): bool
    {
        return strtoupper((string) $this->tipo) === 'ADVERTENCIA';
    }

    public function error(): ?SriError
    {
        return SriError::fromCode($this->identificador);
    }

    public function __toString(): string
    {
        $text = trim((string) $this->mensaje);
        if ($this->identificador !== null && $this->identificador !== '') {
            $text = "[{$this->identificador}] {$text}";
        }
        if ($this->informacionAdicional !== null && $this->informacionAdicional !== '') {
            $text .= ' — ' . $this->informacionAdicional;
        }

        return $text;
    }
}
