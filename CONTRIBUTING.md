# Contribuir

Gracias por contribuir a `sri-comprobantes-php`. Este proyecto trata temas
tributarios y criptográficos: la precisión importa más que la velocidad.

## Requisitos

- PHP 8.2+ con `soap`, `openssl`, `dom` y `libxml`.
- Composer 2.x.
- Git.
- Para cambios de integración: acceso al ambiente de pruebas del SRI y un
  certificado de pruebas **que no debe subirse al repositorio**.

## Preparación

```bash
git clone https://github.com/julioserpone/sri-comprobantes-php.git
cd sri-comprobantes-php
composer install
composer check
```

## Comandos

| Comando | Uso |
| --- | --- |
| `composer test` | PHPUnit |
| `composer analyse` | PHPStan nivel 8 |
| `composer cs` | PHP-CS-Fixer en modo dry-run |
| `composer check` | Los tres anteriores |
| `vendor/bin/php-cs-fixer fix` | Aplicar formato |

## Ramas y commits

- Rama principal: `main`.
- Ramas: `feat/...`, `fix/...`, `docs/...`, `refactor/...`, `test/...`.
- Commits: [Conventional Commits](https://www.conventionalcommits.org/):
  `feat: ...`, `fix: ...`, `docs: ...`, `test: ...`, `chore: ...`.
- Un PR debe tener un propósito. Evite mezclar refactors masivos con cambios de
  comportamiento.

## Reglas técnicas

1. **No inventar datos del SRI.** Todo endpoint, operación, campo, código o
   límite debe citar la ficha (sección/página), el WSDL verificado o una
   resolución. Indique la fecha de verificación.
2. **Distinguir confirmado / inferencia / validar.** Si no está seguro, use la
   marca `Validar` y explique qué falta.
3. **No incluir secretos.** Nada de `.p12`, `.pfx`, `.pem`, `.key`, `.env`,
   contraseñas, XML reales con datos personales ni capturas con RUC/cédulas.
4. **No implementar XAdES-BES ad hoc.** Use o proponga una implementación
   validada; actualice el ADR correspondiente.
5. **No reintentar errores de validación.** Un 35/36/39/47/48/49/52/67 no se
   arregla con backoff.
6. **Mantener PHP 8.2.** No use sintaxis exclusiva de 8.3+ sin subir el
   requisito en `composer.json` y en CI.
7. **Sin dependencias runtime nuevas** sin un ADR que justifique el peso, la
   licencia y la superficie de ataque.
8. **Código tipado.** `declare(strict_types=1)`, PSR-4, DTO `readonly` cuando
   corresponda, sin estado global.
9. **Pruebas.** Todo cambio de comportamiento necesita una prueba. Las pruebas
   no deben llamar a los servidores del SRI.
10. **Compatibilidad.** No rompa la firma de métodos públicos sin una nota de
    migración en `CHANGELOG.md`.

## Cambios en la documentación del SRI

1. Descargue la ficha vigente y anote versión/fecha.
2. Verifique los WSDL afectados (pruebas y producción).
3. Actualice `docs/guia-tecnica-sri.md` citando sección/página.
4. Actualice `docs/fuentes-oficiales.md` si cambian enlaces o vigencias.
5. Agregue una entrada en `CHANGELOG.md`.
6. Ejecute `composer check`.

## Pull requests

Complete la plantilla. El PR debe incluir:

- [ ] Descripción del problema y del cambio.
- [ ] Fuente oficial o justificación técnica.
- [ ] Pruebas nuevas/actualizadas.
- [ ] `composer check` en verde.
- [ ] Sin secretos ni datos personales.
- [ ] Documentación actualizada (si aplica).
- [ ] Impacto de compatibilidad declarado.

Un mantenedor revisará el PR. Los cambios que afecten firma, clave de acceso,
endpoints o códigos de error requieren revisión adicional.

## Reportar errores

Use las plantillas de GitHub. Para vulnerabilidades, siga
[SECURITY.md](SECURITY.md); **no** abra un issue público con detalles
explotables.

## Licencia

Al contribuir, acepta que su aporte se publique bajo la licencia MIT del
proyecto.
