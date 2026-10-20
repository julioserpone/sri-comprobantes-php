<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Xml;

use JulioSerpone\SriComprobantes\AccessKey\AccessKeyGenerator;

/**
 * Construye el XML de un lote de comprobantes según la Tabla 8 de la ficha
 * técnica v2.34. El lote se envía por el mismo método validarComprobante.
 */
final readonly class LoteBuilder
{
    public const MAX_COMPROBANTES = 50;

    public const MAX_BYTES = 512_000; // 500 KB

    public function __construct(private AccessKeyGenerator $accessKeys = new AccessKeyGenerator())
    {
    }

    /**
     * @param list<string> $comprobantes XML firmados, sin declaración XML.
     */
    public function build(string $claveAccesoLote, string $ruc, array $comprobantes): string
    {
        if (!$this->accessKeys->isValid($claveAccesoLote)) {
            throw new \InvalidArgumentException('La clave de acceso del lote no es válida (49 dígitos y módulo 11).');
        }

        if (preg_match('/^\d{13}$/', $ruc) !== 1) {
            throw new \InvalidArgumentException('El RUC debe contener exactamente 13 dígitos.');
        }

        if ($comprobantes === []) {
            throw new \InvalidArgumentException('El lote debe contener al menos un comprobante.');
        }

        if (count($comprobantes) > self::MAX_COMPROBANTES) {
            throw new \InvalidArgumentException(
                sprintf('El lote supera el máximo de %d comprobantes.', self::MAX_COMPROBANTES)
            );
        }

        $items = '';
        foreach ($comprobantes as $comprobante) {
            $comprobante = trim($comprobante);
            if ($comprobante === '') {
                throw new \InvalidArgumentException('Un comprobante del lote está vacío.');
            }

            $items .= '<comprobante><![CDATA[' . self::escapeCdata($comprobante) . ']]></comprobante>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<lote version="1.0.0">'
            . '<claveAcceso>' . $claveAccesoLote . '</claveAcceso>'
            . '<ruc>' . $ruc . '</ruc>'
            . '<comprobantes>' . $items . '</comprobantes>'
            . '</lote>';

        if (strlen($xml) > self::MAX_BYTES) {
            throw new \InvalidArgumentException(
                sprintf('El lote supera el tamaño máximo de %d bytes.', self::MAX_BYTES)
            );
        }

        return $xml;
    }

    /**
     * Un CDATA no puede contener la secuencia "]]>". Se divide en dos secciones
     * CDATA contiguas, técnica estándar y válida en XML 1.0.
     */
    private static function escapeCdata(string $value): string
    {
        return str_replace(']]>', ']]]]><![CDATA[>', $value);
    }
}
