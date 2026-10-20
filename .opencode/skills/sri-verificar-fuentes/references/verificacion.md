# Verificación de WSDL

## Descargar

```bash
BASE_PRUEBAS='https://celcer.sri.gob.ec/comprobantes-electronicos-ws'
BASE_PROD='https://cel.sri.gob.ec/comprobantes-electronicos-ws'

for base in "$BASE_PRUEBAS" "$BASE_PROD"; do
  for servicio in \
    RecepcionComprobantesOffline \
    AutorizacionComprobantesOffline \
    ConsultaComprobante \
    ConsultaFactura
  do
    curl -sS --http1.1 --compressed \
      -A 'Mozilla/5.0' \
      -o "/tmp/${servicio}-$(basename "$base").wsdl" \
      -w "%{http_code} ${base}/${servicio}\n" \
      "${base}/${servicio}?wsdl"
  done
done
```

## Inspeccionar

```bash
grep -oE 'wsdl:operation name="[^"]+"' /tmp/RecepcionComprobantesOffline-*.wsdl
grep -oE 'xs:element name="[^"]+"' /tmp/ConsultaComprobante-*.wsdl
grep -oE 'soap:address location="[^"]+"' /tmp/*.wsdl
```

Compruebe:

- nombre exacto de la operación;
- nombre exacto del parámetro (`xml`, `claveAcceso`,
  `claveAccesoComprobante`, `claveAccesoLote`);
- tipo (`base64Binary` para el XML de recepción);
- raíz de respuesta;
- endpoint del `soap:address`.

## Notas

- Algunos WSDL son pequeños porque definen todo inline; no asuma que están
  incompletos por el tamaño.
- Un `200` con HTML no es un WSDL válido: valide que empiece por
  `<?xml` y contenga `wsdl:definitions`.
- Guarde la fecha y el hash si necesita evidencia:

```bash
shasum -a 256 /tmp/*.wsdl
```
