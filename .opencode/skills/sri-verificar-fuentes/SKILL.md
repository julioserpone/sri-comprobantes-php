---
name: SRI Verificar Fuentes
description: Verificar endpoints, operaciones y códigos del SRI contra los WSDL y la ficha oficial, y actualizar la documentación con fecha y fuente. Úsela cuando haya dudas sobre un endpoint, un campo o un cambio normativo.
---

# SRI Verificar Fuentes

## Cuándo usar

- Antes de agregar/cambiar un endpoint u operación.
- Cuando un usuario reporta un comportamiento distinto al documentado.
- Al actualizar la ficha, los XSD o una resolución.
- Cuando una afirmación no tiene fuente clara.

## Procedimiento

1. **No asumir.** Identifique la afirmación exacta a verificar.
2. **WSDL:** descargue el contrato de pruebas y producción y confirme
   `wsdl:operation`, elementos de request/response y `soap:address`.
3. **Ficha:** localice la sección/página y la versión. Anote fecha.
4. **Resolución:** si aplica, confirme publicación y vigencia en el Registro
   Oficial; no infiera la fecha.
5. **Registre:** fuente, fecha, entorno y resultado.
6. **Actualice** `docs/guia-tecnica-sri.md`, `docs/fuentes-oficiales.md` y
   `CHANGELOG.md`.
7. **Pruebe** que el código sigue el contrato (operación y argumentos).

## Comandos

Ver [references/verificacion.md](references/verificacion.md).

Resumen:

```bash
curl -sS --http1.1 --compressed -A 'Mozilla/5.0' \
  'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl' \
  -o /tmp/recepcion.wsdl
```

Luego:

```bash
grep -oE 'wsdl:operation name="[^"]+"' /tmp/recepcion.wsdl
grep -oE 'xs:element name="[^"]+"' /tmp/recepcion.wsdl
grep -oE 'soap:address location="[^"]+"' /tmp/recepcion.wsdl
```

## Reglas

- No invente una URL "parecida" si la oficial no responde.
- No use endpoints sin `Offline` para recepción/autorización: no responden.
- Distinga `Confirmado`, `Inferencia` y `Validar`.
- No copie bloques largos de la ficha; resuma y cite.
- No incluya datos de contribuyentes reales.
- Si no puede verificar, deje el punto como pendiente y pregunte.

## Salida esperada

Un resumen con:

- afirmación verificada;
- fuente exacta (documento, sección, WSDL, fecha);
- entorno (pruebas/producción);
- cambios realizados;
- pendientes de validar.
