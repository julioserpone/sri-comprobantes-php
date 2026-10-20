---
name: SRI Consulta Masiva
description: Diseñar consultas o conciliaciones de muchos comprobantes del SRI sin inventar una API masiva. Úsela cuando pidan descargar todos los comprobantes, consultar por rango de fechas/RUC o automatizar el portal.
---

# SRI Consulta Masiva

## Principio

**No existe API oficial de consulta/descarga masiva.** Los WSDL permiten una
clave por llamada. El portal es individual y no es un contrato de integración.

## Cuándo usar

- "Descarga todos los comprobantes de este mes".
- "Consulta por RUC/fecha".
- "Automatiza el portal".
- "¿Hay un endpoint masivo?"

## Respuesta base

1. Explique que no hay API masiva.
2. Diferencie consulta individual, portal y datos abiertos.
3. Proponga una cola de claves conocidas por el emisor.
4. Si piden scraping, advierta sobre términos, captchas, límites y
   mantenimiento; no lo presente como API oficial.

## Diseño recomendado

```text
[claves conocidas] → [cola] → [worker concurrencia N]
                    → [consulta individual]
                    → [estado final] → [persistir]
                    → [PPR/transporte] → [reencolar con backoff]
```

- Idempotencia por clave.
- Límite de concurrencia y de solicitudes/minuto.
- Distinguir error de transporte de `NAT`/`RECHAZADA`.
- No reintentar `PPR`/70 en bucle cerrado.
- Guardar estado, fecha, código y mensaje.
- No almacenar XML de terceros sin base legal.

## Entregables

- Documento de alcance y límites.
- Diseño de cola/backoff.
- Métricas: pendientes, autorizados, rechazados, errores.
- Plan de contingencia si el portal cambia.

## No hacer

- Inventar `descargarTodo()` o un endpoint por rango.
- Prometer tiempo real sin medir latencia/límites.
- Ignorar protección de datos.
- Consultar sin límite y sin backoff.

Ver `docs/consulta-masiva.md`.
