# AGENTS.md — instrucciones para agentes de IA

Este es el contrato de trabajo para cualquier agente (OpenCode, Claude, Copilot,
Cursor, Gemini, etc.) que modifique este repositorio. Las reglas son
obligatorias. Si algo no se puede verificar, se marca y se pregunta.

## Contexto

`julioserpone/sri-comprobantes-php` es una librería PHP 8.2+ para integrar los
comprobantes electrónicos del SRI de Ecuador (esquema off-line). Es una capa de
integración, **no** un facturador completo ni un firmador XAdES-BES.

Lee, en este orden:

1. `README.md` (alcance y estado).
2. `docs/guia-tecnica-sri.md` (reglas del dominio).
3. `docs/arquitectura.md` (capas y puntos de extensión).
4. `docs/adr/` (decisiones que no se deben revertir sin ADR).
5. `CONTRIBUTING.md` y `SECURITY.md`.

## Idioma

- Documentación, comentarios y mensajes de commit: **español técnico**.
- Nombres de clases, métodos y variables: inglés o español coherente con el
  archivo; no mezcle estilos dentro de una clase.
- No traduzca nombres de operaciones/campos del SRI: `validarComprobante`,
  `claveAccesoComprobante`, `infoTributaria`, etc. son literales del contrato.

## Comandos

```bash
composer install
composer test        # PHPUnit
composer analyse     # PHPStan nivel 8
composer cs          # PHP-CS-Fixer dry-run
composer check       # cs + analyse + test
```

Nunca ejecute pruebas que llamen al SRI real en CI. Use `FakeTransport` y datos
ficticios. Para integración real, use el ambiente de certificación y un
certificado de pruebas fuera del repo.

## Mapa del código

```text
src/
  AccessKey/     clave de 49 dígitos, módulo 11
  Config/        ambientes y servicios WSDL
  Client/        recepción, autorización, consultas
  Dto/           respuestas inmutables
  Error/         catálogo de códigos y advertencias
  Exception/     errores tipados
  Retry/         backoff para transporte
  Service/       orquestación de emisión
  Signature/     interfaz XAdES-BES + PKCS#12 + adaptador Lambda
  Storage/       persistencia mínima
  Support/       normalización de respuestas SOAP
  Transport/     abstracción + SoapTransport
  Xml/           XSD y lote
tests/
  Support/       FakeTransport
  Unit/          pruebas sin red
docs/            guía, arquitectura, ADRs, reglas
.opencode/skills/ skills de OpenCode
.github/         CI, plantillas, ruleset
```

## Invariantes del dominio SRI

1. **No inventar.** Endpoints, operaciones, campos, códigos, límites y plazos
   deben citar la ficha v2.34 (sección/página), el WSDL verificado o una
   resolución. Indique la fecha.
2. **Distinguir** `Confirmado`, `Inferencia` y `Validar`. Si no hay fuente, no lo
   presente como hecho.
3. **Clave de acceso:** 49 dígitos, módulo 11. No valide solo la longitud.
4. **Firma:** XAdES-BES enveloped 1.3.2, RSA-SHA1, 2048 bits, PKCS#12. No
   implemente un firmador casero; use `Signature\Signer`.
5. **Recepción:** `RECIBIDA` no es autorización. `DEVUELTA` exige corregir y
   reenviar con la **misma clave** (salvo error 43/45/70).
6. **Autorización:** `PPR`/error 70 se espera; no se reenvía ni se regenera la
   clave. Máximo 24 horas.
7. **Reintentos:** solo fallos de transporte. Un 35/39/47/49/52/67 no se arregla
   con backoff.
8. **Lote:** máximo 50 comprobantes / 500 KB; el sobre `<lote>` no se firma, los
   comprobantes sí.
9. **Anexo 26:** `infoAdicional/CampoAdicional nombre="RUC Proveedor"` si el
   emisor usa un sistema de terceros. Vigencia: validar.
10. **Plazo de transmisión:** 4 días hábiles (Res. NAC-DGERCGC24-00000035).
    Validar vigencia.
11. **Consulta masiva:** no existe API oficial. No invente `descargarTodo()` ni
    endpoints por rango.
