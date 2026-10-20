<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Dto;

use DateTimeImmutable;
use JulioSerpone\SriComprobantes\Support\Arr;

final readonly class ConsultaResult
{
    /**
     * @param list<Mensaje> $mensajes
     */
    public function __construct(
        public ?string $claveAcceso = null,
        public ?string $estadoAutorizacion = null,
        public ?string $estadoConsulta = null,
        public ?string $tipoComprobante = null,
        public ?string $rucEmisor = null,
        public ?DateTimeImmutable $fechaAutorizacion = null,
        public array $mensajes = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $data = Arr::unwrap($data, 'EstadoAutorizacionComprobante');
        $items = Arr::listOf($data['mensajes']['mensaje'] ?? $data['mensajes'] ?? []);

        return new self(
            Arr::string($data, 'claveAcceso'),
            Arr::string($data, 'estadoAutorizacion'),
            Arr::string($data, 'estadoConsulta'),
            Arr::string($data, 'tipoComprobante'),
            Arr::string($data, 'rucEmisor'),
            self::parseDate(Arr::string($data, 'fechaAutorizacion')),
            array_map(static fn (array $item): Mensaje => Mensaje::fromArray($item), $items),
        );
    }

    public function isAutorizado(): bool
    {
        return strtoupper((string) $this->estadoAutorizacion) === 'AUTORIZADO';
    }

    public function isRechazada(): bool
    {
        return strtoupper((string) $this->estadoConsulta) === 'RECHAZADA';
    }

    private static function parseDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
