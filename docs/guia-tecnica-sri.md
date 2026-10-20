# Guía técnica: comprobantes electrónicos del SRI (esquema off-line)

**Versión de referencia:** Ficha Técnica de Comprobantes Electrónicos Offline
**v2.34**, actualizada en **julio de 2026**.
**Verificación de endpoints:** 20/10/2026.
**Paquete:** `julioserpone/sri-comprobantes-php` (PHP 8.2+).

Esta guía resume el flujo técnico, los servicios, la firma, los estados y los
códigos publicados por el SRI. No sustituye a la ficha oficial ni a los XSD
vigentes. Cuando una afirmación no está confirmada por una fuente oficial, se
marca explícitamente.

## Cómo leer esta guía

| Marca | Significado |
| --- | --- |
| **Confirmado** | Está en la ficha v2.34, en un WSDL oficial verificado o en una resolución citada. |
| **Inferencia** | Se deduce del comportamiento documentado o de la arquitectura, pero no está escrito literalmente. |
| **Validar** | Requiere comprobación en el ambiente de certificación, en el Registro Oficial o con el SRI antes de producción. |

Regla del proyecto: **no se inventan endpoints, operaciones, campos ni códigos**.
Si un dato no se puede verificar, se documenta como pendiente.

## 1. Resumen ejecutivo

El esquema off-line del SRI funciona así:

1. El emisor genera un XML por comprobante, conforme al XSD del tipo y versión
   correspondientes.
2. Calcula una clave de acceso única de 49 dígitos.
3. Firma el XML con XAdES-BES enveloped.
4. Envía el XML firmado (individual o en lote) al servicio de recepción.
5. El SRI responde `RECIBIDA` o `DEVUELTA`.
6. El emisor consulta el servicio de autorización hasta obtener `AUTORIZADO`
   (`AUT`) o `NO AUTORIZADO` (`NAT`).
7. Si está autorizado, conserva y entrega el XML autorizado y el RIDE.
8. Si fue devuelto o no autorizado, corrige y reenvía con la **misma clave**,
   salvo los casos en que el SRI indique lo contrario (por ejemplo, clave ya
   registrada o en procesamiento).

El SRI puede tardar hasta **24 horas** en procesar un comprobante. La obligación
de verificar el estado autorizado es del contribuyente.

## 2. Marco y alcance

- **Normativa:** el esquema off-line se sustenta en la ficha técnica y en las
  resoluciones del SRI. La ficha v2.34 incluye el Anexo 26 sobre el RUC del
  proveedor de sistemas de facturación (Resolución
  `NAC-DGERCGC26-00000027`).
- **Plazo de transmisión:** la Resolución `NAC-DGERCGC24-00000035` establece un
  plazo de **4 días hábiles** para transmitir los comprobantes. **Validar** la
  vigencia, el cómputo y las excepciones antes de implementar alertas.
- **Certificación:** antes de producción, el contribuyente debe certificar su
  solución en el ambiente de pruebas con cada tipo de comprobante que emita.
- **Alcance de esta guía:** emisión, recepción, autorización y consulta. No
  cubre la declaración tributaria, el RIDE en detalle ni la devolución
  automática de IVA (que tiene sus propios servicios y códigos).

## 3. Flujo end-to-end

