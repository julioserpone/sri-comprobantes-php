# ADR 0005: no incluir generadores de XML por tipo de comprobante

- **Estado:** aceptado
- **Fecha:** 2026-10-20

## Contexto

Cada comprobante (factura, nota de crédito, nota de débito, guía de remisión,
retención, liquidación de compra) tiene su propio XSD, versión y anexos. Los
campos obligatorios cambian con la normativa y con el tipo de contribuyente
(RIMPE, grandes contribuyentes, agentes de retención, etc.).

## Decisión

Mantener el paquete como capa de integración y no como un facturador completo.
El XML de negocio lo genera la aplicación, que conoce sus datos y obligaciones.
El paquete ofrece validación XSD, clave, lote, firma (interfaz), transporte,
DTO, errores, reintentos y almacenamiento.

## Consecuencias

- El usuario debe modelar su XML y mantener los XSD.
- Se evita una API rígida que se rompa con cada anexo.
- Los ejemplos de la documentación son conceptuales, no plantillas de
  producción.

## Alternativas consideradas

- **Builders fluidos por tipo:** mucho mantenimiento y riesgo de generar XML
  incompleto o normativamente incorrecto.
- **Plantillas copiadas de blogs:** descartado; no son verificables.
