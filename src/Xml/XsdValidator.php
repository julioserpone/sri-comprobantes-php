<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Xml;

use DOMDocument;
use JulioSerpone\SriComprobantes\Exception\ConfigurationException;
use JulioSerpone\SriComprobantes\Exception\XmlValidationException;

/**
 * Valida un XML contra un XSD local. No descarga esquemas remotos: los XSD
 * oficiales deben versionarse o montarse de forma controlada.
 */
final class XsdValidator
{
    public function validate(string $xml, string $xsdPath): void
    {
        if (!is_file($xsdPath) || !is_readable($xsdPath)) {
            throw new ConfigurationException("No se puede leer el XSD: {$xsdPath}");
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $document = new DOMDocument();
            if (!$document->loadXML($xml, LIBXML_NONET)) {
                throw new XmlValidationException('El XML no está bien formado: ' . implode('; ', $this->errors()));
            }

            if (!$document->schemaValidate($xsdPath)) {
                throw new XmlValidationException('El XML no valida contra el XSD: ' . implode('; ', $this->errors()));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** @return list<string> */
    public function errors(): array
    {
        $messages = [];
        foreach (libxml_get_errors() as $error) {
            $messages[] = sprintf(
                'línea %d, columna %d: %s',
                $error->line,
                $error->column,
                trim($error->message),
            );
        }

        return $messages;
    }
}
