<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use DateTimeImmutable;
use JulioSerpone\SriComprobantes\AccessKey\AccessKeyGenerator;
use JulioSerpone\SriComprobantes\Xml\LoteBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoteBuilder::class)]
final class LoteBuilderTest extends TestCase
{
    public function testBuildsLoteWithCdata(): void
    {
        $key = $this->key();
        $xml = (new LoteBuilder())->build($key, '1790012345001', ['<factura id="1"/>', '<factura id="2"/>']);

        self::assertStringContainsString('<lote version="1.0.0">', $xml);
        self::assertStringContainsString("<claveAcceso>{$key}</claveAcceso>", $xml);
        self::assertStringContainsString('<ruc>1790012345001</ruc>', $xml);
        self::assertSame(2, substr_count($xml, '<comprobante><![CDATA['));
    }

    public function testEscapesCdataTerminator(): void
    {
        $xml = (new LoteBuilder())->build($this->key(), '1790012345001', ['<x>]]></x>']);

        self::assertStringContainsString(']]]]><![CDATA[>', $xml);
        self::assertStringNotContainsString('<x>]]></x>', $xml);
    }

    public function testRejectsMoreThanFiftyComprobantes(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LoteBuilder())->build($this->key(), '1790012345001', array_fill(0, 51, '<factura/>'));
    }

    public function testRejectsInvalidRuc(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LoteBuilder())->build($this->key(), '123', ['<factura/>']);
    }

    private function key(): string
    {
        return (new AccessKeyGenerator())->generate(
            new DateTimeImmutable('2026-10-20'),
            '01',
            '1790012345001',
            '1',
            '001001',
            '000000001',
            '12345678',
        );
    }
}
