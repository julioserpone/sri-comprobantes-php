# Seguridad

## Versiones soportadas

| Versión | Soporte |
| --- | --- |
| 0.1.x | Sí, versión actual |

El proyecto está en estado alfa. No se garantiza soporte de ramas antiguas.

## Reportar una vulnerabilidad

**No abra un issue público** con detalles explotables, certificados, claves,
contraseñas, XML reales o datos fiscales.

Use el **reporte privado de vulnerabilidades** de GitHub:

1. Abra la pestaña **Security** del repositorio.
2. Elija **Advisories** → **Report a vulnerability**.
3. Incluya: versión, entorno, pasos de reproducción, impacto y una prueba de
   concepto mínima.

Si el reporte privado no está disponible, contacte a
[@julioserpone](https://github.com/julioserpone) por GitHub sin publicar el
detalle.

## Alcance

Se consideran vulnerabilidades:

- manejo inseguro de certificados o claves privadas;
- XXE o expansión de entidades en la validación XML;
- inyección de comandos, rutas o XML;
- SSRF en el transporte;
- escritura fuera del directorio permitido (path traversal);
- exposición de secretos en logs o excepciones;
- dependencias con vulnerabilidades conocidas;
- bypass de validaciones de clave o tamaño.

Quedan fuera del alcance:

- errores de negocio del SRI;
- indisponibilidad o cambios de sus endpoints;
- rechazos tributarios;
- configuraciones inseguras del usuario (por ejemplo, desactivar TLS).

## Tiempos de respuesta

Es un proyecto mantenido por una persona. Se intentará acusar recibo en un
plazo razonable y coordinar la divulgación. No hay bug bounty.

## Uso seguro

- No versione `.env`, `.p12`, `.pfx`, `.pem` ni `.key`.
- Use TLS con verificación de certificado.
- Guarde secretos en un gestor de secretos, no en el código.
- Restrinja permisos del almacenamiento (0700/0600).
- No loguee XML completos ni claves privadas.
- Valide la vigencia y revocación del certificado antes de firmar.
- Mantenga las dependencias actualizadas y revise Dependabot.
- Pruebe en el ambiente de certificación antes de producción.

## Divulgación

Se solicita un plazo razonable antes de publicar detalles. Se acreditará a quien
reporte, salvo que prefiera el anonimato.
