<?php

define('STUB_DIR', __DIR__);
define('RAIZ', dirname(__DIR__, 2));
define('LOG_FILE', STUB_DIR . '/peticiones.log');

function bitacora($mensaje)
{
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . "  $mensaje\n", FILE_APPEND);
}

function responder($httpCode, array $cuerpo)
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function nombreDistinguido($partes)
{
    if (!is_array($partes)) {
        return (string)$partes;
    }
    $texto = [];
    foreach ($partes as $clave => $valor) {
        if (is_array($valor)) {
            $valor = implode(' / ', $valor);
        }
        $texto[] = "$clave=$valor";
    }
    return implode(', ', $texto);
}

function estamparFirma($pdfBytes, array $datos)
{
    $entrada = STUB_DIR . '/tmp_entrada.pdf';
    file_put_contents($entrada, $pdfBytes);

    ob_start();
    require_once RAIZ . '/html_a_pdf/tcpdf/tcpdf.php';
    require_once RAIZ . '/html_a_pdf/fpdi/fpdi.php';

    $pdf = new FPDI();
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $paginas = $pdf->setSourceFile($entrada);

    for ($i = 1; $i <= $paginas; $i++) {
        $plantilla = $pdf->ImportPage($i);
        $medidas   = $pdf->getTemplatesize($plantilla);
        $pdf->AddPage('P', [$medidas['w'], $medidas['h']]);
        $pdf->useTemplate($plantilla);

        if ($i === $paginas) {
            $pdf->SetXY(12, $medidas['h'] - 34);
            $pdf->SetDrawColor(0, 90, 160);
            $pdf->SetTextColor(0, 60, 120);
            $pdf->SetFont('helvetica', '', 6.5);
            $pdf->MultiCell(
                86,
                22,
                "FIRMADO ELECTRONICAMENTE POR\n"
                . $datos['firmante'] . "\n"
                . 'Emisor: ' . $datos['emisor'] . "\n"
                . 'Fecha: ' . $datos['fecha'] . "\n"
                . 'Razon: ' . $datos['razon'] . '  -  Lugar: ' . $datos['lugar'] . "\n"
                . '(STUB LOCAL DE PRUEBAS - SIN VALIDEZ LEGAL)',
                1,
                'L',
                false,
                1
            );
        }
    }

    $salida = $pdf->Output('', 'S');
    ob_end_clean();
    @unlink($entrada);

    return $salida;
}

$ruta   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodo === 'GET' && ($ruta === '/' || $ruta === '/salud')) {
    responder(200, [
        'servicio' => 'Smart-Sign STUB local',
        'estado'   => 'arriba',
        'endpoint' => 'POST /api/signature/smart-sign',
        'aviso'    => 'Firma simulada: se estampa un recuadro visible, no hay firma criptografica.',
    ]);
}

if ($ruta !== '/api/signature/smart-sign') {
    responder(404, ['success' => false, 'message' => "Ruta no encontrada: $ruta"]);
}

if ($metodo !== 'POST') {
    responder(405, ['success' => false, 'message' => 'Solo se admite POST']);
}

$peticion = json_decode(file_get_contents('php://input'), true);

if (!is_array($peticion)) {
    bitacora('RECHAZADA: cuerpo no es JSON valido');
    responder(400, ['success' => false, 'message' => 'El cuerpo de la peticion no es JSON valido']);
}

foreach (['pdfBase64', 'certificateBase64', 'certificatePassword'] as $campo) {
    if (!isset($peticion[$campo]) || $peticion[$campo] === '') {
        bitacora("RECHAZADA: falta el campo $campo");
        responder(400, ['success' => false, 'message' => "Falta el campo obligatorio: $campo"]);
    }
}

$pdfBytes = base64_decode($peticion['pdfBase64'], true);
if ($pdfBytes === false || strncmp($pdfBytes, '%PDF', 4) !== 0) {
    bitacora('RECHAZADA: pdfBase64 no contiene un PDF');
    responder(200, ['success' => false, 'message' => 'El contenido enviado en pdfBase64 no es un PDF valido']);
}

