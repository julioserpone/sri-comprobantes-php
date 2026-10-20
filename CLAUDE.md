# Claude

Lea y siga [`AGENTS.md`](AGENTS.md). Es la fuente única de instrucciones del
proyecto.

Recordatorios críticos:

- No invente endpoints, campos ni códigos del SRI; cite ficha/WSDL y fecha.
- No implemente un firmador XAdES-BES casero; use `Signature\Signer`.
- No reintente errores de validación; sí fallos de transporte.
- No incluya certificados, claves, `.env` ni XML reales.
- Ejecute `composer check` antes de proponer un cambio.

Documentación: [`README.md`](README.md),
[`docs/guia-tecnica-sri.md`](docs/guia-tecnica-sri.md),
[`docs/arquitectura.md`](docs/arquitectura.md).