```text
┌──────────────┐  1. generar XML   ┌──────────────┐
│ Datos de la  │ ────────────────> │ XML sin firma │
│ transacción  │                   └──────┬───────┘
└──────────────┘                          │ 2. clave de acceso (49)
                                          ▼
                                   ┌──────────────┐
                                   │ XML + clave  │
                                   └──────┬───────┘
                                          │ 3. XAdES-BES
                                          ▼
                                   ┌──────────────┐
                                   │ XML firmado  │
                                   └──────┬───────┘
                                          │ 4. validarComprobante
                                          ▼
                        ┌────────────────────────────────┐
                        │ RecepcionComprobantesOffline   │
                        └───────────────┬────────────────┘
                                        │
                     RECIBIDA ◄─────────┴─────────► DEVUELTA
                        │                              │
                        │ 5. autorizacionComprobante   │ corregir y
                        ▼                              │ reenviar misma clave
             ┌─────────────────────┐                   │
             │ AUT / NAT / PPR     │                   │
             └─────────┬───────────┘                   │
                       │                               │
          AUTORIZADO   │   NO AUTORIZADO / PPR         │
              ▼        │        ▼                      ▼
      guardar XML      │   corregir / esperar      (volver al inicio)
      autorizado       │   (máx. 24 h para PPR)
```

Puntos críticos:

- La clave se calcula **antes** de firmar y debe coincidir con los campos del
  XML.
- El XML que se envía debe estar firmado; el lote contiene comprobantes ya
  firmados.
- No se debe regenerar la clave por un simple rechazo de validación.
- Un `RECIBIDA` no es una autorización.
- Un `PPR` no es un error definitivo.

## 4. Ambientes y endpoints

**Confirmado** por los WSDL oficiales consultados el 20/10/2026:

| Servicio | Pruebas (`celcer`) | Producción (`cel`) |
| --- | --- | --- |
| Recepción | `https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl` | `https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl` |
| Autorización | `https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl` | `https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl` |
| Consulta | `https://celcer.sri.gob.ec/comprobantes-electronicos-ws/ConsultaComprobante?wsdl` | `https://cel.sri.gob.ec/comprobantes-electronicos-ws/ConsultaComprobante?wsdl` |
| Factura negociable | `https://celcer.sri.gob.ec/comprobantes-electronicos-ws/ConsultaFactura?wsdl` | `https://cel.sri.gob.ec/comprobantes-electronicos-ws/ConsultaFactura?wsdl` |

Notas:

- Los endpoints antiguos `RecepcionComprobantes` y
  `AutorizacionComprobantes` (sin `Offline`) **ya no responden**. No los use.
- El SRI puede rotar certificados TLS. No fije certificados, IPs ni cadenas en
  el código; mantenga `verify_peer` y `verify_peer_name` activos.
- Use `?wsdl` para obtener el contrato. No invente rutas alternativas.
- El WSDL declara `xml` como `xs:base64Binary`; `ext-soap` codifica el valor.
  Si usa otro transporte, respete el tipo del contrato.

## 5. Clave de acceso

### 5.1 Estructura (49 dígitos)

| Orden | Campo | Longitud | Formato |
| --- | --- | --- | --- |
| 1 | Fecha de emisión `ddmmaaaa` | 8 | Numérico |
| 2 | Tipo de comprobante | 2 | Tabla 3 |
| 3 | RUC del emisor | 13 | Numérico |
| 4 | Ambiente | 1 | `1` pruebas, `2` producción |
| 5 | Serie | 6 | Numérico |
| 6 | Secuencial | 9 | Numérico |
| 7 | Código numérico | 8 | Numérico |
| 8 | Tipo de emisión | 1 | `1` normal |
| 9 | Dígito verificador | 1 | Módulo 11 |

### 5.2 Tipos de comprobante (Tabla 3)

| Código | Comprobante |
| --- | --- |
| `01` | Factura |
| `03` | Liquidación de compra de bienes y prestación de servicios |
| `04` | Nota de crédito |
| `05` | Nota de débito |
| `06` | Guía de remisión |
| `07` | Comprobante de retención |

### 5.3 Algoritmo del dígito verificador

**Confirmado** (ficha v2.34, sección 5.2):

1. Tomar los primeros 48 dígitos.
2. Recorrer de derecha a izquierda con pesos `2,3,4,5,6,7,2,3,...`.
3. Sumar los productos.
4. Calcular `11 - (suma mod 11)`.
5. Si el resultado es `11`, usar `0`; si es `10`, usar `1`.

