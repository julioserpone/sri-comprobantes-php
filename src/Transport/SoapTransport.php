<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Transport;

use JulioSerpone\SriComprobantes\Exception\ConfigurationException;
use JulioSerpone\SriComprobantes\Exception\TransportException;
use SoapClient;
use SoapFault;

/**
 * Transporte basado en ext-soap.
 *
 * Usa TLS con verificación de certificado y keep-alive desactivado. El WSDL
 * se carga de forma perezosa para no bloquear el arranque de la aplicación.
 */
final class SoapTransport implements Transport
{
    private ?SoapClient $client = null;

    private ?string $lastRequest = null;

    private ?string $lastResponse = null;

    /**
     * @param array<string, mixed> $options Opciones adicionales de SoapClient.
     */
    public function __construct(
        private readonly string $wsdl,
        private readonly float $connectionTimeout = 10.0,
        private readonly float $timeout = 30.0,
        private readonly int $wsdlCache = WSDL_CACHE_DISK,
        private readonly array $options = [],
    ) {
        if (filter_var($this->wsdl, FILTER_VALIDATE_URL) === false) {
            throw new ConfigurationException("WSDL no válido: {$this->wsdl}");
        }

        if ($this->connectionTimeout <= 0 || $this->timeout <= 0) {
            throw new ConfigurationException('Los tiempos de conexión y espera deben ser mayores que cero.');
        }
    }

    public function wsdl(): string
    {
        return $this->wsdl;
    }

    public function call(string $operation, array $arguments): mixed
    {
        $this->lastRequest = null;
        $this->lastResponse = null;

        try {
            $result = $this->client()->__soapCall($operation, [$arguments]);
        } catch (SoapFault $fault) {
            throw new TransportException(
                "Error SOAP al ejecutar {$operation}: {$fault->getMessage()}",
                0,
                $fault,
            );
        } catch (\Throwable $throwable) {
            throw new TransportException(
                "Error de transporte al ejecutar {$operation}: {$throwable->getMessage()}",
                0,
                $throwable,
            );
        }

        $this->lastRequest = $this->client()->__getLastRequest() ?: null;
        $this->lastResponse = $this->client()->__getLastResponse() ?: null;

        return $result;
    }

    public function lastRequest(): ?string
    {
        return $this->lastRequest;
    }

    public function lastResponse(): ?string
    {
        return $this->lastResponse;
    }

    private function client(): SoapClient
    {
        if ($this->client instanceof SoapClient) {
            return $this->client;
        }

        $options = [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => $this->wsdlCache,
            'connection_timeout' => $this->connectionTimeout,
            'keep_alive' => false,
            'user_agent' => 'julioserpone/sri-comprobantes-php',
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false,
                ],
            ]),
        ];

        $this->client = new SoapClient($this->wsdl, array_replace($options, $this->options));

        return $this->client;
    }
}
