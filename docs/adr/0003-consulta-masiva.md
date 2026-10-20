# ADR 0003: no ofrecer descarga masiva como API

- **Estado:** aceptado
- **Fecha:** 2026-10-20

## Contexto

Es frecuente que se solicite "un endpoint para descargar todos los
comprobantes". Los WSDL del SRI solo exponen consultas por clave de acceso. El
portal permite consultas/descargas individuales, pero no publica una API masiva.

## Decisión

1. No implementar ni documentar una API masiva inexistente.
2. Exponer solo consultas individuales por clave.
3. Documentar estrategias responsables: colas, concurrencia limitada, backoff,
   caché de estados finales y conciliación con sistemas internos.
4. Desaconsejar el scraping no autorizado y exigir validación legal/operativa
   si se evalúa RPA.

## Consecuencias

- No habrá un método `descargarTodo()`.
- El usuario diseña su propia orquestación masiva sobre consultas unitarias.
- Se evita acoplar el paquete a la UI del portal, que cambia sin contrato.

## Alternativas consideradas

- **Scraper del portal:** descartado como funcionalidad del paquete.
- **Datos abiertos:** no entrega comprobantes individuales; no cubre el caso.
- **Convenio/API privada:** fuera del alcance de un paquete open source.