Ejemplo oficial:

```text
Cadena: 4 1 2 6 1 5 3 3
Pesos:  3 2 7 6 5 4 3 2
Suma:   12 + 2 + 14 + 36 + 5 + 20 + 9 + 6 = 104
104 mod 11 = 5
11 - 5 = 6  → dígito verificador = 6
```

El paquete expone `AccessKeyGenerator::module11()` para verificar este vector y
`AccessKeyGenerator::isValid()` para validar una clave completa.

**Validar:** la clave es única, pero el SRI también valida que sus componentes
coincidan con el XML (error 58) y que no esté registrada (error 43).

## 6. XML, versiones y XSD

### 6.1 Versiones

La ficha incluye formatos `1.0.0`, `1.1.0`, `2.0.0` y `2.1.0` para distintos
comprobantes y anexos (factura, exportación, reembolso, subsidios, rubros de
terceros, guía de remisión, retención ATS, etc.). **No existe una única versión
"vigente" para todos los tipos**: consulte el anexo y el XSD aplicables.

**Confirmado:** la validación de esquema es la primera barrera del SRI; si el
XML no pasa el XSD, devuelve `DEVUELTA` con código 35.

### 6.2 Estructura conceptual de una factura

La siguiente estructura es **conceptual**; los campos obligatorios y sus
longitudes dependen de la versión y de los anexos aplicables:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<factura id="comprobante" version="1.1.0">
  <infoTributaria>
    <ambiente>1</ambiente>
    <tipoEmision>1</tipoEmision>
    <razonSocial>EMISOR DE PRUEBA</razonSocial>
    <nombreComercial>EMISOR</nombreComercial>
    <ruc>1790012345001</ruc>
    <claveAcceso>...</claveAcceso>
    <codDoc>01</codDoc>
    <estab>001</estab>
    <ptoEmi>001</ptoEmi>
    <secuencial>000000123</secuencial>
    <dirMatriz>...</dirMatriz>
  </infoTributaria>
  <infoFactura>
    <fechaEmision>20/10/2026</fechaEmision>
    <obligadoContabilidad>NO</obligadoContabilidad>
    <tipoIdentificacionComprador>...</tipoIdentificacionComprador>
    <razonSocialComprador>...</razonSocialComprador>
    <identificacionComprador>...</identificacionComprador>
    <totalSinImpuestos>...</totalSinImpuestos>
    <totalDescuento>...</totalDescuento>
    <totalConImpuestos>...</totalConImpuestos>
    <propina>0.00</propina>
    <importeTotal>...</importeTotal>
    <moneda>DOLAR</moneda>
  </infoFactura>
  <detalles>
    <detalle>
      <!-- código, descripción, cantidad, precio, descuento, impuestos -->
    </detalle>
  </detalles>
  <infoAdicional>
    <campoAdicional nombre="...">...</campoAdicional>
  </infoAdicional>
  <!-- ds:Signature (XAdES-BES) -->
</factura>
```

No copie esta plantilla a producción sin completar todos los campos del XSD y
los requisitos del anexo que correspondan. El paquete **no** genera este XML.

### 6.3 Escape y codificación

- Codificación: **UTF-8**.
- El `&` debe ir como `&amp;`; de lo contrario el XML queda mal formado.
- No incluya bytes BOM.
- Use `CDATA` solo donde el esquema lo permita; no es una solución universal
  para caracteres inválidos.
- Declare los namespaces de firma correctamente; un namespace mal ubicado puede
  invalidar la firma aunque el XML "se vea bien".

### 6.4 Lote (Tabla 8)

**Confirmado:** el lote se envía por el mismo método `validarComprobante` y
tiene este formato:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<lote version="1.0.0">
  <claveAcceso>2808201400179210439400110010010000000091234567812</claveAcceso>
  <ruc>1792104394001</ruc>
  <comprobantes>
    <comprobante><![CDATA[COMPROBANTE 1]]></comprobante>
    <comprobante><![CDATA[COMPROBANTE 2]]></comprobante>
  </comprobantes>
</lote>
```