$p12 = base64_decode($peticion['certificateBase64'], true);
if ($p12 === false || $p12 === '') {
    bitacora('RECHAZADA: certificateBase64 ilegible');
    responder(200, ['success' => false, 'message' => 'El certificado enviado no se pudo decodificar']);
}

$almacen = [];
if (!openssl_pkcs12_read($p12, $almacen, $peticion['certificatePassword'])) {
    $detalle = [];
    while ($linea = openssl_error_string()) {
        $detalle[] = $linea;
    }
    bitacora('RECHAZADA: no se pudo abrir el .p12 (clave incorrecta o archivo invalido)');
    responder(200, [
        'success' => false,
        'message' => 'No se pudo abrir el certificado: contrasena incorrecta o archivo .p12 invalido',
        'error'   => implode(' | ', $detalle),
    ]);
}

$certificado = openssl_x509_parse($almacen['cert']);
if ($certificado === false) {
    bitacora('RECHAZADA: certificado ilegible dentro del .p12');
    responder(200, ['success' => false, 'message' => 'El certificado contenido en el .p12 no se pudo leer']);
}

$firmante = $certificado['subject']['CN'] ?? nombreDistinguido($certificado['subject'] ?? []);
$emisor   = $certificado['issuer']['CN'] ?? nombreDistinguido($certificado['issuer'] ?? []);
$desde    = (int)($certificado['validFrom_time_t'] ?? 0);
$hasta    = (int)($certificado['validTo_time_t'] ?? 0);
$ahora    = time();

if ($hasta > 0 && $ahora > $hasta) {
    bitacora("RECHAZADA: certificado caducado ($firmante)");
    responder(200, [
        'success' => false,
        'message' => 'El certificado esta caducado (vencio el ' . date('Y-m-d', $hasta) . ')',
    ]);
}

if ($desde > 0 && $ahora < $desde) {
    bitacora("RECHAZADA: certificado aun no vigente ($firmante)");
    responder(200, [
        'success' => false,
        'message' => 'El certificado aun no entra en vigencia (rige desde el ' . date('Y-m-d', $desde) . ')',
    ]);
}

$aviso = null;

try {
    $pdfFirmado = estamparFirma($pdfBytes, [
        'firmante' => $firmante,
        'emisor'   => $emisor,
        'fecha'    => date('Y-m-d H:i:s'),
        'razon'    => $peticion['reason'] ?? 'Firma Digital - Quipux',
        'lugar'    => $peticion['location'] ?? 'Ecuador',
    ]);
    if (!is_string($pdfFirmado) || strncmp($pdfFirmado, '%PDF', 4) !== 0) {
        throw new RuntimeException('la salida de FPDI no es un PDF');
    }
} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $pdfFirmado = $pdfBytes;
    $aviso = 'No se pudo estampar el recuadro de firma (' . $e->getMessage() . '); se devuelve el PDF original.';
    bitacora("AVISO: $aviso");
}

bitacora(sprintf(
    'FIRMADO ok - firmante=%s - emisor=%s - pdf_in=%d bytes - pdf_out=%d bytes - keyword=%s',
    $firmante,
    $emisor,
    strlen($pdfBytes),
    strlen($pdfFirmado),
    $peticion['keyword'] ?? ''
));

$respuesta = [
    'success'         => true,
    'message'         => 'Documento firmado por el stub local de Smart-Sign',
    'signedPdfBase64' => base64_encode($pdfFirmado),
    'signatureDate'   => date('c'),
    'certificateInfo' => [
        'subjectName'  => $firmante,
        'issuerName'   => $emisor,
        'serialNumber' => (string)($certificado['serialNumberHex'] ?? $certificado['serialNumber'] ?? ''),
        'validFrom'    => $desde ? date('c', $desde) : '',
        'validTo'      => $hasta ? date('c', $hasta) : '',
    ],
];

if ($aviso !== null) {
    $respuesta['warning'] = $aviso;
}

responder(200, $respuesta);
