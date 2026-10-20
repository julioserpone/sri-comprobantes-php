# Reglas y configuración de GitHub

Este documento describe cómo está configurado el repositorio y cómo replicar
las reglas en otro fork. La configuración se aplica con GitHub CLI y queda
versionada en `.github/`.

## 0. Estado actual (20/10/2026)

- Repositorio **público**.
- Ruleset **`main-protection`** (ID `25503138`) con `enforcement: active` sobre
  `refs/heads/main`.
- **PR obligatorio** para fusionar. Se permiten 0 aprobaciones (auto-fusión)
  porque el proyecto tiene un mantenedor; si el equipo crece, suba el mínimo a 1.
- **Historial lineal**, conversaciones resueltas y prohibición de borrado y
  force-push.
- **Check requerido:** `ci-success`, que solo pasa si toda la matriz de CI
  (PHP 8.2, 8.3, 8.4 y 8.5), el estilo, PHPStan y `composer audit` están OK.
- **Sin bypass**, incluido el administrador (`current_user_can_bypass: never`).
- El ruleset se aplicó después de hacer público el repositorio. En un repo
  privado sin GitHub Pro, GitHub rechaza los rulesets con HTTP 403.

## 1. Configuración del repositorio

| Ajuste | Valor | Motivo |
| --- | --- | --- |
| Visibilidad | Pública | Código y documentación abiertos; sin secretos ni datos reales |
| Rama por defecto | `main` | Convención |
| Issues | Habilitados | Seguimiento |
| Wiki / Projects | Deshabilitados | Evitar documentación duplicada |
| Merge | Solo squash | Historial lineal y PR atómicos |
| Borrar rama al fusionar | Sí | Limpieza |
| Auto-merge | Habilitado | Dependabot y PR pequeños |
| Topics | `sri`, `ecuador`, `facturacion-electronica`, `comprobantes-electronicos`, `php` | Descubrimiento |

Script idempotente:

```bash
./scripts/setup-github.sh julioserpone/sri-comprobantes-php
```

El script requiere `gh auth login` con permisos de administración. Si el repo
vuelve a ser privado y el plan de GitHub no permite rulesets, muestra un aviso y
continúa sin bloquear el resto de la configuración.

## 2. Protección de `main`

El ruleset versionado está en
[`.github/rulesets/main.json`](../.github/rulesets/main.json).

Reglas:

- **No borrar** la rama.
- **No force-push** (`non_fast_forward`).
- **Historial lineal** (`required_linear_history`).
- **Resolver conversaciones** antes de fusionar.
- **PR obligatorio**; se permite auto-fusión con 0 aprobaciones para un
  mantenedor único. Si el equipo crece, suba
  `required_approving_review_count` a 1.
- **Check requerido:** `ci-success`, con política estricta (la rama debe estar
  actualizada).
- **Sin bypass**, incluido el administrador (`current_user_can_bypass: never`).

Aplicar:

```bash
gh api -X POST repos/julioserpone/sri-comprobantes-php/rulesets \
  --input .github/rulesets/main.json
```

Verificar:

```bash
gh ruleset list --repo julioserpone/sri-comprobantes-php
gh api repos/julioserpone/sri-comprobantes-php/rulesets
```

> El check `ci-success` debe existir al menos una vez (workflow ejecutado) antes
> de exigirlo; de lo contrario, los PR quedarán bloqueados. El workflow CI ya
> define un job aggregator con ese nombre.

### Alternativa: branch protection clásica

Si su plan no soporta rulesets, use la API clásica:

```bash
gh api -X PUT repos/julioserpone/sri-comprobantes-php/branches/main/protection \
  -H "Accept: application/vnd.github+json" \
  --input - <<'JSON'
{
  "required_status_checks": {
    "strict": true,
    "contexts": ["ci-success"]
  },
  "enforce_admins": true,
  "required_pull_request_reviews": null,
  "restrictions": null,
  "allow_force_pushes": false,
  "allow_deletions": false,
  "required_conversation_resolution": true
}
JSON
```