Reglas:

- Máximo **50 comprobantes** o **500 KB** por lote (aproximadamente; el límite
  se aplica al conjunto).
- Cada comprobante debe estar firmado.
- La clave del lote es una clave de acceso de 49 dígitos; el emisor debe asignar
  **serie y secuencial exclusivos para el envío en lote**.
- El comprobante individual tiene un límite de **320 KB**.
- La respuesta de autorización del lote es `RespuestaAutorizacionLote`; consulte
  con `autorizacionComprobanteLote`.

`Xml\LoteBuilder` arma este XML, valida la clave y el RUC, limita a 50
comprobantes/512000 bytes y escapa la secuencia `]]>` dentro de CDATA.

## 7. Firma electrónica

### 7.1 Requisitos confirmados (ficha v2.34, sección 6)

| Aspecto | Valor |
| --- | --- |
| Estándar | XAdES-BES |
| Versión de esquema | 1.3.2 (`http://uri.etsi.org/01903/v1.3.2#`) |
| Tipo | Enveloped (`http://www.w3.org/2000/09/xmldsig#enveloped-signature`) |
| Codificación | UTF-8 |
| Algoritmo | RSA-SHA1 |
| Longitud de clave | 2048 bits |
| Intercambio | PKCS#12 (`.p12`) |
| `KeyInfo` | Certificado firmante en base64, firmado para evitar sustitución |
| `SignedProperties` | Forma parte de la firma |

El SRI menciona que utilizó las librerías MITyCLib (XADES, TSA, API, OCSP,
Trust) para incorporar y validar la firma. Eso **no** obliga a usar esas
librerías, pero sí a producir una firma interoperable.

### 7.2 Implicaciones prácticas

- Firme el comprobante **después** de completar todos los datos y la clave.
- No reformatee, reindente ni vuelva a serializar el XML después de firmar:
  cambiaría los bytes y la firma dejaría de validar.
- Incruste la cadena de certificados necesaria y valide la vigencia/revocación
  (OCSP) antes de firmar.
- RSA-SHA1 es el algoritmo exigido por la ficha; es legacy desde el punto de
  vista criptográfico. **Validar** si el SRI publica una actualización.
- Un error de firma se reporta como 39 (firma inválida), 40 (certificado) o 42
  (certificado revocado/caducado).
- **Validar:** OpenSSL 3 puede rechazar PKCS#12 antiguos que usan algoritmos
  legacy. Si `openssl_pkcs12_read` falla con un certificado válido, pruebe el
  proveedor `legacy` de OpenSSL o convierta el contenedor en un entorno
  controlado; no debilite la validación TLS.

### 7.3 Decisión de este paquete

El paquete **no implementa XAdES-BES**. Ofrece:

- `Signature\Signer`: interfaz para un firmador validado.
- `Signature\CallbackSigner`: adaptador para HSM, servicio o librería externa.
- `Signature\P12Certificate`: carga y verificación básica de un PKCS#12.

Ver [ADR 0002](adr/0002-firma-xades-bes.md). No use un firmador casero sin
probarlo contra el ambiente de certificación del SRI.

## 8. Recepción

### 8.1 Operación

```text
validarComprobante(xml: base64Binary) → RespuestaRecepcionComprobante
```

Respuesta exitosa:

```xml
<RespuestaRecepcionComprobante>
  <estado>RECIBIDA</estado>
  <comprobantes/>
</RespuestaRecepcionComprobante>
```

Respuesta devuelta:

```xml
<RespuestaRecepcionComprobante>
  <estado>DEVUELTA</estado>
  <comprobantes>
    <comprobante>
      <claveAcceso>...</claveAcceso>
      <mensajes>
        <mensaje>
          <identificador>35</identificador>
          <mensaje>DOCUMENTO INVÁLIDO</mensaje>
          <informacionAdicional>...</informacionAdicional>
          <tipo>ERROR</tipo>
        </mensaje>
      </mensajes>
    </comprobante>
  </comprobantes>
</RespuestaRecepcionComprobante>
```

