<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Exception\TransportException;
use JulioSerpone\SriComprobantes\Retry\RetryPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RetryPolicy::class)]
final class RetryPolicyTest extends TestCase
{
    public function testRetriesTransientFailures(): void
    {
        $attempts = 0;
        $policy = new RetryPolicy(maxAttempts: 3, baseDelayMs: 0, maxDelayMs: 0);

        $result = $policy->execute(function () use (&$attempts): string {
            $attempts++;
            if ($attempts < 3) {
                throw new TransportException('timeout');
            }

            return 'ok';
        });

        self::assertSame('ok', $result);
        self::assertSame(3, $attempts);
    }

    public function testGivesUpAfterMaxAttempts(): void
    {
        $attempts = 0;
        $policy = new RetryPolicy(maxAttempts: 2, baseDelayMs: 0, maxDelayMs: 0);

        try {
            $policy->execute(function () use (&$attempts): never {
                $attempts++;
                throw new TransportException('timeout');
            });
            self::fail('Se esperaba TransportException.');
        } catch (TransportException) {
            self::assertSame(2, $attempts);
        }
    }

    public function testDoesNotRetryValidationErrors(): void
    {
        $attempts = 0;
        $policy = new RetryPolicy(maxAttempts: 5, baseDelayMs: 0, maxDelayMs: 0);

        $this->expectException(\InvalidArgumentException::class);

        try {
            $policy->execute(function () use (&$attempts): never {
                $attempts++;
                throw new \InvalidArgumentException('dato inválido');
            });
        } finally {
            self::assertSame(1, $attempts);
        }
    }
}
