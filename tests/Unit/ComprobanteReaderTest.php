<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Comprobante\ComprobanteException;
use JulioSerpone\SriComprobantes\Comprobante\ComprobanteReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComprobanteReader::class)]
final class ComprobanteReaderTest extends TestCase
{
    private ComprobanteReader $reader;

    protected function setUp(): void
    {
        $this->reader = new ComprobanteReader();
    }

    private static function fixture(string $nombre): string
    {
        $xml = file_get_contents(__DIR__ . '/../Fixtures/' . $nombre);

        self::assertIsString($xml);

        return $xml;
    }

    public function test_lee_una_factura_completa(): void
    {
        $comprobante = $this->reader->read(self::fixture('factura.xml'));

        self::assertSame('01', $comprobante->tipo);
        self::assertSame('2', $comprobante->ambiente);
        self::assertSame('2026-09-15', $comprobante->fechaEmision);
        self::assertSame('1758871873001', $comprobante->rucEmisor);
        self::assertSame('PROVEEDOR DE PRUEBA S.A.', $comprobante->razonSocialEmisor);
        self::assertSame('1790012345001', $comprobante->rucReceptor);
        self::assertSame('001', $comprobante->establecimiento);
        self::assertSame('001', $comprobante->puntoEmision);
        self::assertSame('000000123', $comprobante->secuencial);
        self::assertSame('USD', $comprobante->moneda);
    }

    public function test_convierte_los_montos_a_centavos_sin_float(): void
    {
        $comprobante = $this->reader->read(self::fixture('factura.xml'));

        self::assertSame(10000, $comprobante->subtotalCentavos);
        self::assertSame(1000, $comprobante->descuentoCentavos);
        self::assertSame(1350, $comprobante->impuestoCentavos);
        self::assertSame(0, $comprobante->propinaCentavos);
        self::assertSame(10350, $comprobante->totalCentavos);
        self::assertFalse($comprobante->totalsMismatch());
    }

    public function test_lee_items_e_impuestos(): void
    {
        $comprobante = $this->reader->read(self::fixture('factura.xml'));
        $primero = $comprobante->items[0];

        self::assertCount(2, $comprobante->items);
        self::assertSame(1, $primero->numeroLinea);
        self::assertSame('Servicios de consultoría contable', $primero->descripcion);
        self::assertSame('SERVICIOS DE CONSULTORIA CONTABLE', $primero->descripcionNormalizada);
        self::assertSame('SRV-001', $primero->codigoPrincipal);
        self::assertSame(9000, $primero->subtotalCentavos);
        self::assertSame(10350, $primero->totalCentavos);
        self::assertSame(10000, $comprobante->itemsSubtotalCentavos());
        self::assertCount(2, $comprobante->impuestos);
        self::assertSame('4', $comprobante->impuestos[0]->codigoPorcentaje);
        self::assertSame(1350, $comprobante->impuestosTotalCentavos());
    }