`RecepcionResult` normaliza el caso de un solo comprobante/mensaje y el de
listas, porque `ext-soap` representa de forma distinta un nodo único y uno
repetido.

### 8.2 Validaciones del SRI (orden aproximado)

**Confirmado** por la ficha (sección 11):

1. XML bien formado y válido contra el XSD; tamaño; esquema activo.
2. Contribuyente emisor: RUC activo, establecimiento activo, autorización para
   emitir y para el tipo de comprobante.
3. Unicidad: clave no registrada, secuencial único, clave bien formada.
4. Firma: validez y cadena de confianza, OCSP.
5. Verificaciones adicionales: fecha de emisión, identificación del receptor,
   documentos de sustento.
6. Diferencias de cálculo.

El SRI no devuelve todos los errores a la vez: se detiene en el primero que
detecta.

## 9. Autorización

### 9.1 Operaciones

```text
autorizacionComprobante(claveAccesoComprobante)
  → RespuestaAutorizacionComprobante

autorizacionComprobanteLote(claveAccesoLote)
  → RespuestaAutorizacionLote
```

Respuesta individual (resumida):

```xml
<RespuestaAutorizacionComprobante>
  <claveAccesoConsultada>...</claveAccesoConsultada>
  <numeroComprobantes>1</numeroComprobantes>
  <autorizaciones>
    <autorizacion>
      <estado>AUTORIZADO</estado>
      <numeroAutorizacion>...</numeroAutorizacion>
      <fechaAutorizacion>2026-10-20T10:00:00.000-05:00</fechaAutorizacion>
      <ambiente>PRUEBAS</ambiente>
      <comprobante><![CDATA[XML AUTORIZADO]]></comprobante>
      <mensajes>...</mensajes>
    </autorizacion>
  </autorizaciones>
</RespuestaAutorizacionComprobante>
```

Lote:

```xml
<RespuestaAutorizacionLote>
  <claveAccesoLoteConsultada>...</claveAccesoLoteConsultada>
  <numeroComprobantesLote>2</numeroComprobantesLote>
  <autorizaciones>
    <autorizacion>...</autorizacion>
    <autorizacion>...</autorizacion>
  </autorizaciones>
</RespuestaAutorizacionLote>
```

### 9.2 Estados

| Estado | Siglas | Significado | Acción |
| --- | --- | --- | --- |
| En procesamiento | `PPR` | El SRI aún procesa | Esperar; no reenviar ni regenerar clave |
| Autorizado | `AUT` | Autorización emitida | Guardar XML autorizado y notificar |
| No autorizado | `NAT` | Rechazo definitivo | Corregir, reenviar misma clave y notificar |

La ficha también muestra `AUTORIZADO`, `RECHAZADO` y `NO AUTORIZADO` en
respuestas históricas. El código debe comparar de forma robusta (por ejemplo,
`AUTORIZADO`/`AUT`) y no asumir un único literal.

### 9.3 Tiempos y reintentos

- **Hasta 24 horas** para procesar y devolver la autorización.
- Si recibe `PPR`, no reenvíe ni genere otra clave; espere y consulte.
- Error **70** (clave en procesamiento): tampoco reenviar; esperar hasta 24 h.
- Si el comprobante ya fue autorizado, el WS devuelve el último estado; no es
  necesario reenviarlo.
- Un `NAT` no se arregla reintentando el mismo XML: hay que corregir.

Política recomendada:

1. Enviar una vez.
2. Si `RECIBIDA`, consultar con backoff (por ejemplo, 2 s, 5 s, 10 s, 30 s,
   60 s) durante un máximo operativo razonable.
