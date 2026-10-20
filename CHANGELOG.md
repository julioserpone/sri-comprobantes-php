# Changelog

Todos los cambios relevantes se documentan aquí. El formato sigue
[Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el proyecto usa
[SemVer](https://semver.org/lang/es/).

## [Unreleased]

### Por hacer

- Firmador XAdES-BES de referencia (fuera del alcance actual; ver ADR 0002).
- Generadores de XML por tipo de comprobante (fuera del alcance actual; ver
  ADR 0005).
- Persistencia transaccional para múltiples nodos.

## [0.1.1] - 2026-10-20

### Corregido

- `ConsultaResult` ignora fechas inválidas en lugar de lanzar una excepción.
- `AutorizacionResult::estado()` maneja explícitamente la ausencia de
  autorizaciones.
- El script de consulta usa la propiedad `mensajes` del DTO.
- CI ejecuta `composer audit --locked`.

### Documentación

- Instalación vía VCS mientras el paquete no esté en Packagist.
- Limitación de rulesets en repositorios privados sin GitHub Pro.

## [0.1.0] - 2026-10-20

### Añadido

- `AccessKeyGenerator` con estructura de 49 dígitos y módulo 11.
- `SriEnvironment` y `SriService` con endpoints de pruebas/producción.
- `SoapTransport` con TLS, timeouts y WSDL perezoso.
- Clientes de recepción, autorización individual/lote y consultas.
- DTO inmutables para recepción, autorización, lote, consulta y factura
  negociable.
- Catálogo `SriError` con códigos y advertencias de la ficha v2.34.
- `RetryPolicy` con backoff y jitter para fallos de transporte.
- `EmisionService` para enviar y esperar autorización.
- `XsdValidator` y `LoteBuilder` (Tabla 8, 50 comprobantes/500 KB).
- `Signer`, `CallbackSigner` y `P12Certificate`.
- `ComprobanteRepository` y almacenamiento local atómico con permisos 0600.
- Pruebas unitarias, PHPStan nivel 8, PHP-CS-Fixer y CI.
- Documentación técnica, ADRs, plantillas de GitHub y skills para agentes.

### Seguridad

- Sin credenciales ni certificados en el repositorio.
- Validación TLS estricta por defecto.
- Límite de tamaño y escape de CDATA en lotes.

[Unreleased]: https://github.com/julioserpone/sri-comprobantes-php/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/julioserpone/sri-comprobantes-php/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/julioserpone/sri-comprobantes-php/releases/tag/v0.1.0
