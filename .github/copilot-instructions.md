# Copilot instructions

Repo: librería PHP 8.2+ para comprobantes electrónicos del SRI de Ecuador
(esquema off-line). Lea `AGENTS.md` para el contrato completo.

- No invente endpoints, operaciones, campos, códigos ni límites. Cite la ficha
  v2.34 (sección/página), el WSDL o la resolución, con fecha.
- Use los endpoints `*Offline` y `ConsultaComprobante`/`ConsultaFactura`.
- Clave de acceso: 49 dígitos con módulo 11.
- No implemente XAdES-BES; use `Signature\Signer`.
- `RECIBIDA` no es autorización; `PPR`/error 70 se espera, no se reenvía.
- Reintente solo errores de transporte.
- Lote: máximo 50 comprobantes / 500 KB.
- Sin dependencias runtime nuevas sin ADR.
- PHP 8.2, `declare(strict_types=1)`, PSR-12, PHPStan 8, pruebas con
  `FakeTransport`.
- No incluya `.env`, `.p12`, `.pfx`, `.pem`, `.key` ni XML reales.
- Antes de terminar: `composer check`.