3. Si tras 24 h sigue `PPR`, escalar a revisión manual; no generar otra clave.
4. Persistir cada intento y la respuesta cruda para auditoría (sin secretos).

## 10. Consultas

### 10.1 Estado de autorización

```text
consultarEstadoAutorizacionComprobante(claveAcceso)
  → EstadoAutorizacionComprobante
```

Valores documentados de `estadoAutorizacion`:

- `AUTORIZADO`
- `NO AUTORIZADO`
- `PENDIENTE DE ANULAR`
- `ANULADO`

Además, el XML puede traer `estadoConsulta`:

- `RECHAZADA` si la clave está fuera del rango de fechas permitido o no existe
  en la base del SRI; en ese caso el mensaje suele ser el código **99**
  ("ERROR AL CONSULTAR DATOS DEL SERVICIO WEB").
- Si el comprobante no existe, `informacionAdicional` suele decir "No existen
  datos para los parámetros ingresados".

La ficha muestra una inconsistencia de redacción: en algunos párrafos dice
`estadoAutorizacion` y en el XML de error usa `estadoConsulta`. El DTO del
paquete conserva ambos campos.

### 10.2 Factura comercial negociable

```text
consultarEstadoConfirmacionFacturaComercialNegociable(claveAcceso)
  → EstadoConfirmacionFacturaComercialNegociable
```

- `estadoConfirmacion = "SI"`: aceptada como factura comercial negociable.
- `estadoConsulta = "RECHAZADA"` + código 99: no existe, está fuera de rango o
  no fue aceptada como negociable.

No interprete la ausencia de `estadoConfirmacion` como un "NO" definitivo sin
revisar `estadoConsulta` y los mensajes.

### 10.3 Límites

**Confirmado:** los servicios de consulta son individuales por clave. **No**
existe un método WSDL para consultar por rango de fechas, por RUC ni para
descargar masivamente. Ver [consulta-masiva.md](consulta-masiva.md).

## 11. Códigos de error y advertencia

Fuente: ficha v2.34, sección 11. Los códigos 59, 60, 62 y 68 son
**advertencias**; el resto de la tabla son errores (algunos se validan en el
emisor antes de enviar).

### 11.1 Errores

| Código | Descripción | Ámbito |
| --- | --- | --- |
| 2 | RUC del emisor no activo | Autorización |
| 10 | Establecimiento del emisor clausurado | Autorización |
| 26 | Tamaño máximo superado | Recepción |
| 27 | Clase de contribuyente no permitida | Autorización |
| 28 | Acuerdo de medios electrónicos no aceptado | Recepción |
| 34 | Comprobante no autorizado en la solicitud de emisión | Emisor |
| 35 | Documento inválido (no pasa el XSD) | Recepción |
| 36 | Versión de esquema descontinuada | Recepción |
| 37 | RUC sin autorización de emisión | Autorización |
| 39 | Firma inválida | Autorización |
| 40 | Error en el certificado | Autorización |
| 42 | Certificado revocado o caducado | Emisor |
| 43 | Clave de acceso ya registrada | Recepción |
| 45 | Secuencial ya registrado | Recepción |
| 46 | RUC no existe | Autorización |
| 47 | Tipo de comprobante no existe | Recepción |
| 48 | Esquema XSD no existe | Recepción |
| 49 | Argumentos nulos al WS | Recepción |
| 50 | Error interno del servidor | Recepción |
| 52 | Error en diferencias de cálculo | Autorización / emisor |
| 56 | Establecimiento cerrado | Autorización |
| 57 | Autorización suspendida | Autorización |
| 58 | Error en la estructura de la clave | Autorización |
| 63 | RUC clausurado | Autorización |
| 64 | Código de documento de sustento inexistente | Emisor |
| 65 | Fecha de emisión extemporánea | Emisor / recepción |
| 67 | Fecha inválida | Recepción |
| 69 | Identificación del receptor no existe en el RUC | Emisor |
| 70 | Clave de acceso en procesamiento | Recepción |
| 80 | Error en la estructura de la clave consultada | Autorización |
| 82 | Fecha de inicio de transporte menor a la de emisión | Recepción |
| 92 | Monto de devolución de IVA no coincide con el autorizado por DIG | Recepción |
| 99 | Error al consultar datos del servicio web | Consultas |

