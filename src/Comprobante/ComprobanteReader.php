<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Comprobante;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use JulioSerpone\SriComprobantes\AccessKey\AccessKeyGenerator;
use JulioSerpone\SriComprobantes\Comprobante\Data\ComprobanteData;
use JulioSerpone\SriComprobantes\Comprobante\Data\ImpuestoData;
use JulioSerpone\SriComprobantes\Comprobante\Data\ItemData;
use JulioSerpone\SriComprobantes\Comprobante\Data\RetencionData;

/**
 * Lector de comprobantes electrónicos recibidos del SRI.
 *
 * Contraparte de `LoteBuilder`: interpreta el XML autorizado que devuelve la
 * consulta por clave de acceso. Soportado:
 *
 * | Código | Comprobante |
 * |---|---|
 * | 01 | Factura |
 * | 03 | Liquidación de compra |
 * | 04 | Nota de crédito |
 * | 05 | Nota de débito |
 * | 06 | Guía de remisión |
 * | 07 | Comprobante de retención |
 *
 * Seguridad: `LIBXML_NONET`, sin `LIBXML_NOENT` y sin DTD, de modo que las
 * entidades externas no se resuelven (XXE). Los montos se convierten a
 * centavos con aritmética de cadenas, nunca con `float`.
 */
final class ComprobanteReader
{
    public const MAX_BYTES = 5242880;

    /** Nodo raíz => [tipo, nodo de información] */
    private const TIPOS = [
        'factura' => ['01', 'infoFactura'],
        'liquidacionCompra' => ['03', 'infoLiquidacionCompra'],
        'notaCredito' => ['04', 'infoNotaCredito'],
        'notaDebito' => ['05', 'infoNotaDebito'],
        'guiaRemision' => ['06', 'infoGuiaRemision'],
        'comprobanteRetencion' => ['07', 'infoCompRetencion'],
    ];

