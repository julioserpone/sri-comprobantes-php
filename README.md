# SRI Comprobantes PHP

Paquete PHP sin framework para integrar la emisión y consulta de **comprobantes
electrónicos del Servicio de Rentas Internas (SRI) de Ecuador**, esquema
**off-line**.

> **Estado: alfa (0.1.x).** El paquete implementa la clave de acceso, el
> transporte SOAP, los clientes de recepción/autorización/consulta, los DTO de
> respuesta, el catálogo de errores, reintentos, validación XSD, armado de lotes
> y almacenamiento local. **No incluye un firmador XAdES-BES** ni un generador
> de XML por tipo de comprobante; esas piezas se integran mediante interfaces
> explícitas. Lea [Firma](#firma) y [Alcance](#alcance).

Este proyecto **no está afiliado ni respaldado por el SRI**. La fuente de verdad
es la ficha técnica oficial y los XSD publicados por el SRI. La versión de
referencia de esta documentación es la **Ficha Técnica de Comprobantes
Electrónicos Offline v2.34 (julio 2026)**.

## Contenido

- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Inicio rápido](#inicio-rápido)
- [Qué incluye y qué no](#qué-incluye-y-qué-no)
- [Endpoints](#endpoints)
- [Clave de acceso](#clave-de-acceso)
- [Firma](#firma)
- [Reintentos y estados](#reintentos-y-estados)
- [Seguridad](#seguridad)
- [Pruebas y calidad](#pruebas-y-calidad)
- [Documentación](#documentación)
- [Contribuir](#contribuir)

## Requisitos

- PHP **8.2** o superior.
- Extensiones `soap`, `openssl`, `dom` y `libxml`.
- Acceso HTTPS a `celcer.sri.gob.ec` (pruebas) o `cel.sri.gob.ec` (producción).
- Certificado de firma electrónica válido, emitido por una entidad de
  certificación autorizada en Ecuador (PKCS#12 para el flujo soportado).
- Un firmador XAdES-BES validado (HSM, servicio de firma o librería
  especializada). Ver [Firma](#firma).

## Instalación

El paquete **todavía no está publicado en Packagist**. Mientras tanto, instálelo
desde el repositorio VCS. Si en el futuro el repositorio vuelve a ser privado,
configure las credenciales de GitHub para Composer.

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/julioserpone/sri-comprobantes-php" }
  ],
  "require": {
    "julioserpone/sri-comprobantes-php": "^0.1"
  }
}
```

```bash
composer update julioserpone/sri-comprobantes-php
```

Cuando se publique en Packagist, bastará con:

```bash
composer require julioserpone/sri-comprobantes-php
```

## Inicio rápido

### 1. Consultar una autorización

```php
<?php

declare(strict_types=1);

use JulioSerpone\SriComprobantes\Client\AutorizacionClient;
use JulioSerpone\SriComprobantes\Config\SriEnvironment;
use JulioSerpone\SriComprobantes\Config\SriService;
use JulioSerpone\SriComprobantes\Transport\SoapTransport;

$environment = SriEnvironment::fromCode('1'); // 1 = pruebas, 2 = producción

$transport = new SoapTransport(
    wsdl: $environment->wsdl(SriService::AUTORIZACION),
    connectionTimeout: 10.0,
    timeout: 30.0,
);

$result = (new AutorizacionClient($transport))->consultar($claveAcceso);

if ($result->isAutorizado()) {
    $xmlAutorizado = $result->xmlAutorizado();
    $numeroAutorizacion = $result->first()?->numeroAutorizacion;
}
```

### 2. Generar una clave de acceso

```php
use JulioSerpone\SriComprobantes\AccessKey\AccessKeyGenerator;

$clave = (new AccessKeyGenerator())->generate(
    fechaEmision: new DateTimeImmutable('2026-10-20'),
    tipoComprobante: '01',      // factura
    ruc: '1790012345001',
    ambiente: '1',              // pruebas
    serie: '001001',
    secuencial: '000000123',
    codigoNumerico: '12345678',
    tipoEmision: '1',
);

// 49 dígitos; el último es el verificador módulo 11.
```

### 3. Validar el XML y armar un lote

```php
use JulioSerpone\SriComprobantes\Xml\LoteBuilder;
use JulioSerpone\SriComprobantes\Xml\XsdValidator;

// El XSD oficial debe estar versionado o montado localmente.
(new XsdValidator())->validate($xmlFirmado, __DIR__ . '/xsd/factura_V1.1.0.xsd');

$lote = (new LoteBuilder())->build(
    claveAccesoLote: $claveDelLote,
    ruc: '1790012345001',
    comprobantes: [$xmlFirmado1, $xmlFirmado2], // máximo 50 / 500 KB
);
```

### 4. Enviar y esperar autorización

```php
use JulioSerpone\SriComprobantes\Client\AutorizacionClient;
use JulioSerpone\SriComprobantes\Client\RecepcionClient;
use JulioSerpone\SriComprobantes\Retry\RetryPolicy;
use JulioSerpone\SriComprobantes\Service\EmisionService;

$service = new EmisionService(
    new RecepcionClient(new SoapTransport($environment->wsdl(SriService::RECEPCION))),
    new AutorizacionClient(new SoapTransport($environment->wsdl(SriService::AUTORIZACION))),
    new RetryPolicy(maxAttempts: 3, baseDelayMs: 250, maxDelayMs: 5000),
);

$autorizacion = $service->emitir(
    xml: $xmlFirmado,
    claveAcceso: $clave,
    intentos: 10,
    segundosEntreIntentos: 3,
);
```

`emitir()` lanza `SriException` si la recepción es `DEVUELTA` o la autorización
termina en un estado no autorizado. Si prefiere controlar el flujo, use
`enviar()` y `esperarAutorizacion()` por separado.

### 5. Consultar el estado desde el portal/WS de consultas

```php
use JulioSerpone\SriComprobantes\Client\ConsultaClient;

$consulta = (new ConsultaClient(
    new SoapTransport($environment->wsdl(SriService::CONSULTA))
))->estado($clave);

$consulta->estadoAutorizacion; // AUTORIZADO, NO AUTORIZADO, ...
```

## Qué incluye y qué no

| Área | Incluido |
| --- | --- |
| Clave de acceso de 49 dígitos (módulo 11) | Sí, con validación |
| Ambientes y WSDL oficiales | Sí, pruebas y producción |
| Transporte SOAP con TLS y timeout | Sí (`ext-soap`) |
| Recepción individual y por lote | Sí (cliente) |
| Autorización individual y por lote | Sí (clientes y DTO) |
| Consulta de estado y factura negociable | Sí |
| Catálogo de errores/advertencias v2.34 | Sí |
| Reintentos con backoff | Sí, solo fallos de transporte |
| Validación contra XSD local | Sí |
| Armado del XML de lote (Tabla 8) | Sí |
| Almacenamiento local de XML/estado | Sí, implementación mínima |
| Generación del XML de factura/notas/retenciones | **No** |
| Firma XAdES-BES | **No** (interfaz + carga de PKCS#12) |
| Descarga masiva desde el portal | **No** (no existe API oficial) |

### Alcance

Este paquete es una **capa de integración**, no un facturador completo. La
generación de cada comprobante depende del tipo, versión de esquema y anexos
vigentes; hacerlo bien exige modelar cada XSD y sus reglas de negocio. El
paquete ofrece las piezas estables alrededor de ese XML: clave, validación,
transporte, estados, errores, reintentos y persistencia.

## Endpoints

Verificados en vivo el **20/10/2026** contra los WSDL del SRI:

| Servicio | Pruebas | Producción |
| --- | --- | --- |
| Recepción | `https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl` | `https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl` |
| Autorización | `https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl` | `https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl` |
| Consulta | `https://celcer.sri.gob.ec/comprobantes-electronicos-ws/ConsultaComprobante?wsdl` | `https://cel.sri.gob.ec/comprobantes-electronicos-ws/ConsultaComprobante?wsdl` |
| Factura negociable | `https://celcer.sri.gob.ec/comprobantes-electronicos-ws/ConsultaFactura?wsdl` | `https://cel.sri.gob.ec/comprobantes-electronicos-ws/ConsultaFactura?wsdl` |

No use los endpoints antiguos `RecepcionComprobantes` ni
`AutorizacionComprobantes`: ya no responden. No fije certificados ni IPs en el
código; el SRI puede rotarlos. Use TLS con verificación del certificado.

Operaciones WSDL confirmadas:

- `validarComprobante(xml)` → `RespuestaRecepcionComprobante`
- `autorizacionComprobante(claveAccesoComprobante)` → `RespuestaAutorizacionComprobante`
- `autorizacionComprobanteLote(claveAccesoLote)` → `RespuestaAutorizacionLote`
- `consultarEstadoAutorizacionComprobante(claveAcceso)` → `EstadoAutorizacionComprobante`
- `consultarEstadoConfirmacionFacturaComercialNegociable(claveAcceso)` →
  `EstadoConfirmacionFacturaComercialNegociable`

## Clave de acceso

Estructura de 49 dígitos (ficha v2.34, sección 5.2):

```text
ddmmaaaa  tipo(2)  RUC(13)  ambiente(1)  serie(6)  secuencial(9)
códigoNumérico(8)  tipoEmisión(1)  dígitoVerificador(1)
```

- Ambiente: `1` pruebas, `2` producción.
- Tipo de emisión del esquema off-line: `1` (normal).
- Dígito verificador: módulo 11 con pesos 2..7 de derecha a izquierda;
  resultado 11 → `0`, resultado 10 → `1`.
- Ejemplo oficial del algoritmo: `41261533` → `6`.

El paquete valida longitud, caracteres numéricos y dígito verificador. **No**
valida que el RUC exista, que el establecimiento esté activo ni que la clave no
haya sido registrada; eso lo determina el SRI.

## Firma

El SRI exige **XAdES-BES enveloped**, esquema 1.3.2, UTF-8, RSA-SHA1 y clave de
2048 bits (ficha v2.34, sección 6). El paquete **no implementa** la firma:
hacerlo sin validación contra el SRI es propenso a errores de canonicalización,
namespaces y `SignedProperties`.

Use `Signature\Signer` para integrar un firmador validado:

```php
use JulioSerpone\SriComprobantes\Signature\CallbackSigner;

$signer = new CallbackSigner(
    fn (string $xml): string => $miFirmadorXadesBes->firmar($xml)
);

$xmlFirmado = $signer->sign($xml);
```

`P12Certificate` carga un PKCS#12 y expone certificado/clave en PEM para un
firmador externo. Trate la clave privada como secreto: no la loguee, no la
persista y no la envíe por red.

## Reintentos y estados

- `RECIBIDA`: el SRI aceptó el archivo; todavía no implica autorización.
- `DEVUELTA`: el archivo no pasó validación. Corrija el XML y reenvíe con **la
  misma clave de acceso y secuencial** (ficha v2.34, sección 5.10).
- `PPR`: en procesamiento. El SRI puede tardar hasta 24 horas. **No** reenvíe ni
  genere otra clave mientras esté en `PPR` (error 70).
- `AUT`: autorizado.
- `NAT`: no autorizado; corrija, reenvíe y notifique al receptor.

`RetryPolicy` solo reintenta `TransportException` por defecto. Los errores de
validación del SRI no se reintentan: el XML debe corregirse. Ajuste el número de
intentos y el intervalo según su operación; no bloquee un worker durante horas.

## Seguridad

- Nunca versione `.env`, certificados `.p12/.pfx`, claves PEM ni XML con datos
  personales.
- Use TLS con verificación de certificado; no desactive `verify_peer`.
- Restrinja permisos del directorio de almacenamiento (0700) y de los archivos
  (0600). La implementación de filesystem es local y no apta para múltiples
  servidores.
- No registre XML completos, claves privadas ni contraseñas. Los mensajes del
  SRI pueden contener información fiscal.
- Rote credenciales y certificados; valide la cadena de confianza y la vigencia
  antes de firmar.
- Consulte [SECURITY.md](SECURITY.md) para reportar vulnerabilidades.

## Pruebas y calidad

```bash
composer install
composer check     # php-cs-fixer + phpstan nivel 8 + phpunit
composer test
composer analyse
```

Las pruebas no llaman al SRI: usan un transporte falso. La integración real debe
probarse en el ambiente de certificación con un certificado de pruebas y los XSD
oficiales. Para una comprobación manual del servicio de consulta:

```bash
php scripts/smoke-consulta.php <clave-de-49-digitos> 1
```

No use claves de producción en terminales compartidas ni ejecute este script en
CI.

## Documentación

- [Guía técnica SRI](docs/guia-tecnica-sri.md): flujo, endpoints, firma,
  estados, errores y consultas.
- [Arquitectura](docs/arquitectura.md): capas, decisiones y puntos de extensión.
- [Consulta masiva](docs/consulta-masiva.md): qué existe, qué no y alternativas.
- [Fuentes oficiales](docs/fuentes-oficiales.md): enlaces y fecha de verificación.
- [Reglas de GitHub](docs/GITHUB_RULES.md): protección de rama, checks y
  releases.
- [ADRs](docs/adr/): decisiones de arquitectura.
- [AGENTS.md](AGENTS.md): instrucciones para agentes de IA.

## Contribuir

Lea [CONTRIBUTING.md](CONTRIBUTING.md) y el
[Código de Conducta](CODE_OF_CONDUCT.md). Los cambios de documentación técnica
deben citar la sección/página de la ficha o el WSDL verificado y la fecha.

## Licencia

MIT. Ver [LICENSE](LICENSE).

---

**Descargo de responsabilidad:** este software se distribuye "tal cual", sin
garantía. La normativa tributaria, los esquemas XSD, los endpoints y los
certificados pueden cambiar. El usuario es responsable de validar su
implementación ante el SRI y de cumplir sus obligaciones tributarias.