### 11.2 Advertencias

| Código | Descripción |
| --- | --- |
| 59 | Identificación del adquirente no existe |
| 60 | Comprobante emitido en ambiente de pruebas/certificación |
| 62 | Identificación del adquirente incorrecta (por ejemplo, cédula con DV inválido) |
| 68 | Documento de sustento no existe como electrónico |

Una advertencia no impide la autorización, pero puede indicar un dato a
corregir. No la trate como error fatal ni la ignore en los reportes.

### 11.3 Reglas de recuperación

- **35, 36, 47, 48, 49, 67, 82:** corregir el XML/estructura y reenviar con la
  misma clave (salvo que el SRI indique otra cosa).
- **39, 40, 42:** revisar firma, certificado, cadena y revocación.
- **43, 45:** no reenviar a ciegas; consultar autorización y revisar unicidad.
- **70:** esperar; no reenviar ni cambiar clave.
- **2, 10, 37, 46, 56, 57, 63:** son bloqueos del emisor; requieren gestión
  tributaria, no un simple reintento.
- **50:** error interno; se puede reintentar con backoff, pero registre la
  respuesta.
- **99:** no confundir con "no autorizado"; la consulta no pudo resolver el
  dato.

## 12. Reglas operativas y cumplimiento

- **Plazo de transmisión:** 4 días hábiles (Resolución
  `NAC-DGERCGC24-00000035`). **Validar** cómputo y vigencia.
- **Procesamiento:** hasta 24 horas.
- **Conservación:** mantenga el XML autorizado, el XML firmado enviado y el
  acuse/respuesta; son evidencia tributaria. Consulte los plazos de
  conservación aplicables.
- **Notificación al receptor:** si hay `NAT`, corrija, reenvíe y entregue el
  nuevo comprobante por correo electrónico.
- **RIDE:** el XML autorizado es la fuente; el RIDE es su representación
  impresa. No invente el RIDE a partir de datos no autorizados.
- **Contingencia:** el esquema off-line actual no usa claves de contingencia
  (fueron eliminadas en la versión 2.0). No implemente "contingencia" con
  códigos antiguos.

## 13. Anexo 26: RUC del proveedor de sistemas

**Confirmado (ficha v2.34, Anexo 26):** los contribuyentes que usen sistemas de
facturación electrónica de terceros (proveedores de la Resolución 2718) deben
incluir el RUC del proveedor en `infoAdicional`:

```xml
<infoAdicional>
  <campoAdicional nombre="RUC Proveedor">1790012345001</campoAdicional>
</infoAdicional>
```

- El valor del atributo `nombre` debe ser exactamente `RUC Proveedor`.
- El contenido es el RUC del proveedor (alfanumérico, máximo 300 caracteres).
- Es obligatorio a los **60 días calendario** desde la publicación del acto
  normativo en el Registro Oficial (Resolución
  `NAC-DGERCGC26-00000027`).

**Validar:** la fecha exacta de publicación y la vigencia. La ficha no la
incluye en el texto; no asuma que los 60 días ya se cumplieron.

## 14. Consulta y descarga masiva

**Confirmado:** no existe una API oficial de consulta o descarga masiva. Los
servicios WSDL reciben **una clave** (o una clave de lote) por llamada y no
ofrecen listados por fecha/RUC.

Lo que existe:

1. Consulta individual por clave en los WS descritos.
2. Portal del SRI para consultas y descargas individuales de XML/RIDE.
3. Datos abiertos del SRI, generalmente agregados, no comprobantes
   individuales.