    private const MAPA_ACENTOS = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
        'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
        'ñ' => 'n', 'Ñ' => 'N',
    ];

    public function read(string $xml): ComprobanteData
    {
        if (strlen($xml) > self::MAX_BYTES) {
            throw ComprobanteException::demasiadoGrande(strlen($xml), self::MAX_BYTES);
        }

        $documento = $this->cargar($xml);
        $raiz = $documento->documentElement;

        if ($raiz === null) {
            throw ComprobanteException::unreadable();
        }

        $nombreRaiz = (string) $raiz->localName;
        $definicion = self::TIPOS[$nombreRaiz] ?? null;

        if ($definicion === null) {
            throw ComprobanteException::tipoNoSoportado($nombreRaiz);
        }

        [$tipo, $nodoInfo] = $definicion;
        $xpath = new DOMXPath($documento);

        $infoTributaria = $this->requerir($xpath, 'infoTributaria');
        $info = $this->requerir($xpath, $nodoInfo);

        $claveAcceso = $this->texto($xpath, 'claveAcceso', $infoTributaria);
        $this->validarClave($claveAcceso);

        $items = $this->items($xpath);
        $impuestos = $this->impuestos($xpath, $info);

        return new ComprobanteData(
            claveAcceso: $claveAcceso,
            tipo: $tipo,
            ambiente: $this->texto($xpath, 'ambiente', $infoTributaria),
            fechaEmision: $this->fecha(
                $this->textoOpcional($xpath, 'fechaEmision', $info)
                ?? $this->textoOpcional($xpath, 'fechaIniTransporte', $info),
            ),
            rucEmisor: $this->texto($xpath, 'ruc', $infoTributaria),
            razonSocialEmisor: $this->texto($xpath, 'razonSocial', $infoTributaria),
            establecimiento: $this->texto($xpath, 'estab', $infoTributaria),
            puntoEmision: $this->texto($xpath, 'ptoEmi', $infoTributaria),
            secuencial: $this->texto($xpath, 'secuencial', $infoTributaria),
            subtotalCentavos: $tipo === '07'
                ? $this->retencionesTotalCentavos($xpath)
                : ($this->centavosOpcional($xpath, 'totalSinImpuestos', $info)
                    ?: $this->centavosOpcional($xpath, 'totalSinImpuestos', $this->opcional($xpath, 'infoNotaModificada'))),
            descuentoCentavos: $this->centavosOpcional($xpath, 'totalDescuento', $info),
            impuestoCentavos: array_sum(array_map(
                static fn (ImpuestoData $impuesto): int => $impuesto->valorCentavos,
                $impuestos,
            )),
            totalCentavos: $tipo === '07'
                ? $this->retencionesTotalCentavos($xpath)
                : $this->centavosOpcional($xpath, 'importeTotal', $info),
            items: $items,
            impuestos: $impuestos,
            retenciones: $tipo === '07' ? $this->retenciones($xpath) : [],
            clavesRelacionadas: $this->clavesRelacionadas($xpath),
            rucReceptor: $this->textoOpcional($xpath, 'identificacionComprador', $info)
                ?? $this->textoOpcional($xpath, 'identificacionProveedor', $info)
                ?? $this->textoOpcional($xpath, 'identificacionDestinatario'),
            razonSocialReceptor: $this->textoOpcional($xpath, 'razonSocialComprador', $info)
                ?? $this->textoOpcional($xpath, 'razonSocialProveedor', $info)
                ?? $this->textoOpcional($xpath, 'razonSocialDestinatario'),
            iceCentavos: $this->centavosOpcional($xpath, 'totalIce', $info),
            irbpnrCentavos: $this->centavosOpcional($xpath, 'totalIRBPNR', $info),
            propinaCentavos: $this->centavosOpcional($xpath, 'propina', $info),
            moneda: $this->textoOpcional($xpath, 'moneda', $info) ?? 'USD',
            motivo: $this->textoOpcional($xpath, 'motivo', $info),
            numeroAutorizacion: $this->textoOpcional($xpath, 'numeroAutorizacion'),
            fechaAutorizacion: $this->fechaHoraOpcional($xpath),
        );
    }

    /**
     * Comprobantes con `<detalles><detalle>` (factura, liquidación, NC, ND y
     * guía de remisión: la guía usa `detalles/detalle` con `cantidad` y
     * `descripcion`).
     *
     * @return list<ItemData>
     */
    private function items(DOMXPath $xpath): array
    {
        $items = [];
        $numero = 0;

        foreach ($this->nodos($xpath, 'detalle', $this->opcional($xpath, 'detalles')) as $detalle) {
            $numero++;
            $descripcion = $this->textoOpcional($xpath, 'descripcion', $detalle) ?? '';
            $subtotal = $this->centavosOpcional($xpath, 'precioTotalSinImpuesto', $detalle);
            $impuestos = $this->impuestos($xpath, $detalle);

            $items[] = new ItemData(
                numeroLinea: $numero,
                descripcion: $descripcion,
                descripcionNormalizada: $this->normalizar($descripcion),
                cantidad: $this->textoOpcional($xpath, 'cantidad', $detalle) ?? '0',
                precioUnitario: $this->textoOpcional($xpath, 'precioUnitario', $detalle) ?? '0',
                descuento: $this->textoOpcional($xpath, 'descuento', $detalle) ?? '0',
                subtotalCentavos: $subtotal,
                totalCentavos: $subtotal + array_sum(array_map(
                    static fn (ImpuestoData $impuesto): int => $impuesto->valorCentavos,
                    $impuestos,
                )),
                codigoPrincipal: $this->textoOpcional($xpath, 'codigoPrincipal', $detalle)
                    ?? $this->textoOpcional($xpath, 'codigoInterno', $detalle),
                codigoAuxiliar: $this->textoOpcional($xpath, 'codigoAuxiliar', $detalle)
                    ?? $this->textoOpcional($xpath, 'codigoAdicional', $detalle),
            );
        }

        return $items;
    }

    /**
     * En la cabecera los impuestos cuelgan de `totalConImpuestos`; en cada
     * línea, de `impuestos`.
     *
     * @return list<ImpuestoData>
     */
    private function impuestos(DOMXPath $xpath, ?DOMElement $contexto): array
    {
        $contenedor = $this->opcional($xpath, 'totalConImpuestos', $contexto);
        $nodo = 'totalImpuesto';

        if ($contenedor === null) {
            $contenedor = $this->opcional($xpath, 'impuestos', $contexto);
            $nodo = 'impuesto';
        }

        $impuestos = [];

        foreach ($this->nodos($xpath, $nodo, $contenedor) as $impuesto) {
            $impuestos[] = new ImpuestoData(
                codigo: $this->texto($xpath, 'codigo', $impuesto),
                codigoPorcentaje: $this->texto($xpath, 'codigoPorcentaje', $impuesto),
                tarifa: $this->textoOpcional($xpath, 'tarifa', $impuesto) ?? '0',
                baseCentavos: $this->centavosOpcional($xpath, 'baseImponible', $impuesto),
                valorCentavos: $this->centavosOpcional($xpath, 'valor', $impuesto),
            );
        }

        return $impuestos;
    }

    /**
     * @return list<RetencionData>
     */
    private function retenciones(DOMXPath $xpath): array
    {
        $retenciones = [];

        foreach ($this->nodos($xpath, 'impuesto', $this->opcional($xpath, 'impuestos')) as $retencion) {
            $retenciones[] = new RetencionData(
                codigo: $this->texto($xpath, 'codigo', $retencion),
                codigoRetencion: $this->texto($xpath, 'codigoRetencion', $retencion),
                baseImponible: $this->textoOpcional($xpath, 'baseImponible', $retencion) ?? '0',
                porcentajeRetener: $this->texto($xpath, 'porcentajeRetener', $retencion),
                valorRetenidoCentavos: $this->centavosOpcional($xpath, 'valorRetenido', $retencion),
                tipoDocumento: $this->textoOpcional($xpath, 'codDocSustento', $retencion) ?? '',
                numeroDocumento: $this->textoOpcional($xpath, 'numDocSustento', $retencion) ?? '',
                fechaEmisionDocumento: $this->fechaOpcional($xpath, 'fechaEmisionDocSustento', $retencion),
                baseCentavos: $this->centavosOpcional($xpath, 'baseImponible', $retencion),
            );
        }

        return $retenciones;
    }

    private function retencionesTotalCentavos(DOMXPath $xpath): int
    {
        return array_sum(array_map(
            static fn (RetencionData $retencion): int => $retencion->valorRetenidoCentavos,
            $this->retenciones($xpath),
        ));
    }

    /**
     * @return list<string>
     */
    private function clavesRelacionadas(DOMXPath $xpath): array
    {
        $claves = [];

        foreach ($this->nodos($xpath, 'numDocModificado', $this->opcional($xpath, 'infoNotaModificada')) as $nodo) {
            $valor = trim($nodo->textContent);

            if ($valor !== '') {
                $claves[] = $valor;
            }
        }

        return $claves;
    }

    private function validarClave(string $clave): void
    {
        if (preg_match('/^\d{49}$/', $clave) !== 1) {
            throw ComprobanteException::campoInvalido('claveAcceso');
        }

        if (!(new AccessKeyGenerator())->isValid($clave)) {
            throw ComprobanteException::campoInvalido('claveAcceso');
        }
    }

    private function cargar(string $xml): DOMDocument
    {
        if (trim($xml) === '') {
            throw ComprobanteException::unreadable();
        }

        $estado = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $documento = new DOMDocument();
        $documento->resolveExternals = false;
        $documento->substituteEntities = false;
        $cargado = $documento->loadXML($xml, LIBXML_NONET);

        libxml_clear_errors();
        libxml_use_internal_errors($estado);

        if (!$cargado) {
            throw ComprobanteException::unreadable();
        }

        return $documento;
    }

    private function requerir(DOMXPath $xpath, string $nombre): DOMElement
    {
        $nodo = $this->opcional($xpath, $nombre);

        if ($nodo === null) {
            throw ComprobanteException::campoFaltante($nombre);
        }

        return $nodo;
    }

    /**
     * Busca por nombre local para no depender del `xmlns` que declare la raíz
     * (hay comprobantes del SRI con y sin namespace).
     */
    private function opcional(DOMXPath $xpath, string $nombre, ?DOMElement $contexto = null): ?DOMElement
    {
        $nodo = $this->nodoHijo($xpath, $nombre, $contexto);

        return $nodo instanceof DOMElement ? $nodo : null;
    }

    private function nodoHijo(DOMXPath $xpath, string $nombre, ?DOMElement $contexto): ?DOMNode
    {
        $consulta = $contexto === null
            ? sprintf('//*[local-name()="%s"]', $nombre)
            : sprintf('*[local-name()="%s"]', $nombre);

        $nodos = $contexto === null ? $xpath->query($consulta) : $xpath->query($consulta, $contexto);

        if ($nodos === false) {
            return null;
        }

        $nodo = $nodos->item(0);

        return $nodo instanceof DOMNode ? $nodo : null;
    }

    /**
     * @return list<DOMElement>
     */
    private function nodos(DOMXPath $xpath, string $nombre, ?DOMElement $contexto): array
    {
        if ($contexto === null) {
            return [];
        }

        $nodos = $xpath->query(sprintf('*[local-name()="%s"]', $nombre), $contexto);
        $resultado = [];

        if ($nodos === false) {
            return $resultado;
        }

        foreach ($nodos as $nodo) {
            if ($nodo instanceof DOMElement) {
                $resultado[] = $nodo;
            }
        }

        return $resultado;
    }

    private function texto(DOMXPath $xpath, string $nombre, ?DOMElement $contexto): string
    {
        $valor = $this->textoOpcional($xpath, $nombre, $contexto);

        if ($valor === null) {
            throw ComprobanteException::campoFaltante($nombre);
        }

        return $valor;
    }

    private function textoOpcional(DOMXPath $xpath, string $nombre, ?DOMElement $contexto = null): ?string
    {
        $nodo = $this->nodoHijo($xpath, $nombre, $contexto);

        if ($nodo === null) {
            return null;
        }

        $valor = trim($nodo->textContent);

        return $valor === '' ? null : $valor;
    }

    private function centavosOpcional(DOMXPath $xpath, string $nombre, ?DOMElement $contexto): int
    {
        $valor = $this->textoOpcional($xpath, $nombre, $contexto);

        return $valor === null ? 0 : $this->centavos($valor, $nombre);
    }

    /**
     * `"12.35"` → `1235`, redondeando al centavo (medio hacia arriba).
     */
    public function centavos(string $valor, string $campo = 'valor'): int
    {
        $normalizado = str_replace([',', ' '], ['.', ''], trim($valor));

        if (preg_match('/^(-)?(\d*)(?:\.(\d{1,6}))?$/', $normalizado, $partes) !== 1) {
            throw ComprobanteException::campoInvalido($campo);
        }

        $negativo = $partes[1] === '-';
        $unidades = $partes[2] === '' ? '0' : $partes[2];
        $decimales = str_pad($partes[3] ?? '', 3, '0');
        $centavos = (int) $unidades * 100 + (int) substr($decimales, 0, 2);

        if ((int) $decimales[2] >= 5) {
            $centavos++;
        }

        return $negativo ? -$centavos : $centavos;
    }

    public function normalizar(string $descripcion): string
    {
        $sinAcentos = strtr($descripcion, self::MAPA_ACENTOS);
        $colapsado = preg_replace('/\s+/u', ' ', trim($sinAcentos)) ?? '';

        return mb_strtoupper($colapsado, 'UTF-8');
    }

    private function fecha(?string $valor): string
    {
        if ($valor === null) {
            throw ComprobanteException::campoFaltante('fechaEmision');
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $formato) {
            $fecha = \DateTimeImmutable::createFromFormat('!' . $formato, $valor);

            if ($fecha !== false && $fecha->format($formato) === $valor) {
                return $fecha->format('Y-m-d');
            }
        }

        throw ComprobanteException::campoInvalido('fechaEmision');
    }

    private function fechaOpcional(DOMXPath $xpath, string $nombre, ?DOMElement $contexto): string
    {
        $valor = $this->textoOpcional($xpath, $nombre, $contexto);

        return $valor === null ? '' : $this->fecha($valor);
    }

    private function fechaHoraOpcional(DOMXPath $xpath): ?string
    {
        $valor = $this->textoOpcional($xpath, 'fechaAutorizacion');

        if ($valor === null) {
            return null;
        }

        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i:sP'] as $formato) {
            $fecha = \DateTimeImmutable::createFromFormat('!' . $formato, $valor);

            if ($fecha !== false) {
                return $fecha->format('Y-m-d H:i:s');
            }
        }

        throw ComprobanteException::campoInvalido('fechaAutorizacion');
    }
}