    public function test_marca_totales_inconsistentes_sin_corregirlos(): void
    {
        $xml = str_replace('<importeTotal>103.50</importeTotal>', '<importeTotal>104.00</importeTotal>', self::fixture('factura.xml'));

        $comprobante = $this->reader->read($xml);

        self::assertTrue($comprobante->totalsMismatch());
        self::assertSame(10400, $comprobante->totalCentavos);
        self::assertSame(10350, $comprobante->totalEsperadoCentavos());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function tipos(): array
    {
        return [
            'factura' => ['factura.xml', '01'],
            'liquidación de compra' => ['liquidacion-compra.xml', '03'],
            'nota de crédito' => ['nota-credito.xml', '04'],
            'nota de débito' => ['nota-debito.xml', '05'],
            'guía de remisión' => ['guia-remision.xml', '06'],
            'comprobante de retención' => ['comprobante-retencion.xml', '07'],
        ];
    }

    #[DataProvider('tipos')]
    public function test_lee_todos_los_tipos_soportados(string $fixture, string $tipo): void
    {
        $comprobante = $this->reader->read(self::fixture($fixture));

        self::assertSame($tipo, $comprobante->tipo);
        self::assertFalse($comprobante->totalsMismatch(), 'Los totales del fixture deben cuadrar.');
    }

    public function test_lee_una_nota_de_credito_y_su_documento_modificado(): void
    {
        $comprobante = $this->reader->read(self::fixture('nota-credito.xml'));

        self::assertSame('04', $comprobante->tipo);
        self::assertSame(['1509202601175887187300120010010000001238765432117'], $comprobante->clavesRelacionadas);
        self::assertSame('Devolución de servicios', $comprobante->motivo);
        self::assertCount(1, $comprobante->items);
        self::assertSame('SRV-001', $comprobante->items[0]->codigoPrincipal);
    }

    public function test_lee_una_liquidacion_de_compra(): void
    {
        $comprobante = $this->reader->read(self::fixture('liquidacion-compra.xml'));

        self::assertSame('03', $comprobante->tipo);
        self::assertSame('PROVEEDOR DE PRUEBA S.A.', $comprobante->razonSocialReceptor);
        self::assertSame(20000, $comprobante->subtotalCentavos);
        self::assertSame(23000, $comprobante->totalCentavos);
    }

    public function test_lee_una_guia_de_remision_con_su_destinatario(): void
    {
        $comprobante = $this->reader->read(self::fixture('guia-remision.xml'));

        self::assertSame('06', $comprobante->tipo);
        self::assertSame('2026-09-19', $comprobante->fechaEmision);
        self::assertSame('1790012345001', $comprobante->rucReceptor);
        self::assertCount(1, $comprobante->items);
    }

    public function test_lee_las_retenciones_practicadas(): void
    {
        $comprobante = $this->reader->read(self::fixture('comprobante-retencion.xml'));

        self::assertSame('07', $comprobante->tipo);
        self::assertCount(2, $comprobante->retenciones);
        self::assertSame('1', $comprobante->retenciones[0]->codigo);
        self::assertSame('303', $comprobante->retenciones[0]->codigoRetencion);
        self::assertSame('2.00', $comprobante->retenciones[0]->porcentajeRetener);
        self::assertSame(200, $comprobante->retenciones[0]->valorRetenidoCentavos);
        self::assertSame('2026-09-15', $comprobante->retenciones[0]->fechaEmisionDocumento);
        self::assertSame(2900, $comprobante->totalCentavos);
    }

    public function test_lee_comprobantes_con_namespace_en_la_raiz(): void
    {
        $xml = str_replace(
            '<factura id="comprobante" version="1.1.0">',
            '<factura xmlns="http://www.sri.gob.ec/factura" id="comprobante" version="1.1.0">',
            self::fixture('factura.xml'),
        );

        self::assertSame('01', $this->reader->read($xml)->tipo);
    }

    public function test_rechaza_un_xml_malformado(): void
    {
        $this->expectException(ComprobanteException::class);
        $this->expectExceptionMessage('no es válido');

        $this->reader->read('<factura><infoTributaria>');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function raicesNoSoportadas(): array
    {
        return [
            'comprobante de nota de venta' => ['<notaVenta></notaVenta>', 'no está soportado'],
            'xhtml' => ['<html><body/></html>', 'no está soportado'],
        ];
    }

    #[DataProvider('raicesNoSoportadas')]
    public function test_rechaza_una_raiz_no_soportada(string $xml, string $mensaje): void
    {
        $this->expectException(ComprobanteException::class);
        $this->expectExceptionMessage($mensaje);

        $this->reader->read($xml);
    }

    public function test_rechaza_un_comprobante_sin_info_tributaria(): void
    {
        $xml = (string) preg_replace('#<infoTributaria>.*?</infoTributaria>#s', '', self::fixture('factura.xml'));

        $this->expectException(ComprobanteException::class);
        $this->expectExceptionMessage('infoTributaria');

        $this->reader->read($xml);
    }

    public function test_rechaza_una_clave_de_acceso_invalida(): void
    {
        $xml = str_replace(
            '<claveAcceso>1509202601175887187300120010010000001238765432117</claveAcceso>',
            '<claveAcceso>1509202601175887187300120010010000001238765432118</claveAcceso>',
            self::fixture('factura.xml'),
        );

        $this->expectException(ComprobanteException::class);
        $this->expectExceptionMessage('claveAcceso');

        $this->reader->read($xml);
    }

    public function test_no_resuelve_entidades_externas(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0"?>
            <!DOCTYPE factura [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
            <factura><infoTributaria><claveAcceso>&xxe;</claveAcceso></infoTributaria></factura>
            XML;

        try {
            $this->reader->read($xml);

            self::fail('El lector debía rechazar el documento.');
        } catch (ComprobanteException $excepcion) {
            self::assertStringNotContainsString('root:', $excepcion->getMessage());
        }
    }

    public function test_rechaza_un_xml_que_supera_el_maximo(): void
    {
        $this->expectException(ComprobanteException::class);
        $this->expectExceptionMessage('tamaño máximo');

        $this->reader->read(str_repeat('a', ComprobanteReader::MAX_BYTES + 1));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function montos(): array
    {
        return [
            'entero' => ['12', 1200],
            'decimal' => ['12.35', 1235],
            'uno' => ['0.01', 1],
            'coma decimal' => ['12,35', 1235],
            'redondeo arriba' => ['1.005', 101],
            'redondeo abajo' => ['1.004', 100],
            'negativo' => ['-3.50', -350],
        ];
    }

    #[DataProvider('montos')]
    public function test_convierte_montos_a_centavos(string $valor, int $centavos): void
    {
        self::assertSame($centavos, $this->reader->centavos($valor));
    }

    public function test_rechaza_un_monto_invalido(): void
    {
        $this->expectException(ComprobanteException::class);

        $this->reader->centavos('12.3.4');
    }

    public function test_normaliza_la_descripcion_para_clasificacion(): void
    {
        self::assertSame(
            'SERVICIOS DE CONSULTORIA Y ASESORIA',
            $this->reader->normalizar("  Servicios   de   consultoría y asesoría "),
        );
    }
}
