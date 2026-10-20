# Arquitectura

## Principios

1. **Sin framework y sin estado global.** El paquete se integra en Laravel,
   Symfony, un worker CLI o un script puntual.
2. **Dependencias explícitas.** Los clientes reciben un `Transport`; el servicio
   recibe clientes y una política de reintentos. Todo se puede sustituir en
   pruebas.
3. **DTO inmutables.** Las respuestas del SRI se normalizan a `readonly class`.
4. **Separación de responsabilidades.** Generar XML, firmar y transportar son
   problemas distintos. El paquete solo implementa los dos últimos cuando puede
   hacerlo de forma verificable.
5. **Fallar de forma explícita.** Un XML vacío, una clave inválida o un
   certificado que no abre producen excepciones claras, no respuestas silenciosas.
6. **No ocultar estados.** `esperarAutorizacion()` devuelve el último estado
   (`PPR`, `NAT`, etc.) en lugar de fingir éxito.

## Capas

```text
┌────────────────────────────────────────────────────────────┐
│ Aplicación: datos de negocio, XML por tipo, RIDE, colas     │
└───────────────────────────┬────────────────────────────────┘
                            │
┌───────────────────────────▼────────────────────────────────┐
│ Service\EmisionService                                      │
│  enviar() · esperarAutorizacion() · emitir()                │
└───────────┬───────────────────────────────┬────────────────┘
            │                               │
┌───────────▼──────────┐        ┌───────────▼────────────────┐
│ Client\Recepcion     │        │ Client\Autorizacion        │
│ Client\Consulta      │        │ DTO + Error\SriError       │
└───────────┬──────────┘        └───────────┬────────────────┘
            │                               │
            └──────────────┬────────────────┘
                           │
                 ┌─────────▼─────────┐
                 │ Transport          │
                 │  SoapTransport     │
                 └─────────┬─────────┘
                           │ HTTPS + SOAP
                 ┌─────────▼─────────┐
                 │ WSDL del SRI      │
                 └───────────────────┘

Piezas laterales:
  AccessKey\AccessKeyGenerator   → clave de 49 dígitos
  Xml\XsdValidator               → validación local
  Xml\LoteBuilder                → sobre <lote>
  Signature\Signer               → integración XAdES-BES
  Signature\P12Certificate       → carga PKCS#12
  Retry\RetryPolicy              → backoff para transporte
  Storage\ComprobanteRepository  → trazabilidad
```

## Flujo de una emisión

1. La aplicación arma el XML con los datos del comprobante.
2. `AccessKeyGenerator` calcula la clave; la aplicación la inserta en
   `infoTributaria/claveAcceso`.
3. `XsdValidator` valida el XML contra el XSD local (opcional pero recomendado).
4. Un `Signer` externo firma el XML (XAdES-BES).
5. `RecepcionClient::validar()` envía el XML o el lote.
6. Si `RECIBIDA`, `AutorizacionClient::consultar()` consulta el estado.
7. `EmisionService::emitir()` encapsula 5–6 y lanza `SriException` ante un
   rechazo.
8. La aplicación guarda el XML autorizado, el estado y los metadatos.

## Decisiones de diseño

| Decisión | Motivo | Consecuencia |
| --- | --- | --- |
| `Transport` como interfaz | Probar sin red; permitir otro cliente | Más cableado en la aplicación |
| `SoapTransport` perezoso | No descargar WSDL al construir el objeto | El primer error de red aparece en `call()` |
| DTO `readonly` | Evitar mutaciones accidentales | No se reutilizan para request |
| Normalización tolerante | `ext-soap` devuelve objeto o lista según cardinalidad | Los DTO deben manejar ambos casos |
| Reintento solo de transporte | Un XML inválido no se arregla reintentando | La aplicación debe corregir y reenviar |
| Sin firmador propio | XAdES-BES es sensible y difícil de validar | Se requiere un firmador externo |
| Filesystem repository mínimo | Trazabilidad sin imponer base de datos | No apto para múltiples nodos |
| Sin dependencias runtime | Menos superficie de ataque y conflictos | Se usa `ext-soap`/`ext-openssl` |

## Puntos de extensión

- **Firmador:** implemente `Signature\Signer` o use `CallbackSigner`.
- **Transporte:** implemente `Transport` con Guzzle, un proxy o un cliente
  corporativo.
- **Persistencia:** implemente `Storage\ComprobanteRepository` sobre PDO,
  Redis, S3 o un servicio interno.
- **Reintentos:** pase una `RetryPolicy` con otra política o un callback
  `shouldRetry`.
- **Reloj:** los métodos que reciben fechas aceptan `DateTimeInterface`; para
  pruebas use fechas fijas. No hay reloj global.

## Pruebas

- Unitarias: clave, entornos, errores, DTO, lote, XSD, almacenamiento, PKCS#12.
- Integración simulada: `FakeTransport` devuelve respuestas del SRI y permite
  verificar operación y argumentos.
- Integración real: **no** se ejecuta en CI. Se hace en el ambiente de
  certificación con un certificado de pruebas, XSD oficiales y un firmador
  validado.

## Lo que esta arquitectura no resuelve

- Generación de XML por tipo/versión (factura, notas, retenciones, guías).
- Cálculo de impuestos y validaciones de negocio.
- Firma XAdES-BES.
- RIDE.
- Consulta masiva (no hay API oficial).
- Colas, reintentos persistentes o idempotencia de extremo a extremo; eso
  pertenece a la aplicación.

Ver [ADR 0001](adr/0001-esquema-offline-y-endpoints.md),
[ADR 0002](adr/0002-firma-xades-bes.md),
[ADR 0003](adr/0003-consulta-masiva.md) y
[ADR 0004](adr/0004-lotes.md).
