<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Dto;

use DateTimeImmutable;
use JulioSerpone\SriComprobantes\Support\Arr;

final readonly class Autorizacion
{
    /**
     * @param list<Mensaje> $mensajes
     */
    public function __construct(
        public string $estado,
        public ?string $numeroAutorizacion = null,
        public ?DateTimeImmutable $fechaAutorizacion = null,
        public ?string $ambiente = null,
        public ?string $comprobante = null,
        public array $mensajes = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $items = Arr::listOf($data['mensajes']['mensaje'] ?? $data['mensajes'] ?? []);

        return new self(
            (string) ($data['estado'] ?? ''),
            Arr::string($data, 'numeroAutorizacion'),
            self::parseDate(Arr::string($data, 'fechaAutorizacion')),
            Arr::string($data, 'ambiente'),
            Arr::string($data, 'comprobante'),
            array_map(static fn (array $item): Mensaje => Mensaje::fromArray($item), $items),
        );
    }

    public function isAutorizado(): bool
    {
        return strtoupper($this->estado) === 'AUTORIZADO';
    }

    public function isRechazado(): bool
    {
        return in_array(strtoupper($this->estado), ['RECHAZADO', 'NO AUTORIZADO', 'NAT'], true);
    }

    private static function parseDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
