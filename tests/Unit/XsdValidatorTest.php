<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Exception\XmlValidationException;
use JulioSerpone\SriComprobantes\Xml\XsdValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XsdValidator::class)]
final class XsdValidatorTest extends TestCase
{
    private string $xsd;

    protected function setUp(): void
    {
        $this->xsd = sys_get_temp_dir() . '/sri-test-' . bin2hex(random_bytes(6)) . '.xsd';
        file_put_contents($this->xsd, <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
              <xs:element name="factura">
                <xs:complexType>
                  <xs:sequence>
                    <xs:element name="total" type="xs:decimal"/>
                  </xs:sequence>
                  <xs:attribute name="id" type="xs:string" use="required"/>
                </xs:complexType>
              </xs:element>
            </xs:schema>
            XML);
    }

    protected function tearDown(): void
    {
        @unlink($this->xsd);
    }

    public function testAcceptsValidXml(): void
    {
        $this->expectNotToPerformAssertions();

        (new XsdValidator())->validate('<factura id="1"><total>10.50</total></factura>', $this->xsd);
    }

    public function testRejectsXmlThatDoesNotMatchSchema(): void
    {
        $this->expectException(XmlValidationException::class);

        (new XsdValidator())->validate('<factura id="1"></factura>', $this->xsd);
    }

    public function testRejectsMalformedXml(): void
    {
        $this->expectException(XmlValidationException::class);

        (new XsdValidator())->validate('<factura>', $this->xsd);
    }

    public function testMissingSchemaIsAConfigurationError(): void
    {
        $this->expectException(\JulioSerpone\SriComprobantes\Exception\ConfigurationException::class);

        (new XsdValidator())->validate('<factura/>', '/ruta/que/no/existe.xsd');
    }
}
