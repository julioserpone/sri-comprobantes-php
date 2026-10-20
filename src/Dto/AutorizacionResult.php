<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Dto;

use JulioSerpone\SriComprobantes\Support\Arr;

final readonly class AutorizacionResult
{
    /**
     * @param list<Autorizacion> $autorizaciones
     */
    public function __construct(
        public ?string $claveAccesoConsultada,
        public int $numeroComprobantes,
        public array $autorizaciones = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $data = Arr::unwrap($data, 'RespuestaAutorizacionComprobante');
        $items = Arr::listOf($data['autorizaciones']['autorizacion'] ?? $data['autorizaciones'] ?? []);

        return new self(
            Arr::string($data, 'claveAccesoConsultada'),
            (int) ($data['numeroComprobantes'] ?? 0),
            array_map(static fn (array $item): Autorizacion => Autorizacion::fromArray($item), $items),
        );
    }

    public function first(): ?Autorizacion
    {
        return $this->autorizaciones[0] ?? null;
    }

    public function estado(): string
    {
        $first = $this->first();

        return $first === null ? 'SIN_RESPUESTA' : $first->estado;
    }

    public function isAutorizado(): bool
    {
        return $this->first()?->isAutorizado() ?? false;
    }

    public function isEnProceso(): bool
    {
        return in_array(strtoupper($this->estado()), ['PPR', 'EN PROCESO', 'RECIBIDA'], true);
    }

    public function xmlAutorizado(): ?string
    {
        return $this->first()?->comprobante;
    }

    /** @return list<Mensaje> */
    public function mensajes(): array
    {
        $messages = [];
        foreach ($this->autorizaciones as $autorizacion) {
            foreach ($autorizacion->mensajes as $mensaje) {
                $messages[] = $mensaje;
            }
        }

        return $messages;
    }
}
