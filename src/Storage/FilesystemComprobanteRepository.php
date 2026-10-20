<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Storage;

use DateTimeImmutable;
use RuntimeException;

/**
 * Almacenamiento local atómico: XML con permisos 0600 y metadatos JSON.
 * No apto para múltiples servidores; para producción use una base de datos
 * transaccional o almacenamiento de objetos con cifrado.
 */
final readonly class FilesystemComprobanteRepository implements ComprobanteRepository
{
    public function __construct(private string $directory)
    {
        if (trim($this->directory) === '') {
            throw new \InvalidArgumentException('El directorio de almacenamiento es obligatorio.');
        }
    }

    public function guardar(string $claveAcceso, string $xml, string $estado): void
    {
        $this->assertAccessKey($claveAcceso);
        $this->ensureDirectory();

        $xmlPath = $this->path($claveAcceso, 'xml');
        $tmp = tempnam($this->directory, '.tmp-');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal de almacenamiento.');
        }

        try {
            if (file_put_contents($tmp, $xml, LOCK_EX) === false) {
                throw new RuntimeException('No se pudo escribir el XML del comprobante.');
            }
            chmod($tmp, 0o600);
            if (!rename($tmp, $xmlPath)) {
                throw new RuntimeException('No se pudo mover el XML a su ubicación final.');
            }
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }

        $meta = json_encode([
            'claveAcceso' => $claveAcceso,
            'estado' => $estado,
            'actualizadoEn' => (new DateTimeImmutable())->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        if (file_put_contents($this->path($claveAcceso, 'json'), $meta, LOCK_EX) === false) {
            throw new RuntimeException('No se pudo escribir el estado del comprobante.');
        }
    }

    public function obtenerXml(string $claveAcceso): ?string
    {
        $this->assertAccessKey($claveAcceso);
        $path = $this->path($claveAcceso, 'xml');

        if (!is_file($path)) {
            return null;
        }

        $xml = file_get_contents($path);

        return $xml === false ? null : $xml;
    }

    public function obtenerEstado(string $claveAcceso): ?string
    {
        $this->assertAccessKey($claveAcceso);
        $path = $this->path($claveAcceso, 'json');

        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $data = json_decode($contents, true);
        if (!is_array($data) || !isset($data['estado']) || !is_string($data['estado'])) {
            return null;
        }

        return $data['estado'];
    }

    private function path(string $claveAcceso, string $extension): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . $claveAcceso . '.' . $extension;
    }

    private function ensureDirectory(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        if (!mkdir($this->directory, 0o700, true) && !is_dir($this->directory)) {
            throw new RuntimeException("No se pudo crear el directorio: {$this->directory}");
        }
    }

    private function assertAccessKey(string $claveAcceso): void
    {
        if (preg_match('/^\d{49}$/', $claveAcceso) !== 1) {
            throw new \InvalidArgumentException('La clave de acceso debe contener exactamente 49 dígitos.');
        }
    }
}
