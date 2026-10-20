# Fuentes oficiales y verificación

## Documentos

| Documento | Versión / fecha | Uso |
| --- | --- | --- |
| Ficha Técnica de Comprobantes Electrónicos Offline | v2.34, julio 2026 | Reglas, tablas, firma, errores, anexos |
| WSDL Recepción | consultado 20/10/2026 | Operación y contrato |
| WSDL Autorización | consultado 20/10/2026 | Operación individual y lote |
| WSDL Consulta | consultado 20/10/2026 | Estado de autorización |
| WSDL Factura | consultado 20/10/2026 | Factura negociable |
| Resolución NAC-DGERCGC24-00000035 | vigencia a validar | Plazo de transmisión |
| Resolución NAC-DGERCGC26-00000027 | vigencia a validar | Anexo 26 (RUC del proveedor) |

## Enlaces

- Portal del SRI: <https://www.sri.gob.ec/>
- Comprobantes electrónicos (portal): busque la sección "Comprobantes
  electrónicos" y la ficha vigente. Los enlaces directos cambian con frecuencia.
- ETSI XAdES v1.3.2:
  <http://uri.etsi.org/01903/v1.3.2/ts_101903v010302p.pdf>
- XML Signature: <https://www.w3.org/TR/xmldsig-core/>

## Cómo verificar los WSDL

```bash
for servicio in \
  RecepcionComprobantesOffline \
  AutorizacionComprobantesOffline \
  ConsultaComprobante \
  ConsultaFactura
do
  curl -sS --http1.1 --compressed \
    -A 'Mozilla/5.0' \
    -o "/tmp/${servicio}.wsdl" \
    -w "${servicio} HTTP %{http_code}\n" \
    "https://celcer.sri.gob.ec/comprobantes-electronicos-ws/${servicio}?wsdl"
done
```

Revise en el WSDL:

- `wsdl:operation name="..."`;
- `xs:element name="..."` del request/response;
- `soap:address location="..."`;
- tipos de los parámetros (`base64Binary`, `string`).

No confíe en un endpoint solo porque aparece en un blog. Verifique el WSDL y
registre la fecha.

## Cómo actualizar la documentación

1. Descargue la ficha vigente y anote versión/fecha.
2. Compare las tablas modificadas (tipos, errores, anexos, firma).
3. Verifique los WSDL en pruebas y producción.
4. Actualice `docs/guia-tecnica-sri.md` con la sección/página de origen.
5. Agregue una entrada en `CHANGELOG.md`.
6. Ejecute `composer check`.
7. Abra un PR citando la fuente y la fecha de verificación.

## Advertencia

Los enlaces, versiones, certificados y reglas pueden cambiar. Este repositorio
no es una fuente normativa. La ficha oficial y los XSD publicados por el SRI
prevalecen.
