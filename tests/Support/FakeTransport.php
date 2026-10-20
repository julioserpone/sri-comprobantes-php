<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Support;

use JulioSerpone\SriComprobantes\Transport\Transport;

final class FakeTransport implements Transport
{
    /** @var list<array{operation: string, arguments: array<string, mixed>}> */
    public array $calls = [];

    /** @var list<mixed> */
    private array $responses;

    /** @param list<mixed> $responses */
    public function __construct(array $responses = [])
    {
        $this->responses = $responses;
    }

    public function call(string $operation, array $arguments): mixed
    {
        $this->calls[] = ['operation' => $operation, 'arguments' => $arguments];

        if ($this->responses === []) {
            throw new \RuntimeException("Sin respuesta simulada para {$operation}");
        }

        return array_shift($this->responses);
    }

    public function lastRequest(): ?string
    {
        return null;
    }

    public function lastResponse(): ?string
    {
        return null;
    }
}
