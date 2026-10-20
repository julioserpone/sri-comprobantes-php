# ADR 0001: esquema off-line y endpoints vigentes

- **Estado:** aceptado
- **Fecha:** 2026-10-20
- **Contexto:** integración con el SRI de Ecuador

## Contexto

El SRI publicó históricamente servicios `RecepcionComprobantes` y
`AutorizacionComprobantes` (sin el sufijo `Offline`). En la verificación del
20/10/2026 esos endpoints ya no responden. La ficha v2.34 documenta el esquema
off-line con los servicios `RecepcionComprobantesOffline` y
`AutorizacionComprobantesOffline`, además de `ConsultaComprobante` y
`ConsultaFactura`.

## Decisión

1. Soportar únicamente los endpoints vigentes del esquema off-line.
2. Modelar ambientes como `SriEnvironment` (`1` pruebas, `2` producción) y
   servicios como `SriService`, sin URLs duplicadas en los clientes.
3. No incluir endpoints antiguos ni "fallbacks" automáticos.
4. Documentar la fecha de verificación y exigir TLS con validación de
   certificado.

## Consecuencias

- Una integración antigua debe migrar sus URLs.
- El código no queda atado a una IP ni a un certificado TLS.
- Si el SRI publica un endpoint nuevo, se agrega al enum y se documenta; no se
  adivina una ruta.

## Alternativas consideradas

- **Mantener ambos endpoints:** descartado; los antiguos no responden y un
  fallback silencioso oculta el problema.
- **URLs en variables de entorno libres:** descartado para el caso común; se
  puede usar `SoapTransport` con un WSDL explícito si se requiere.
