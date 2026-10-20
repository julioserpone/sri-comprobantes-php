---
name: PHP Release
description: Preparar y publicar una versión del paquete PHP con changelog, tag SemVer, CI y GitHub Release. Úsela cuando pidan "release", "nueva versión", "tag" o "publicar".
---

# PHP Release

## Antes de empezar

- Confirme que el árbol está limpio y que `main` está actualizado.
- Confirme el tipo de versión (major/minor/patch) con el usuario.
- Revise `CHANGELOG.md` y los ADR.
- No publique si `composer check` falla.

## Procedimiento

1. Actualice `CHANGELOG.md` con la versión y la fecha.
2. Ejecute:

   ```bash
   composer check
   ```

3. Cree el tag anotado:

   ```bash
   git tag -a v0.1.0 -m "v0.1.0"
   git push origin main
   git push origin v0.1.0
   ```

4. El workflow `.github/workflows/release.yml` ejecuta la calidad y crea el
   release con notas generadas.
5. Verifique:

   ```bash
   gh release view v0.1.0
   ```

## Reglas

- SemVer: `0.x` inestable; `1.0.0` estable.
- No reutilice un tag ni lo mueva después de publicarlo.
- No publique secretos ni artefactos con certificados.
- Si el release falla, corrija y cree un nuevo tag; no force-push el tag.
- Anote cambios incompatibles en el changelog.

## Checklist

Ver [references/release-checklist.md](references/release-checklist.md).