**Inferencia:** para volumen alto, las alternativas realistas son procesar los
comprobantes que el propio emisor generó, integrarse con el sistema contable o
usar un proveedor autorizado. El scraping del portal es frágil, puede violar
términos de uso y no es una API. Si se evalúa RPA, **validar** condiciones
legales, captchas, límites y estabilidad.

Ver el documento dedicado: [consulta-masiva.md](consulta-masiva.md).

## 15. Seguridad

- Use TLS con verificación estricta; no desactive la validación para "que
  funcione".
- Guarde el `.p12` fuera del repositorio, con permisos mínimos y contraseña en
  un gestor de secretos.
- No loguee XML completos, claves privadas, contraseñas ni datos personales.
- Valide el certificado (vigencia, cadena, revocación) antes de firmar.
- Restrinja el acceso a los XML autorizados; contienen datos fiscales.
- Firme con un usuario/servicio de mínimo privilegio.
- Monitoree la rotación de certificados TLS y de firma.
- No confíe en la respuesta del SRI sin verificar el estado y la firma del XML
  autorizado cuando su proceso lo requiera.

## 16. Checklist de implementación

- [ ] Identificar tipo, versión de esquema y anexos aplicables.
- [ ] Descargar/versionar los XSD oficiales.
- [ ] Implementar la clave de acceso y validar el módulo 11.
- [ ] Generar el XML completo y validarlo contra el XSD.
- [ ] Calcular todos los impuestos y totales; validar diferencias.
- [ ] Firmar con XAdES-BES validado en el ambiente de pruebas.
- [ ] Implementar recepción individual y lote (≤ 50 / ≤ 500 KB).
- [ ] Persistir clave, XML firmado, estado y respuestas.
- [ ] Implementar polling de autorización con backoff y límite de 24 h.
- [ ] Manejar `DEVUELTA`, `NAT`, `PPR`, error 70 y advertencias.
- [ ] Reenviar con la misma clave cuando corresponda.
- [ ] Notificar al receptor y conservar evidencia.
- [ ] Implementar el RUC del proveedor (Anexo 26) si aplica.
- [ ] Revisar el plazo de 4 días hábiles y alertas.
- [ ] Probar en certificación con cada tipo de comprobante.
- [ ] Definir monitoreo, alertas y plan de rotación de certificados.

## 17. Fuentes y verificación

| Fuente | Uso | Fecha |
| --- | --- | --- |
| Ficha Técnica de Comprobantes Electrónicos Offline v2.34 (julio 2026) | Reglas, tablas, firma, errores, anexos | 20/10/2026 |
| WSDL `RecepcionComprobantesOffline` (pruebas y producción) | Operación, tipos, endpoint | 20/10/2026 |
| WSDL `AutorizacionComprobantesOffline` (pruebas y producción) | Operación individual y lote | 20/10/2026 |
| WSDL `ConsultaComprobante` | Consulta de estado | 20/10/2026 |
| WSDL `ConsultaFactura` | Factura negociable | 20/10/2026 |
| Resolución `NAC-DGERCGC24-00000035` | Plazo de 4 días hábiles | **Validar vigencia** |
| Resolución `NAC-DGERCGC26-00000027` | Anexo 26, RUC del proveedor | **Validar publicación/vigencia** |

Enlaces de referencia:

- Portal del SRI: <https://www.sri.gob.ec/>
- Ficha y formatos: consulte la sección de comprobantes electrónicos del portal
  del SRI. Los enlaces directos cambian; verifique siempre la versión publicada.
- ETSI XAdES: <http://uri.etsi.org/01903/v1.3.2/ts_101903v010302p.pdf>
- XML Signature: <https://www.w3.org/TR/xmldsig-core/>

> Esta guía es un resumen técnico. La ficha oficial, los XSD y las resoluciones
> del SRI prevalecen sobre cualquier afirmación de este repositorio.