En un repositorio privado, la protección de rama clásica puede requerir un plan
de pago. Verifique el resultado; no asuma que la regla se aplicó.

## 3. Flujo de trabajo

1. Cree una rama desde `main`: `feat/...`, `fix/...`, `docs/...`.
2. Haga commits pequeños con Conventional Commits.
3. Abra un PR con la plantilla.
4. Espere `ci-success` (PHP 8.2–8.5, estilo, PHPStan, PHPUnit).
5. Fusione con **squash**.
6. Elimine la rama.

No se permite push directo a `main` una vez activo el ruleset. Para cambios de
emergencia, use un PR con la etiqueta `hotfix` y documente la causa.

## 4. Checks requeridos

Workflow: [`.github/workflows/ci.yml`](../.github/workflows/ci.yml).

| Check | Qué valida |
| --- | --- |
| `tests (PHP 8.2)` … `tests (PHP 8.5)` | Matriz de pruebas |
| `ci-success` | Agregador; es el único requerido por el ruleset |

El job `ci-success` depende de toda la matriz. Si una versión falla, el
agregador no se ejecuta y el PR queda bloqueado.

## 5. Releases

Workflow: [`.github/workflows/release.yml`](../.github/workflows/release.yml).

1. Actualice `CHANGELOG.md`.
2. Verifique `composer check`.
3. Cree un tag SemVer: `git tag -a v0.1.0 -m "v0.1.0"`.
4. Empuje el tag: `git push origin v0.1.0`.
5. El workflow ejecuta la calidad y crea el release con notas generadas.

Convención:

- `0.x.y`: API inestable (alfa/beta).
- `1.0.0`: primera API estable.
- Cambios incompatibles: major.
- Nuevas funcionalidades compatibles: minor.
- Correcciones: patch.

## 6. Seguridad del repositorio

- **Dependabot alerts** y **automated security fixes** habilitados.
- **Secret scanning** y **push protection** cuando el plan lo permita. En repos
  privados puede requerir GitHub Advanced Security.
- **Private vulnerability reporting** habilitado; enlace en la plantilla de
  issues.
- Nunca commitear `.env`, `.p12`, `.pfx`, `.pem`, `.key`, XML reales ni datos
  personales.
- Revise los PR de Dependabot; no los fusione a ciegas si cambian una major.
- Ejecute `composer audit` antes de un release (si su entorno lo soporta).

## 7. CODEOWNERS

`.github/CODEOWNERS` asigna todo a `@julioserpone`. Si se agregan mantenedores,
divida por área:

```text
/src/Transport/   @persona-a
/src/Signature/   @persona-b
/docs/            @persona-c
```

Con `require_code_owner_review` activo, los cambios que toquen firma o
transporte requerirán al owner correspondiente.

## 8. Variables y secretos

El CI no necesita secretos para las pruebas unitarias. El workflow de release
usa el `GITHUB_TOKEN` automático con `contents: write`.

Si en el futuro se agregan pruebas de integración:

- Use **GitHub Environments** (`certification`) con revisores.
- Guarde el PKCS#12 y la contraseña como secretos separados; nunca en el repo.
- No imprima el certificado ni el XML firmado.
- Restrinja el environment a `main` y a tags.

## 9. Checklist de configuración inicial

- [ ] Repo creado y primer push.
- [ ] `main` como rama por defecto.
- [ ] Topics y descripción.
- [ ] Issues habilitados; wiki/projects deshabilitados.
- [ ] Squash-only y borrado de ramas.
- [ ] Ruleset `main-protection` aplicado.
- [ ] Check `ci-success` verde al menos una vez.
- [ ] Dependabot y alertas de seguridad.
- [ ] Private vulnerability reporting.
- [ ] CODEOWNERS.
- [ ] Primer release `v0.1.0`.
