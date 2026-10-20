<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Dto;

use JulioSerpone\SriComprobantes\Support\Arr;

/**
 * Respuesta de autorizacionComprobanteLote. La clave consultada es la clave
 * del lote, no la de cada comprobante.
 */
final readonly class LoteAutorizacionResult
{
    /**
     * @param list<Autorizacion> $autorizaciones
     */
    public function __construct(
        public ?string $claveAccesoLoteConsultada,
        public int $numeroComprobantesLote,
        public array $autorizaciones = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $data = Arr::unwrap($data, 'RespuestaAutorizacionLote');
        $items = Arr::listOf($data['autorizaciones']['autorizacion'] ?? $data['autorizaciones'] ?? []);

        return new self(
            Arr::string($data, 'claveAccesoLoteConsultada'),
            (int) ($data['numeroComprobantesLote'] ?? 0),
            array_map(static fn (array $item): Autorizacion => Autorizacion::fromArray($item), $items),
        );
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
