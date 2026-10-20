<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Storage\FilesystemComprobanteRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FilesystemComprobanteRepository::class)]
final class FilesystemComprobanteRepositoryTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/sri-outbox-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->directory);
    }

    public function testSavesAndReadsXmlAndState(): void
    {
        $repository = new FilesystemComprobanteRepository($this->directory);
        $key = str_repeat('7', 49);

        $repository->guardar($key, '<factura/>', 'AUTORIZADO');

        self::assertSame('<factura/>', $repository->obtenerXml($key));
        self::assertSame('AUTORIZADO', $repository->obtenerEstado($key));
        self::assertNull($repository->obtenerXml(str_repeat('8', 49)));
        self::assertNull($repository->obtenerEstado(str_repeat('9', 49)));
        self::assertSame(0o600, fileperms($this->directory . '/' . $key . '.xml') & 0o777);
    }

    public function testRejectsAccessKeysThatCouldEscapeTheDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new FilesystemComprobanteRepository($this->directory))->guardar('../../etc/passwd', '<x/>', 'X');
    }
}
