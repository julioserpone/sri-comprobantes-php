# ADR 0002: no implementar un firmador XAdES-BES propio

- **Estado:** aceptado
- **Fecha:** 2026-10-20

## Contexto

El SRI exige XAdES-BES enveloped, esquema 1.3.2, RSA-SHA1 y certificado
PKCS#12. Una implementación incorrecta puede producir firmas que parecen
válidas localmente pero que el SRI rechaza (errores 39/40), con pérdida de
tiempo y riesgo operativo. La canonicalización, los namespaces, las referencias
y `SignedProperties` son sensibles.

## Decisión

No incluir un firmador XAdES-BES en este paquete. En su lugar:

- `Signature\Signer` define el contrato.
- `Signature\CallbackSigner` adapta un firmador externo (HSM, servicio,
  librería validada).
- `Signature\P12Certificate` carga el PKCS#12 y verifica que la clave
  corresponda al certificado.
- La documentación explica los requisitos y advierte que la firma debe
  certificarse contra el SRI.

## Consecuencias

- El usuario debe aportar un firmador. Es una dependencia explícita, no un
  agujero oculto.
- El paquete sigue siendo útil para transporte, estados, errores y consultas.
- No se promete una funcionalidad que no se puede verificar de extremo a
  extremo.

## Alternativas consideradas

- **Firmador casero con `openssl_sign` + DOM:** descartado para producción;
  requiere pruebas criptográficas y de interoperabilidad que este proyecto no
  puede garantizar.
- **Depender de una librería PHP de XAdES:** se puede hacer en el proyecto
  consumidor; no se impone una dependencia pesada ni una licencia adicional.
- **Firmar con un servicio externo:** soportado vía `CallbackSigner`.

## Referencias

- Ficha v2.34, sección 6 y Anexo 14.
- `docs/guia-tecnica-sri.md`, sección 7.
