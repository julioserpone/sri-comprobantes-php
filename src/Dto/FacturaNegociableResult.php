<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Dto;

use JulioSerpone\SriComprobantes\Support\Arr;

final readonly class FacturaNegociableResult
{
    /**
     * @param list<Mensaje> $mensajes
     */
    public function __construct(
        public ?string $claveAcceso = null,
        public ?string $estadoConfirmacion = null,
        public ?string $estadoConsulta = null,
        public array $mensajes = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $data = Arr::unwrap($data, 'EstadoConfirmacionFacturaComercialNegociable');
        $items = Arr::listOf($data['mensajes']['mensaje'] ?? $data['mensajes'] ?? []);

        return new self(
            Arr::string($data, 'claveAcceso'),
            Arr::string($data, 'estadoConfirmacion'),
            Arr::string($data, 'estadoConsulta'),
            array_map(static fn (array $item): Mensaje => Mensaje::fromArray($item), $items),
        );
    }

    public function isConfirmada(): bool
    {
        return strtoupper((string) $this->estadoConfirmacion) === 'SI';
    }
}
