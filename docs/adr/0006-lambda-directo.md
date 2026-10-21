# ADR 0006: adaptador para firmador Lambda directo

- **Estado:** aceptado
- **Fecha:** 2026-10-20

## Contexto

El paquete no implementa XAdES-BES (ADR 0002). El firmador
[`sri-xades-signer`](https://github.com/julioserpone/sri-xades-signer) puede desplegarse como una
Lambda compartida que lee el PKCS#12 de cada inquilino desde S3. El backend Laravel necesita una
forma explícita de invocarla sin acoplar el paquete a un SDK pesado.

## Decisión

Agregar:

- `Signature\LambdaInvoker`: contrato para invocar el firmador remoto.
- `Signature\CallableLambdaInvoker`: adaptador para pruebas o clientes propios.
- `Signature\AwsLambdaInvoker`: adaptador para `Aws\Lambda\LambdaClient` del SDK de AWS.
- `Signature\LambdaSigner`: implementación de `Signer` que representa a un inquilino y envía
  `{tenantUuid,password,xmlBase64}`.

`aws/aws-sdk-php` queda como dependencia **sugerida**, no obligatoria. El paquete sigue sin
dependencias runtime adicionales y `LambdaSigner` se puede probar con un invocador falso.

## Consecuencias

- El proyecto consumidor instala el SDK de AWS solo si usa `AwsLambdaInvoker`.
- El password del PKCS#12 viaja al firmador por la invocación IAM/TLS; no se registra.
- `LambdaSigner` debe construirse por inquilino; no se reutiliza para otro UUID.
- Los errores remotos se convierten en `SignatureException` con mensajes seguros.
- La firma sigue siendo responsabilidad del firmador Java; el paquete no implementa XAdES.

## Alternativas consideradas

- **Obligar a `aws/aws-sdk-php`:** aumenta el peso para todos los usuarios, incluso los que usan
  un HSM o un firmador local.
- **Implementar SigV4 en el paquete:** duplica una responsabilidad crítica y difícil de mantener.
- **Usar solo `CallbackSigner`:** funciona, pero deja al usuario el contrato JSON, la codificación
  y el manejo de errores; el adaptador reduce errores de integración.
