<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Dto;

use JulioSerpone\SriComprobantes\Support\Arr;

final readonly class RecepcionComprobante
{
    /**
     * @param list<Mensaje> $mensajes
     */
    public function __construct(
        public ?string $claveAcceso,
        public array $mensajes = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $items = Arr::listOf($data['mensajes']['mensaje'] ?? $data['mensajes'] ?? []);

        return new self(
            Arr::string($data, 'claveAcceso'),
            array_map(static fn (array $item): Mensaje => Mensaje::fromArray($item), $items),
        );
    }
}