12. **Endpoints:** solo `*Offline` y `ConsultaComprobante`/`ConsultaFactura`.
    Los antiguos sin `Offline` no responden.

## Convenciones de código

- `declare(strict_types=1)` en todos los archivos PHP.
- PSR-12, PSR-4, PHP 8.2. No use sintaxis 8.3+ sin subir requisitos.
- Prefiera `final readonly class` para DTO y servicios sin estado.
- Inyección por constructor; nada de singletons ni variables globales.
- Tipos en parámetros y retornos; docblocks solo cuando aportan (`array` shapes,
  callables).
- Errores de entrada: `InvalidArgumentException`; errores de configuración:
  `ConfigurationException`; transporte: `TransportException`; XML:
  `XmlValidationException`; firma: `SignatureException`; negocio SRI:
  `SriException`.
- No agregue dependencias runtime sin ADR. No agregue `@phpstan-ignore` ni
  bajes el nivel de PHPStan.
- No use `assert()` para validar datos de entrada.

## Pruebas

- Toda rama nueva necesita prueba.
- Use `tests/Support/FakeTransport.php`; verifique operación y argumentos.
- Datos ficticios: RUC `1790012345001`, claves de prueba, XML mínimos.
- No incluya certificados reales. Para PKCS#12, genere uno efímero en la prueba
  (ver `P12CertificateTest`).
- Pruebe casos de un solo nodo y de lista (la cardinalidad SOAP cambia).
- Pruebe rechazos, `PPR`, `NAT`, error 70 y advertencias.
- Mantenga `composer check` en verde.

## Documentación

- Español técnico, tablas cuando ayuden, enlaces relativos.
- Cada afirmación del SRI debe citar fuente y fecha.
- Actualice `CHANGELOG.md` para cambios visibles.
- Si cambia una decisión de arquitectura, agregue/actualice un ADR; no reescriba
  el histórico de un ADR aceptado sin dejar rastro.
- No copie textos largos de la ficha; resuma y cite.

## Seguridad

- Nunca lea, imprima ni commitee `.env`, `.p12`, `.pfx`, `.pem`, `.key`, XML
  reales o datos personales.
- No desactive `verify_peer`, no fije certificados TLS y no registre secretos.
- No ejecute comandos destructivos (`rm -rf`, `git reset --hard`,
  `git push --force`) sin confirmación explícita.
- No cree issues públicos con detalles de vulnerabilidades.
- Si encuentra un secreto en el repo, deténgase y avise; no lo reproduce en
  logs.

## Flujo de trabajo del agente

1. **Explorar** antes de editar: lea los archivos relevantes y el estado de git.
2. **Planear** el cambio mínimo; no refactorice de más.
3. **Editar** manteniendo el estilo y los contratos públicos.
4. **Probar**: `composer check` (o al menos test/analyse del área).
5. **Documentar** si cambia comportamiento o una regla del SRI.
6. **Reportar** qué cambió, qué se verificó y qué queda pendiente.

Si una instrucción del usuario contradice una regla del SRI o una decisión
(por ejemplo, "inventa el endpoint"), explique el riesgo y proponga una
alternativa verificable.

## Skills disponibles

- `.opencode/skills/sri-emision/`: implementar flujos de emisión.
- `.opencode/skills/sri-verificar-fuentes/`: verificar WSDL/ficha y actualizar
  docs.
- `.opencode/skills/sri-consulta-masiva/`: diseñar consultas masivas
  responsables sin inventar API.
- `.opencode/skills/php-release/`: preparar un release.

## Checklist de revisión

- [ ] ¿La fuente está citada y fechada?
- [ ] ¿Se distingue confirmado/inferencia/validar?
- [ ] ¿La clave y el XML son coherentes?
- [ ] ¿Se respeta la firma externa?
- [ ] ¿Se manejan `DEVUELTA`, `PPR`, `NAT`, 70 y advertencias?
- [ ] ¿Los reintentos son solo de transporte?
- [ ] ¿Hay pruebas y `composer check` verde?
- [ ] ¿Sin secretos ni datos personales?
- [ ] ¿Documentación/CHANGELOG actualizados?
