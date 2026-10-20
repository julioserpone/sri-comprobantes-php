<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Support;

/**
 * Utilidades para normalizar la respuesta de SoapClient (stdClass) a arrays.
 * No interpreta reglas de negocio.
 */
final class Arr
{
    /** @return array<string, mixed> */
    public static function toArray(mixed $value): array
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (!is_array($value)) {
            return ['value' => $value];
        }

        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = is_array($item) || is_object($item) ? self::toArray($item) : $item;
        }

        return $result;
    }

    /**
     * Desenvuelve la raíz de la respuesta SOAP, p. ej.
     * ["RespuestaRecepcionComprobante" => [...]].
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function unwrap(array $data, string $root): array
    {
        $value = $data[$root] ?? null;

        return is_array($value) ? $value : $data;
    }

    /**
     * Normaliza un nodo que puede venir como objeto único o como lista.
     *
     * @return list<array<string, mixed>>
     */
    public static function listOf(mixed $value): array
    {
        if (!is_array($value) || $value === []) {
            return [];
        }

        if (array_is_list($value)) {
            return array_map(
                static fn (mixed $item): array => is_array($item) ? $item : self::toArray($item),
                $value
            );
        }

        return [$value];
    }

    /** @param array<string, mixed> $data */
    public static function string(array $data, string $key, ?string $default = null): ?string
    {
        $value = $data[$key] ?? $default;

        return $value === null ? null : (string) $value;
    }
}
