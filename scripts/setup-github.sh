#!/usr/bin/env bash
#
# Configura un repositorio de GitHub con las reglas del proyecto.
# Requiere gh autenticado con permisos de administración sobre el repo.
#
# Uso:
#   ./scripts/setup-github.sh [owner/repo]
#
set -euo pipefail

REPO="${1:-julioserpone/sri-comprobantes-php}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if ! command -v gh >/dev/null 2>&1; then
  echo "Error: GitHub CLI (gh) no está instalado." >&2
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "Error: gh no está autenticado. Ejecute: gh auth login" >&2
  exit 1
fi

echo "==> Repositorio: ${REPO}"

gh api -X PATCH "repos/${REPO}" \
  -f description="Paquete PHP para emitir y consultar comprobantes electrónicos del SRI de Ecuador (esquema off-line)." \
  -F has_issues=true \
  -F has_wiki=false \
  -F has_projects=false \
  -F allow_squash_merge=true \
  -F allow_merge_commit=false \
  -F allow_rebase_merge=false \
  -F delete_branch_on_merge=true \
  -F allow_auto_merge=true >/dev/null

gh repo edit "${REPO}" \
  --add-topic sri \
  --add-topic ecuador \
  --add-topic facturacion-electronica \
  --add-topic comprobantes-electronicos \
  --add-topic php >/dev/null

echo "==> Habilitando alertas de seguridad"
gh api -X PUT "repos/${REPO}/vulnerability-alerts" >/dev/null || \
  echo "   Aviso: no se pudieron habilitar las alertas de vulnerabilidades."
gh api -X PUT "repos/${REPO}/automated-security-fixes" >/dev/null || \
  echo "   Aviso: no se pudieron habilitar las correcciones automáticas."

if gh api "repos/${REPO}/rulesets" --jq '.[].name' 2>/dev/null | grep -qx 'main-protection'; then
  echo "==> El ruleset 'main-protection' ya existe; no se modifica."
else
  echo "==> Creando ruleset 'main-protection'"
  if gh api -X POST "repos/${REPO}/rulesets" --input "${ROOT}/.github/rulesets/main.json" >/dev/null; then
    echo "   Ruleset creado."
  else
    echo "   Aviso: no se pudo crear el ruleset."
    echo "   En repos privados, algunas reglas requieren un plan de GitHub compatible."
    echo "   También puede configurarlo en Settings > Rules > Rulesets."
  fi
fi

echo
echo "Configuración aplicada. Verifique:"
echo "  gh ruleset list --repo ${REPO}"
echo "  gh api repos/${REPO}/branches/main/protection"
