---
name: SRI Emisión
description: Implementar o revisar flujos de emisión, recepción y autorización de comprobantes electrónicos del SRI. Úsela al agregar un tipo de comprobante, depurar DEVUELTA/NAT/PPR, cambiar EmisionService o los clientes SOAP.
---

# SRI Emisión

## Cuándo usar

- Agregar soporte para un tipo/versión de comprobante.
- Depurar un `DEVUELTA`, `NAT`, `PPR` o error 35/39/70.
- Cambiar `EmisionService`, `RecepcionClient` o `AutorizacionClient`.
- Diseñar el envío individual o por lote.

## Antes de tocar código

1. Lea `AGENTS.md` y `docs/guia-tecnica-sri.md`.
2. Identifique tipo, versión de esquema y anexos aplicables.
3. Localice el XSD oficial y los campos obligatorios. No invente campos.
4. Revise `docs/adr/0002-firma-xades-bes.md` y
   `docs/adr/0004-lotes.md`.

## Flujo

```text
datos → XML → clave (49) → XSD → firma externa → recepción
      → RECIBIDA → autorización → AUT/NAT/PPR → persistencia
```

1. **Clave:** `AccessKeyGenerator`; valide módulo 11 y coherencia con el XML.
2. **XML:** lo genera la aplicación; el paquete no lo construye.
3. **XSD:** `XsdValidator` con un esquema local versionado.
4. **Firma:** `Signature\Signer` (XAdES-BES externo). No firme con `openssl_sign`
   a mano.
5. **Recepción:** `RecepcionClient::validar()` o
   `EmisionService::enviar()`.
6. **Autorización:** `AutorizacionClient::consultar()` o
   `EmisionService::esperarAutorizacion()`.
7. **Persistencia:** guarde clave, XML firmado, estado y respuesta.

## Reglas de decisión

| Situación | Acción |
| --- | --- |
| `DEVUELTA` 35/36/47/48/49/67/82 | Corregir XML; reenviar misma clave |
| `DEVUELTA` 39/40/42 | Revisar firma/certificado/revocación |
| `DEVUELTA` 43/45 | Consultar autorización; no reenviar a ciegas |
| `DEVUELTA` 70 | Esperar; no reenviar ni cambiar clave |
| `PPR` | Esperar hasta 24 h; no regenerar clave |
| `NAT` | Corregir, reenviar misma clave, notificar |
| `AUT` | Guardar XML autorizado |
| Error de transporte | Reintentar con backoff |
| Error de validación | No reintentar; corregir |

## Implementación

- Use DTO inmutables y mantenga la compatibilidad de firmas públicas.
- Maneje respuestas con un solo nodo y con listas (`Arr::listOf`).
- No registre XML completos ni secretos.
- Añada pruebas con `FakeTransport`; no llame al SRI en la suite.
- Actualice `CHANGELOG.md` y la guía si cambia una regla.

## Verificación

```bash
composer check
```

Checklist completo: [references/checklist.md](references/checklist.md).

## No hacer

- Inventar endpoints, operaciones o códigos.
- Implementar XAdES-BES casero.
- Reintentar un XML inválido.
- Generar una clave nueva para un `PPR` o error 70.
- Incluir certificados o XML reales en pruebas.
