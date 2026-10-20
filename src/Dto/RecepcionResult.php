<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Dto;

use JulioSerpone\SriComprobantes\Support\Arr;

final readonly class RecepcionResult
{
    /**
     * @param list<RecepcionComprobante> $comprobantes
     */
    public function __construct(
        public string $estado,
        public array $comprobantes = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $data = Arr::unwrap($data, 'RespuestaRecepcionComprobante');
        $items = Arr::listOf($data['comprobantes']['comprobante'] ?? $data['comprobantes'] ?? []);

        return new self(
            (string) ($data['estado'] ?? ''),
            array_map(static fn (array $item): RecepcionComprobante => RecepcionComprobante::fromArray($item), $items),
        );
    }

    public function isRecibida(): bool
    {
        return strtoupper($this->estado) === 'RECIBIDA';
    }

    public function isDevuelta(): bool
    {
        return strtoupper($this->estado) === 'DEVUELTA';
    }

    /** @return list<Mensaje> */
    public function mensajes(): array
    {
        $messages = [];
        foreach ($this->comprobantes as $comprobante) {
            foreach ($comprobante->mensajes as $mensaje) {
                $messages[] = $mensaje;
            }
        }

        return $messages;
    }

    public function firstError(): ?Mensaje
    {
        foreach ($this->mensajes() as $mensaje) {
            if ($mensaje->isError()) {
                return $mensaje;
            }
        }

        return null;
    }
}
