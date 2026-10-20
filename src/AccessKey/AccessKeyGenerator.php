<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\AccessKey;

use DateTimeInterface;

/**
 * Genera y valida la clave de acceso de 49 dígitos definida en la ficha
 * técnica del SRI (v2.34, sección 5.2).
 *
 * Estructura: ddmmaaaa (8) + tipo comprobante (2) + RUC (13) + ambiente (1)
 * + serie (6) + secuencial (9) + código numérico (8) + tipo emisión (1)
 * + dígito verificador (1).
 */
final class AccessKeyGenerator
{
    private const BASE_LENGTH = 48;

    public function generate(
        DateTimeInterface $fechaEmision,
        string $tipoComprobante,
        string $ruc,
        string|int $ambiente,
        string $serie,
        string $secuencial,
        string $codigoNumerico,
        string|int $tipoEmision = '1',
    ): string {
        $this->assertDigits('tipo de comprobante', $tipoComprobante, 2);
        $this->assertDigits('RUC', $ruc, 13);
        $this->assertDigits('ambiente', (string) $ambiente, 1);
        $this->assertDigits('serie', $serie, 6);
        $this->assertDigits('secuencial', $secuencial, 9);
        $this->assertDigits('código numérico', $codigoNumerico, 8);
        $this->assertDigits('tipo de emisión', (string) $tipoEmision, 1);

        $base = $fechaEmision->format('dmY')
            . $tipoComprobante
            . $ruc
            . $ambiente
            . $serie
            . $secuencial
            . $codigoNumerico
            . $tipoEmision;

        return $base . $this->verifierDigit($base);
    }

    /**
     * Calcula el dígito verificador (módulo 11, pesos 2..7 de derecha a
     * izquierda). 11 => 0 y 10 => 1, conforme a la ficha técnica.
     */
    public function verifierDigit(string $base): int
    {
        $this->assertDigits('base de la clave', $base, self::BASE_LENGTH);

        return self::module11($base);
    }

    /**
     * Implementación pública del ejemplo de la ficha (cadena 41261533 => 6).
     * Acepta cualquier longitud para poder verificar el algoritmo por separado.
     */
    public static function module11(string $digits): int
    {
        if (preg_match('/^\d+$/', $digits) !== 1) {
            throw new \InvalidArgumentException('La cadena para el módulo 11 debe ser numérica.');
        }

        $sum = 0;
        $factor = 2;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum += ((int) $digits[$i]) * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $digit = 11 - ($sum % 11);

        return match ($digit) {
            11 => 0,
            10 => 1,
            default => $digit,
        };
    }

    public function isValid(string $claveAcceso): bool
    {
        if (preg_match('/^\d{49}$/', $claveAcceso) !== 1) {
            return false;
        }

        $base = substr($claveAcceso, 0, self::BASE_LENGTH);
        $expected = $this->verifierDigit($base);

        return (int) $claveAcceso[48] === $expected;
    }

    private function assertDigits(string $field, string $value, int $length): void
    {
        if (preg_match('/^\d{' . $length . '}$/', $value) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('El campo "%s" debe tener exactamente %d dígitos numéricos.', $field, $length)
            );
        }
    }
}
