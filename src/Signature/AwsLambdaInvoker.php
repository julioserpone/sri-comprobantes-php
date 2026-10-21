<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Signature;

use JulioSerpone\SriComprobantes\Exception\ConfigurationException;
use JulioSerpone\SriComprobantes\Exception\SignatureException;

/**
 * Invoca una función AWS Lambda con el SDK de AWS para PHP.
 *
 * El SDK no es una dependencia obligatoria del paquete: instale
 * `aws/aws-sdk-php` en el proyecto consumidor y pase una instancia de
 * `Aws\Lambda\LambdaClient` al constructor. El cliente se recibe como `object`
 * para que esta clase pueda cargarse sin el SDK.
 */
final readonly class AwsLambdaInvoker implements LambdaInvoker
{
    private \Closure $invoke;

    /**
     * @param object $client Instancia de Aws\Lambda\LambdaClient.
     */
    public function __construct(
        object $client,
        private string $functionName,
        private string $qualifier = '',
    ) {
        if ($functionName === '') {
            throw new ConfigurationException('El nombre de la función Lambda es obligatorio.');
        }

        if (!method_exists($client, 'invoke')) {
            throw new ConfigurationException(
                'El cliente Lambda debe exponer invoke(); use Aws\Lambda\LambdaClient.'
            );
        }

        /** @var callable(array<string, mixed>): mixed $invoke */
        $invoke = [$client, 'invoke'];
        $this->invoke = $invoke(...);
    }

    public function invoke(#[\SensitiveParameter] array $payload): array
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new SignatureException('No se pudo codificar la solicitud de firma.', 0, $exception);
        }

        $arguments = [
            'FunctionName' => $this->functionName,
            'InvocationType' => 'RequestResponse',
            'Payload' => $json,
        ];

        if ($this->qualifier !== '') {
            $arguments['Qualifier'] = $this->qualifier;
        }

        try {
            $result = ($this->invoke)($arguments);
        } catch (\Throwable $exception) {
            throw new SignatureException('No se pudo invocar el firmador Lambda.', 0, $exception);
        }

        if (!is_object($result) && !is_array($result)) {
            throw new SignatureException('La invocación de Lambda devolvió un resultado inválido.');
        }

        $functionError = self::value($result, 'FunctionError');
        if (is_string($functionError) && $functionError !== '') {
            throw new SignatureException('La función Lambda devolvió un error de ejecución.');
        }

        $body = self::value($result, 'Payload');
        if (is_object($body) && method_exists($body, '__toString')) {
            $body = (string) $body;
        }

        if (!is_string($body) || trim($body) === '') {
            throw new SignatureException('La función Lambda no devolvió un payload.');
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new SignatureException('La respuesta de Lambda no es JSON válido.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new SignatureException('La respuesta de Lambda no es un objeto JSON.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param object|array<string, mixed> $result
     */
    private static function value(object|array $result, string $key): mixed
    {
        if (is_array($result)) {
            return $result[$key] ?? null;
        }

        if ($result instanceof \ArrayAccess) {
            return $result[$key] ?? null;
        }

        $values = get_object_vars($result);

        return $values[$key] ?? null;
    }
}
