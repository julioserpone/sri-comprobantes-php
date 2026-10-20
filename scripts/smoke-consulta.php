<?php

declare(strict_types=1);

/**
 * Prueba manual contra el servicio de consulta del SRI.
 *
 * Uso:
 *   php scripts/smoke-consulta.php <clave-de-49-digitos> [1|2]
 *
 * No se ejecuta en CI. No envíe claves de producción por terminals compartidas.
 */

require __DIR__ . '/../vendor/autoload.php';

use JulioSerpone\SriComprobantes\Client\ConsultaClient;
use JulioSerpone\SriComprobantes\Config\SriEnvironment;
use JulioSerpone\SriComprobantes\Config\SriService;
use JulioSerpone\SriComprobantes\Transport\SoapTransport;

$clave = $argv[1] ?? '';
$ambiente = $argv[2] ?? '1';

if (preg_match('/^\d{49}$/', $clave) !== 1) {
    fwrite(STDERR, "Uso: php scripts/smoke-consulta.php <clave-de-49-digitos> [1|2]\n");
    exit(1);
}

try {
    $environment = SriEnvironment::fromCode($ambiente);
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

$transport = new SoapTransport(
    wsdl: $environment->wsdl(SriService::CONSULTA),
    connectionTimeout: 10.0,
    timeout: 20.0,
);

try {
    $result = (new ConsultaClient($transport))->estado($clave);
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage() . "\n");
    exit(2);
}

printf("Ambiente: %s\n", $environment->label());
printf("Clave: %s\n", $clave);
printf("Estado autorización: %s\n", $result->estadoAutorizacion ?? '(sin dato)');
printf("Estado consulta: %s\n", $result->estadoConsulta ?? '(sin dato)');

foreach ($result->mensajes() as $mensaje) {
    printf("Mensaje: %s\n", (string) $mensaje);
}
