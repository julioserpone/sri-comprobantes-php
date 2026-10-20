# ADR 0004: soportar lotes con el formato de la Tabla 8

- **Estado:** aceptado
- **Fecha:** 2026-10-20

## Contexto

La ficha v2.34 permite enviar comprobantes individuales o agrupados. El lote
tiene un XML propio (`<lote version="1.0.0">`) con clave de acceso, RUC y una
lista de comprobantes en CDATA. Se envía por el mismo `validarComprobante`.

## Decisión

- Implementar `Xml\LoteBuilder` con el formato confirmado.
- Validar clave de lote (49 dígitos + módulo 11), RUC (13 dígitos), mínimo 1 y
  máximo 50 comprobantes, y tamaño máximo de 512000 bytes.
- Escapar `]]>` dentro de CDATA para evitar XML mal formado.
- No firmar el lote: cada comprobante ya debe estar firmado. El sobre `<lote>`
  no lleva una firma propia en la Tabla 8.
- Exponer `AutorizacionClient::consultarLote()` y
  `LoteAutorizacionResult`.

## Consecuencias

- El llamador debe firmar cada comprobante antes de armar el lote.
- El límite de 500 KB se controla por bytes; el SRI puede rechazar por tamaño
  aunque el conteo sea menor.
- La clave del lote debe ser única y usar serie/secuencial exclusivos para
  lotes, según la ficha.

## Alternativas consideradas

- **Enviar siempre individual:** válido, pero menos eficiente y no cubre el
  caso documentado.
- **Comprimir el lote:** no está documentado; no se implementa.
- **Firmar el sobre:** no está en la Tabla 8; no se inventa.
