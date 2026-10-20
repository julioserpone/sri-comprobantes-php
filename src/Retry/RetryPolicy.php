<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Retry;

use JulioSerpone\SriComprobantes\Exception\TransportException;

/**
 * Reintentos con retroceso exponencial y jitter para fallos transitorios.
 * No reintenta errores de validación del SRI (esos requieren corregir el XML).
 */
final readonly class RetryPolicy
{
    public function __construct(
        private int $maxAttempts = 3,
        private int $baseDelayMs = 250,
        private int $maxDelayMs = 5000,
    ) {
        if ($this->maxAttempts < 1) {
            throw new \InvalidArgumentException('maxAttempts debe ser al menos 1.');
        }
        if ($this->baseDelayMs < 0 || $this->maxDelayMs < $this->baseDelayMs) {
            throw new \InvalidArgumentException('Los retardos de reintento no son válidos.');
        }
    }

    /**
     * @param callable(): mixed $operation
     * @param (callable(\Throwable): bool)|null $shouldRetry
     */
    public function execute(callable $operation, ?callable $shouldRetry = null): mixed
    {
        $shouldRetry ??= static fn (\Throwable $e): bool => $e instanceof TransportException;

        $attempt = 1;

        while (true) {
            try {
                return $operation();
            } catch (\Throwable $exception) {
                if ($attempt >= $this->maxAttempts || !$shouldRetry($exception)) {
                    throw $exception;
                }

                $delay = min($this->maxDelayMs, $this->baseDelayMs * (2 ** ($attempt - 1)));
                if ($delay > 0) {
                    usleep($delay * 1000 + random_int(0, intdiv($delay, 2)) * 1000);
                }

                $attempt++;
            }
        }
    }
}
