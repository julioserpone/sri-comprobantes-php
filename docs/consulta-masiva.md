# Consulta y descarga masiva de comprobantes

## Conclusión

**No existe una API oficial del SRI para consultar o descargar comprobantes en
masa.** Los servicios publicados reciben una clave de acceso (o una clave de
lote) y devuelven el estado de ese comprobante. No hay operaciones por rango de
fechas, por RUC, por establecimiento ni un endpoint de exportación.

Cualquier solución que prometa "descarga masiva por API" está, como mínimo,
usando scraping del portal, un convenio privado o una interpretación incorrecta
de los WSDL.

## Qué sí existe

| Mecanismo | Alcance | Automatizable |
| --- | --- | --- |
| `ConsultaComprobante` | Estado de una clave | Sí, una por llamada |
| `ConsultaFactura` | Confirmación de factura negociable | Sí, una por llamada |
| `AutorizacionComprobantesOffline` | XML autorizado de una clave | Sí, una por llamada |
| Portal del SRI | Consulta y descarga individual de XML/RIDE | No es una API; requiere navegación |
| Datos abiertos | Información agregada | No entrega comprobantes individuales |

## Estrategias viables

### 1. Consultar solo lo que el emisor ya conoce

Si usted es el emisor, su sistema ya tiene las claves de los comprobantes que
generó. Programe consultas individuales con:

- concurrencia limitada;
- backoff ante errores de transporte;
- caché de estados finales (`AUT`, `NAT`);
- exclusión de `PPR` hasta que pase el intervalo;
- registro de la respuesta cruda y del momento de la consulta.

Esto es una **consulta por lote lógico**, no una API masiva.

### 2. Integrarse con el sistema contable o el proveedor

Si el objetivo es conciliar comprobantes recibidos, el sistema contable o el
proveedor de facturación suele ser la fuente adecuada. Muchos proveedores
ofrecen exportaciones o webhooks internos, aunque no sean un servicio del SRI.

### 3. RPA sobre el portal (evaluar antes)

El portal permite consultas y descargas individuales. Automatizarlo con
navegador es técnicamente posible, pero:

- puede requerir captcha, sesión o aceptación de términos;
- es frágil ante cambios de UI;
- puede tener límites de uso no publicados;
- puede infringir condiciones de uso o normas de protección de datos.

**Validar** con el SRI y con su área legal antes de operar un scraper. No lo
presente como "API oficial".

## Diseño de un proceso masivo responsable

```text
[cola de claves] → [worker con concurrencia N]
                  → [consulta individual]
                  → [estado final?] ── sí → [persistir + métricas]
                                     └─ no → [reencolar con backoff]
```

Recomendaciones:

- Idempotencia por clave: no duplique llamadas para la misma clave.
- Límite de concurrencia y de solicitudes por minuto; mida la latencia real.
- Distinga error de transporte (reintentar) de `NAT`/`RECHAZADA` (no reintentar).
- No reintente `PPR`/error 70 en bucle cerrado; use un intervalo largo.
- Guarde `claveAcceso`, estado, fecha de consulta, código y mensaje.
- No almacene XML de terceros sin base legal y medidas de seguridad.
- Rote logs y anonimice datos personales.

## Señales de una mala implementación

- Generar claves nuevas para "forzar" la consulta.
- Reenviar comprobantes en `PPR` o con error 70.
- Tratar `NAT` como error transitorio.
- Consultar en paralelo sin límite y sin backoff.
- Afirmar que el SRI tiene un endpoint de descarga masiva.
- Depender de scraping sin plan de contingencia.

## Referencias

- [Guía técnica](guia-tecnica-sri.md), secciones 8–10.
- WSDL `ConsultaComprobante` y `ConsultaFactura` (verificados el 20/10/2026).
- Portal del SRI: <https://www.sri.gob.ec/>
