<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Service;

use JulioSerpone\SriComprobantes\Client\AutorizacionClient;
use JulioSerpone\SriComprobantes\Client\RecepcionClient;
use JulioSerpone\SriComprobantes\Dto\AutorizacionResult;
use JulioSerpone\SriComprobantes\Dto\RecepcionResult;
use JulioSerpone\SriComprobantes\Exception\SriException;
use JulioSerpone\SriComprobantes\Retry\RetryPolicy;

/**
 * Orquesta recepción y consulta de autorización. No firma ni genera XML: esas
 * responsabilidades pertenecen al emisor y a un firmador validado.
 */
final readonly class EmisionService
{
    public function __construct(
        private RecepcionClient $recepcion,
        private AutorizacionClient $autorizacion,
        private RetryPolicy $retry = new RetryPolicy(),
    ) {
    }

    public function enviar(string $xml): RecepcionResult
    {
        return $this->retry->execute(fn (): RecepcionResult => $this->recepcion->validar($xml));
    }

    /**
     * Consulta hasta obtener un estado final o agotar los intentos. Devuelve la
     * última respuesta para que el llamador decida; no oculta un estado
     * "EN PROCESO".
     */
    public function esperarAutorizacion(
        string $claveAcceso,
        int $intentos = 15,
        int $segundosEntreIntentos = 2,
    ): AutorizacionResult {
        if ($intentos < 1) {
            throw new \InvalidArgumentException('Los intentos deben ser al menos 1.');
        }

        $last = null;

        for ($i = 0; $i < $intentos; $i++) {
            $last = $this->retry->execute(
                fn (): AutorizacionResult => $this->autorizacion->consultar($claveAcceso)
            );

            if ($last->isAutorizado() || (!$last->isEnProceso() && $last->numeroComprobantes > 0)) {
                return $last;
            }

            if ($i < $intentos - 1 && $segundosEntreIntentos > 0) {
                sleep($segundosEntreIntentos);
            }
        }

        return $last ?? throw new SriException('No se obtuvo respuesta del servicio de autorización.');
    }

    /**
     * Flujo completo: envía, verifica recepción y espera autorización.
     * Lanza SriException si el SRI devuelve o rechaza el comprobante.
     */
    public function emitir(
        string $xml,
        string $claveAcceso,
        int $intentos = 15,
        int $segundosEntreIntentos = 2,
    ): AutorizacionResult {
        $recepcion = $this->enviar($xml);

        if (!$recepcion->isRecibida()) {
            $error = $recepcion->firstError();

            throw $error === null
                ? new SriException('El SRI no recibió el comprobante: ' . $recepcion->estado)
                : SriException::fromMessage(
                    $error->identificador,
                    $error->mensaje,
                    $error->informacionAdicional,
                );
        }

        $autorizacion = $this->esperarAutorizacion($claveAcceso, $intentos, $segundosEntreIntentos);

        if (!$autorizacion->isAutorizado()) {
            $error = null;
            foreach ($autorizacion->mensajes() as $mensaje) {
                if ($mensaje->isError()) {
                    $error = $mensaje;
                    break;
                }
            }

            throw $error === null
                ? new SriException('El comprobante no fue autorizado: ' . $autorizacion->estado())
                : SriException::fromMessage(
                    $error->identificador,
                    $error->mensaje,
                    $error->informacionAdicional,
                );
        }

        return $autorizacion;
    }
}
