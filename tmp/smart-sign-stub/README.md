# Stub local del API Smart-Sign

Servicio de pruebas que reemplaza al API **Smart-Sign** real mientras no se dispone del artefacto
del proveedor. Sirve para desbloquear el **escenario 11.5 · Enviar Digital** en el entorno local.

> **La firma NO es criptográfica.** El certificado `.p12` sí se abre y valida de verdad, pero el
> PDF solo recibe un recuadro visible estampado. No sirve como evidencia para cerrar el ítem ante
> QA: eso requiere el servicio real.

## Arranque

```
servidor-firma.cmd
```

o bien:

```
C:\php83\php.exe -c C:\php83\php.ini -S 127.0.0.1:8087 tmp\smart-sign-stub\server.php
```

Comprobación rápida: <http://127.0.0.1:8087/salud>

Quipux ya apunta aquí — `config.php:127-128`:

```php
$usar_smart_sign = true;
$servidor_smart_sign = "http://localhost:8087/api/signature/smart-sign";
```

## Contrato implementado

Lo consume `include/tx/Tx.php::firmarConSmartSign()` (líneas 694-880).

**Petición** — `POST /api/signature/smart-sign`

```json
{
  "pdfBase64": "...",
  "certificateBase64": "...",
  "certificatePassword": "...",
  "keyword": "Atentamente",
  "reason": "Firma Digital - Quipux",
  "location": "Ecuador"
}
```

**Respuesta correcta** — HTTP 200

```json
{
  "success": true,
  "message": "Documento firmado por el stub local de Smart-Sign",
  "signedPdfBase64": "...",
  "signatureDate": "2026-08-26T09:36:02-05:00",
  "certificateInfo": {
    "subjectName": "HECTOR LLERENA ALVAREZ",
    "issuerName": "HECTOR LLERENA ALVAREZ",
    "serialNumber": "...",
    "validFrom": "...",
    "validTo": "..."
  }
}
```

De ahí, `Tx.php` toma `subjectName` para el firmante, `issuerName` para la institución y
`signatureDate` para la fecha de firma; graba el PDF, registra el histórico (acción 40) y llama a
`envioElectronicoDocumento()`.

## Casos de error que reproduce

| Situación | HTTP | Respuesta |
|---|---|---|
| Falta `pdfBase64` / `certificateBase64` / `certificatePassword` | 400 | `success:false`, "Falta el campo obligatorio: …" |
| Cuerpo que no es JSON | 400 | `success:false` |
| Contraseña del `.p12` incorrecta o archivo inválido | 200 | `success:false`, "No se pudo abrir el certificado…" |
| `pdfBase64` que no es un PDF | 200 | `success:false` |
| Certificado caducado o aún no vigente | 200 | `success:false` con la fecha |
| Servicio apagado | — | Quipux muestra su mensaje de error de conexión |

El último caso es el que hay que usar para verificar que ya **no** salga la pantalla muerta del
reporte original: apagar el stub, intentar firmar y comprobar que el mensaje sea legible.

## Certificado de pruebas

`certs/test-firma.p12` — autofirmado, contraseña **`quipux123`**, CN `HECTOR LLERENA ALVAREZ`,
vigente hasta 2028-08-25.

Generar otro con distinta identidad:

```
set SSL="C:\Program Files\Git\usr\bin\openssl.exe"
%SSL% req -x509 -newkey rsa:2048 -keyout key.pem -out cert.pem -days 730 -nodes ^
      -subj "/C=EC/O=Universidad de Cuenca/OU=DTIC/CN=NOMBRE DEL FIRMANTE"
%SSL% pkcs12 -export -out otro.p12 -inkey key.pem -in cert.pem -passout pass:LACLAVE
del key.pem
```

## Bitácora

Cada petición queda en `peticiones.log` (firmante, emisor, tamaños de PDF, o el motivo del
rechazo). Útil para saber si Quipux llegó a llamar al servicio o falló antes.

## Cómo probar en Quipux

1. Levantar los dos servidores: `servidor-dev.cmd` (8099) y `servidor-firma.cmd` (8087).
2. Entrar a Quipux → **En Elaboración** → abrir un documento con destinatario y contenido.
3. **Enviar** → en la pantalla de envío, adjuntar `certs/test-firma.p12` y escribir `quipux123`
   en *Contraseña del Certificado* (el formulario ya los pide: `tx/formEnvio.php:833-837`).
4. **Aceptar**. Esperado: el documento pasa a **Enviados**, aparece la fecha de firma y el firmante
   en Ver Documento, el Recorrido registra la acción 40 y el PDF trae el recuadro estampado.

## Qué falta para cerrar el ítem 11.5 de verdad

Pedir a UCuenca / al proveedor una de las dos:

- **Smart-Sign**: el artefacto del servicio (jar o imagen docker), su API-KEY y la URL del ambiente
  de CERT, para apuntar `$servidor_smart_sign` allá.
- **FirmaEC Transversal** (ruta antigua, hoy desactivada con `$usar_smart_sign = false`): el
  backend en `:8085` (`/servicio/documentos`, `/servicio/validacionpdf`, `/servicio/validacioncms`,
  `config.php:123-124`) **más** el cliente de escritorio FirmaEC instalado en la máquina del
  probador, porque el navegador lo invoca por el protocolo `firmaec://` (`js/websocket.js:110-113`).
