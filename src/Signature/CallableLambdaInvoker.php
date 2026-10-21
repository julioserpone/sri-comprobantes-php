<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Signature;

use JulioSerpone\SriComprobantes\Exception\SignatureException;

/**
 * Adapta un callable a LambdaInvoker. Útil para pruebas, colas internas o un
 * cliente Lambda propio.
 */
final readonly class CallableLambdaInvoker implements LambdaInvoker
{
    private \Closure $invoker;

    /**
     * @param callable(array{tenantUuid: string, password: string, xmlBase64: string}): mixed $invoker
     */
    public function __construct(callable $invoker)
    {
        $this->invoker = $invoker(...);
    }

    public function invoke(#[\SensitiveParameter] array $payload): array
    {
        $response = ($this->invoker)($payload);

        if (!is_array($response)) {
            throw new SignatureException('El invocador de Lambda no devolvió un arreglo.');
        }

        /** @var array<string, mixed> $response */
        return $response;
    }
}
