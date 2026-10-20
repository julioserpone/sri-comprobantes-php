<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Error\SriError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SriError::class)]
final class SriErrorTest extends TestCase
{
    public function testMapsDocumentedCodes(): void
    {
        self::assertSame(SriError::DOCUMENTO_INVALIDO, SriError::fromCode(35));
        self::assertSame(SriError::DOCUMENTO_INVALIDO, SriError::fromCode('35'));
        self::assertNull(SriError::fromCode(null));
        self::assertNull(SriError::fromCode(9999));
    }

    public function testOnlyInternalErrorIsTransient(): void
    {
        self::assertTrue(SriError::ERROR_INTERNO->isTransient());
        self::assertFalse(SriError::DOCUMENTO_INVALIDO->isTransient());
        self::assertTrue(SriError::CLAVE_EN_PROCESAMIENTO->isInProcess());
    }

    public function testWarningsAreClassified(): void
    {
        self::assertTrue(SriError::AMBIENTE_PRUEBAS->isWarning());
        self::assertFalse(SriError::FIRMA_INVALIDA->isWarning());
        self::assertSame('Firma electrónica inválida', SriError::FIRMA_INVALIDA->description());
    }
}
