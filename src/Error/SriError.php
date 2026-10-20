<?php

declare(strict_types=1);

namespace JulioSerpone\SriComprobantes\Error;

/**
 * Códigos de error y advertencia documentados en la Ficha Técnica v2.34,
 * sección 11. No se inventan códigos: solo se modelan los publicados.
 */
enum SriError: int
{
    case RUC_INACTIVO = 2;
    case ESTABLECIMIENTO_CLAUSURADO = 10;
    case TAMANO_EXCEDIDO = 26;
    case CLASE_NO_PERMITIDA = 27;
    case ACUERDO_MEDIOS_NO_ACEPTADO = 28;
    case COMPROBANTE_NO_AUTORIZADO = 34;
    case DOCUMENTO_INVALIDO = 35;
    case ESQUEMA_DESCONTINUADO = 36;
    case RUC_SIN_AUTORIZACION = 37;
    case FIRMA_INVALIDA = 39;
    case ERROR_CERTIFICADO = 40;
    case CERTIFICADO_REVOCADO = 42;
    case CLAVE_REGISTRADA = 43;
    case SECUENCIAL_REGISTRADO = 45;
    case RUC_NO_EXISTE = 46;
    case TIPO_COMPROBANTE_NO_EXISTE = 47;
    case ESQUEMA_NO_EXISTE = 48;
    case ARGUMENTOS_NULOS = 49;
    case ERROR_INTERNO = 50;
    case ERROR_DIFERENCIAS = 52;
    case ESTABLECIMIENTO_CERRADO = 56;
    case AUTORIZACION_SUSPENDIDA = 57;
    case ERROR_ESTRUCTURA_CLAVE = 58;
    case RUC_CLAUSURADO = 63;
    case CODIGO_DOCUMENTO_SUSTENTO = 64;
    case FECHA_EXTEMPORANEA = 65;
    case FECHA_INVALIDA = 67;
    case IDENTIFICACION_RECEPTOR_NO_EXISTE = 69;
    case CLAVE_EN_PROCESAMIENTO = 70;
    case ERROR_ESTRUCTURA_CLAVE_CONSULTA = 80;
    case ERROR_FECHA_TRANSPORTE = 82;
    case ERROR_DEVOLUCION_IVA = 92;
    case ERROR_CONSULTA_DATOS = 99;

    // Advertencias
    case IDENTIFICACION_NO_EXISTE = 59;
    case AMBIENTE_PRUEBAS = 60;
    case IDENTIFICACION_INCORRECTA = 62;
    case DOCUMENTO_SUSTENTO = 68;

    public function description(): string
    {
        return match ($this) {
            self::RUC_INACTIVO => 'RUC del emisor no activo',
            self::ESTABLECIMIENTO_CLAUSURADO => 'Establecimiento del emisor clausurado',
            self::TAMANO_EXCEDIDO => 'Tamaño máximo superado',
            self::CLASE_NO_PERMITIDA => 'Clase de contribuyente no permitida',
            self::ACUERDO_MEDIOS_NO_ACEPTADO => 'Acuerdo de medios electrónicos no aceptado',
            self::COMPROBANTE_NO_AUTORIZADO => 'Comprobante no autorizado como parte de la solicitud de emisión',
            self::DOCUMENTO_INVALIDO => 'Documento inválido (no supera el XSD)',
            self::ESQUEMA_DESCONTINUADO => 'Versión de esquema descontinuada',
            self::RUC_SIN_AUTORIZACION => 'RUC sin autorización de emisión',
            self::FIRMA_INVALIDA => 'Firma electrónica inválida',
            self::ERROR_CERTIFICADO => 'Error en el certificado',
            self::CERTIFICADO_REVOCADO => 'Certificado revocado o caducado',
            self::CLAVE_REGISTRADA => 'Clave de acceso ya registrada',
            self::SECUENCIAL_REGISTRADO => 'Secuencial ya registrado',
            self::RUC_NO_EXISTE => 'RUC no existe',
            self::TIPO_COMPROBANTE_NO_EXISTE => 'Tipo de comprobante no existe',
            self::ESQUEMA_NO_EXISTE => 'Esquema XSD no existe',
            self::ARGUMENTOS_NULOS => 'Argumentos nulos enviados al WS',
            self::ERROR_INTERNO => 'Error interno del servidor',
            self::ERROR_DIFERENCIAS => 'Error en los cálculos del comprobante',
            self::ESTABLECIMIENTO_CERRADO => 'Establecimiento cerrado',
            self::AUTORIZACION_SUSPENDIDA => 'Autorización suspendida',
            self::ERROR_ESTRUCTURA_CLAVE => 'Error en la estructura de la clave de acceso',
            self::RUC_CLAUSURADO => 'RUC clausurado',
            self::CODIGO_DOCUMENTO_SUSTENTO => 'Código de documento de sustento inexistente',
            self::FECHA_EXTEMPORANEA => 'Fecha de emisión extemporánea',
            self::FECHA_INVALIDA => 'Fecha inválida',
            self::IDENTIFICACION_RECEPTOR_NO_EXISTE => 'Identificación del receptor no existe en el RUC',
            self::CLAVE_EN_PROCESAMIENTO => 'Clave de acceso en procesamiento',
            self::ERROR_ESTRUCTURA_CLAVE_CONSULTA => 'Error en la estructura de la clave consultada',
            self::ERROR_FECHA_TRANSPORTE => 'Error en la fecha de inicio de transporte',
            self::ERROR_DEVOLUCION_IVA => 'Error al validar el monto de devolución de IVA',
            self::ERROR_CONSULTA_DATOS => 'Error al consultar datos del servicio web',
            self::IDENTIFICACION_NO_EXISTE => 'Identificación del adquirente no existe',
            self::AMBIENTE_PRUEBAS => 'Comprobante emitido en ambiente de pruebas',
            self::IDENTIFICACION_INCORRECTA => 'Identificación del adquirente incorrecta',
            self::DOCUMENTO_SUSTENTO => 'Documento de sustento no existe como electrónico',
        };
    }

    public function isWarning(): bool
    {
        return in_array($this, [
            self::IDENTIFICACION_NO_EXISTE,
            self::AMBIENTE_PRUEBAS,
            self::IDENTIFICACION_INCORRECTA,
            self::DOCUMENTO_SUSTENTO,
        ], true);
    }

    /**
     * Un error transitorio puede reintentarse tal cual. Los rechazos de
     * validación no lo son: exigen corregir el XML.
     */
    public function isTransient(): bool
    {
        return $this === self::ERROR_INTERNO;
    }

    public function isInProcess(): bool
    {
        return $this === self::CLAVE_EN_PROCESAMIENTO;
    }

    public static function fromCode(int|string|null $code): ?self
    {
        if ($code === null || $code === '') {
            return null;
        }

        return self::tryFrom((int) $code);
    }
}
