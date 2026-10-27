<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Comprobante;

/**
 * El XML de un comprobante no se pudo interpretar.
 *
 * El mensaje nunca incluye el contenido del documento: puede contener datos
 * personales. Solo indica el motivo y, cuando aplica, el campo con problemas.
 */
final class ComprobanteException extends \RuntimeException
{
    public static function unreadable(): self
    {
        return new self('El XML del comprobante no es válido.');
    }
    public static function tipoNoSoportado(string $tipo): self
    {
        return new self(sprintf('El tipo de comprobante no está soportado: "%s".', $tipo));
    }
    public static function campoFaltante(string $campo): self
    {
        return new self(sprintf('El XML no contiene el campo obligatorio "%s".', $campo));
    }
    public static function campoInvalido(string $campo): self
    {
        return new self(sprintf('El campo "%s" del XML no tiene un valor válido.', $campo));
    }
    public static function demasiadoGrande(int $bytes, int $limite): self
    {
        return new self(sprintf('El XML supera el tamaño máximo permitido (%d > %d bytes).', $bytes, $limite));
    }
}
