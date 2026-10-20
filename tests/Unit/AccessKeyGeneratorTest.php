<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use DateTimeImmutable;
use JulioSerpone\SriComprobantes\AccessKey\AccessKeyGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccessKeyGenerator::class)]
final class AccessKeyGeneratorTest extends TestCase
{
    public function testOfficialModule11Vector(): void
    {
        self::assertSame(6, AccessKeyGenerator::module11('41261533'));
    }

    public function testGeneratesAValid49DigitKey(): void
    {
        $generator = new AccessKeyGenerator();

        $key = $generator->generate(
            new DateTimeImmutable('2026-10-20'),
            '01',
            '1790012345001',
            '1',
            '001001',
            '000000123',
            '12345678',
            '1',
        );

        self::assertSame(49, strlen($key));
        self::assertSame('201020260117900123450011001001000000123123456781', substr($key, 0, 48));
        self::assertSame((string) $generator->verifierDigit(substr($key, 0, 48)), $key[48]);
        self::assertTrue($generator->isValid($key));
    }

    public function testRejectsAnInvalidVerifierDigit(): void
    {
        $generator = new AccessKeyGenerator();

        $key = $generator->generate(
            new DateTimeImmutable('2026-10-20'),
            '01',
            '1790012345001',
            '1',
            '001001',
            '000000123',
            '12345678',
        );

        $last = (int) $key[48];
        $tampered = substr($key, 0, 48) . (string) (($last + 1) % 10);

        self::assertFalse($generator->isValid($tampered));
        self::assertFalse($generator->isValid('123'));
        self::assertFalse($generator->isValid(str_repeat('a', 49)));
    }

    public function testRejectsFieldsWithWrongLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new AccessKeyGenerator())->generate(
            new DateTimeImmutable('2026-10-20'),
            '01',
            '123',
            '1',
            '001001',
            '000000123',
            '12345678',
        );
    }
}
