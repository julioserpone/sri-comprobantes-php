<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Tests\Unit;

use JulioSerpone\SriComprobantes\Exception\ConfigurationException;
use JulioSerpone\SriComprobantes\Exception\SignatureException;
use JulioSerpone\SriComprobantes\Signature\AwsLambdaInvoker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AwsLambdaInvoker::class)]
final class AwsLambdaInvokerTest extends TestCase
{
    public function testInvokesTheFunctionAndDecodesThePayload(): void
    {
        $client = new FakeAwsLambdaClient([
            'StatusCode' => 200,
            'Payload' => '{"signedXmlBase64":"PHhtbD48L3htbD4="}',
        ]);
        $invoker = new AwsLambdaInvoker($client, 'sri-xades-signer-invoke');

        $response = $invoker->invoke([
            'tenantUuid' => '123e4567-e89b-12d3-a456-426614174000',
            'password' => 'secret',
            'xmlBase64' => 'PGZhY3R1cmEvPg==',
        ]);

        self::assertSame('PHhtbD48L3htbD4=', $response['signedXmlBase64']);
        self::assertCount(1, $client->calls);
        self::assertSame('sri-xades-signer-invoke', $client->calls[0]['FunctionName']);
        self::assertSame('RequestResponse', $client->calls[0]['InvocationType']);

        /** @var array{tenantUuid: string, password: string, xmlBase64: string} $payload */
        $payload = json_decode((string) $client->calls[0]['Payload'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('secret', $payload['password']);
    }

    public function testSendsTheQualifierWhenConfigured(): void
    {
        $client = new FakeAwsLambdaClient(['Payload' => '{}']);
        $invoker = new AwsLambdaInvoker($client, 'sri-xades-signer-invoke', 'live');

        $invoker->invoke([
            'tenantUuid' => '123e4567-e89b-12d3-a456-426614174000',
            'password' => 'secret',
            'xmlBase64' => 'PGZhY3R1cmEvPg==',
        ]);

        self::assertSame('live', $client->calls[0]['Qualifier']);
    }

    public function testRejectsAFunctionError(): void
    {
        $client = new FakeAwsLambdaClient([
            'FunctionError' => 'Unhandled',
            'Payload' => '{"errorMessage":"boom"}',
        ]);
        $invoker = new AwsLambdaInvoker($client, 'sri-xades-signer-invoke');

        $this->expectException(SignatureException::class);

        $invoker->invoke([
            'tenantUuid' => '123e4567-e89b-12d3-a456-426614174000',
            'password' => 'secret',
            'xmlBase64' => 'PGZhY3R1cmEvPg==',
        ]);
    }

    public function testRejectsAnInvalidJsonPayload(): void
    {
        $client = new FakeAwsLambdaClient(['Payload' => 'not-json']);
        $invoker = new AwsLambdaInvoker($client, 'sri-xades-signer-invoke');

        $this->expectException(SignatureException::class);

        $invoker->invoke([
            'tenantUuid' => '123e4567-e89b-12d3-a456-426614174000',
            'password' => 'secret',
            'xmlBase64' => 'PGZhY3R1cmEvPg==',
        ]);
    }

    public function testWrapsClientExceptions(): void
    {
        $client = new FakeAwsLambdaClient(new \RuntimeException('network'));
        $invoker = new AwsLambdaInvoker($client, 'sri-xades-signer-invoke');

        $this->expectException(SignatureException::class);

        $invoker->invoke([
            'tenantUuid' => '123e4567-e89b-12d3-a456-426614174000',
            'password' => 'secret',
            'xmlBase64' => 'PGZhY3R1cmEvPg==',
        ]);
    }

    public function testRejectsAClientWithoutInvoke(): void
    {
        $this->expectException(ConfigurationException::class);

        new AwsLambdaInvoker(new \stdClass(), 'sri-xades-signer-invoke');
    }

    public function testRejectsAnEmptyFunctionName(): void
    {
        $this->expectException(ConfigurationException::class);

        new AwsLambdaInvoker(new FakeAwsLambdaClient([]), '');
    }
}

final class FakeAwsLambdaClient
{
    /** @var list<array<string, mixed>> */
    public array $calls = [];

    /**
     * @param array<string, mixed>|\Throwable $result
     */
    public function __construct(private readonly array|\Throwable $result)
    {
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function invoke(array $arguments): array
    {
        $this->calls[] = $arguments;

        if ($this->result instanceof \Throwable) {
            throw $this->result;
        }

        return $this->result;
    }
}
