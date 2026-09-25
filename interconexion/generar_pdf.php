<?php
// This file is part of Quipux – Document Management System
//
// Quipux is free software and is currently under a process of technical
// modernization and functional improvement carried out by
// EXDUCERE ONLINE CIA. LTDA., as part of the development of a new version
// of the Quipux platform.
//
// Quipux is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Quipux is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Quipux. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    interconexion
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!function_exists('quipux_log_pdf')) {
    function quipux_log_pdf($origen, $detalle) {
        $linea = date('Y-m-d H:i:s') . " | $origen | $detalle\n";
        @file_put_contents(dirname(__DIR__) . "/bodega/tmp/quipux_pdf_error.log", $linea, FILE_APPEND);
    }
}

function ws_generar_pdf_base64($html, $plantilla, $servidor, $estado="", $numDocu="", $fechDocu = "", $numPag = "1", $orientPag="V")
{
    // Local processing optimization to prevent PHP-FPM deadlock
    if (strpos($servidor, '.test') !== false || strpos($servidor, 'localhost') !== false || strpos($servidor, '127.0.0.1') !== false) {
        require_once dirname(__DIR__)."/html_a_pdf/html_a_pdf.php";
        try {
            $archivo = "";
            if (trim($plantilla) != "") {
                if (is_file($plantilla)) $archivo = base64_encode(file_get_contents($plantilla));
            }
            return html_a_pdf(base64_encode($html), $archivo, $estado, $numDocu, $fechDocu, $numPag, $orientPag);
        } catch (Exception $e) {
            quipux_log_pdf('ws_generar_pdf_base64/local', 'doc=' . $numDocu . ' estado=' . $estado . ' error=' . $e->getMessage());
            return "0";
        }
    }

    try
    {
    	$wsdl = "$servidor/html_a_pdf/html_a_pdf.php?wsdl";
        
        // Setup SSL bypass for local/self-signed test environments
        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        $sslContext = stream_context_create($contextOptions);

        if(!@file_get_contents($wsdl, false, $sslContext)) {
            throw new SoapFault('Server', 'No WSDL found at ' . $wsdl);
		    return "0";
        }

        //Lamado a la clase SOAP PHP para instanciar clienteSOAP
        ini_set('soap.wsdl_cache_enabled', '0');
        $archivo = "";
        if (trim($plantilla) != "") {
            if (is_file($plantilla))
                $archivo = base64_encode(file_get_contents($plantilla));
        }
        $oSoap = new SoapClient("$wsdl",array(
            "trace" => 1, 
            "exceptions" => 0,
            "stream_context" => $sslContext
        ));

        $envioDatos=$oSoap->__soapcall('html_a_pdf',
            array(
              new SoapParam(base64_encode($html), "set_html"),
              new SoapParam($archivo, "set_pdf"),
              new SoapParam($estado, "set_estado"),
              new SoapParam($numDocu, "set_num_docu"),
              new SoapParam($fechDocu, "set_fech_docu"),
              new SoapParam($numPag, "set_num_pag"),
              new SoapParam($orientPag, "set_orient_pag")
           )
        );
    //Comentar
    /*
        var_dump($envioDatos);

            // Display the request and response
        print "<pre>\n";
        print "Request :\n".htmlspecialchars($oSoap->__getLastRequest()) ."\n";
        print "Response:\n".htmlspecialchars($oSoap->__getLastResponse())."\n";
        print "</pre>";
        /**/
    //Hasta aqui

        if (strtoupper(substr(trim((string)$envioDatos),0,4)) == "SOAP" or strlen((string)$envioDatos)<1000) {
			throw new SoapFault('Server', 'respuesta invalida (' . strlen((string)$envioDatos) . ' bytes): ' . substr(trim((string)$envioDatos),0,300));
        }
    	return $envioDatos;
    } catch (SoapFault $e) { //Captura los errores
        quipux_log_pdf('ws_generar_pdf_base64/soap', 'doc=' . $numDocu . ' estado=' . $estado . ' plantilla=' . $plantilla
            . ' html=' . strlen((string)$html) . 'b wsdl=' . $wsdl . ' | ' . $e->getMessage());
        return "0" ;
       }
}

// Esta función une varios archivos PDF en uno solo
// Recibe un arreglo de archivos PDF en base 64
// Retorna el archivo PDF en base 64
function ws_unir_archivos_pdf($archivos_base64, $servidor)
{
    try
    {
        $wsdl = "$servidor/html_a_pdf/html_a_pdf.php?wsdl";
        if(!@file_get_contents($wsdl)) {
            throw new SoapFault('Server', 'No WSDL found at ' . $wsdl);
        }

        //Lamado a la clase SOAP PHP para instanciar clienteSOAP
        ini_set('soap.wsdl_cache_enabled', '0');
        $oSoap = new SoapClient("$wsdl",array("trace" => 1, "exceptions" => 0));

        $envioDatos=$oSoap->__soapcall('unir_archivos_pdf',
            array(
                new SoapParam($archivos_base64, "set_archivos_pdf")
            )
        );
    //Comentar
    
/*        var_dump($envioDatos);
/*
            // Display the request and response
        print "<pre>\n";
        print "Request :\n".htmlspecialchars($oSoap->__getLastRequest()) ."\n";
        print "Response:\n".htmlspecialchars($oSoap->__getLastResponse())."\n";
        print "</pre>";
        /**/
    //Hasta aqui

        return $envioDatos;
    } catch (SoapFault $e) { //Captura los errores
//        var_dump($e);
        printf("No se generó correctamente el archivo PDF...");
        return "";
       }
}


// Contiene la función generar_pdf y recibe los parámetros $html(codigo html) y $plantilla (archivo PDF)
// Retorna un archivo pdf

function ws_generar_pdf($html, $plantilla, $servidor, $estado="", $numDocu="", $fechDocu = "", $numPag = "", $orientPag="V")
{
    // Local processing optimization to prevent PHP-FPM deadlock
    $servidor_safe = $servidor ?? '';
    if (strpos($servidor_safe, '.test') !== false || strpos($servidor_safe, 'localhost') !== false || strpos($servidor_safe, '127.0.0.1') !== false) {
        require_once dirname(__DIR__)."/html_a_pdf/html_a_pdf.php";
        try {
            $archivo = "";
            if (trim($plantilla) != "") {
                if (is_file($plantilla)) $archivo = base64_encode(file_get_contents($plantilla));
            }
            $pdfBase64 = html_a_pdf(base64_encode($html), $archivo, $estado, $numDocu, $fechDocu, $numPag, $orientPag);
            return base64_decode($pdfBase64);
        } catch (Exception $e) {
            quipux_log_pdf('ws_generar_pdf/local', 'doc=' . $numDocu . ' error=' . $e->getMessage());
            return "0";
        }
    }

    try
    {
    	$wsdl = "$servidor/html_a_pdf/html_a_pdf.php?wsdl";

        // Setup SSL bypass for local/self-signed test environments
        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        $sslContext = stream_context_create($contextOptions);

        if(!@file_get_contents($wsdl, false, $sslContext)) {
            throw new SoapFault('Server', 'No WSDL found at ' . $wsdl);
        }

        //Lamado a la clase SOAP PHP para instanciar clienteSOAP
        ini_set('soap.wsdl_cache_enabled', '0');
        $archivo = "";
        if (trim($plantilla) != "") {
            if (is_file($plantilla))
                $archivo = base64_encode(file_get_contents($plantilla));
        }
        $oSoap = new SoapClient("$wsdl",array(
            "trace" => 1, 
            "exceptions" => 0,
            "stream_context" => $sslContext
        ));

        $envioDatos=$oSoap->__soapcall('html_a_pdf',
            array(
              new SoapParam(base64_encode($html), "set_html"),
              new SoapParam($archivo, "set_pdf"),
              new SoapParam($estado, "set_estado"),
              new SoapParam($numDocu, "set_num_docu"),
              new SoapParam($fechDocu, "set_fech_docu"),
              new SoapParam($numPag, "set_num_pag"),
              new SoapParam($orientPag, "set_orient_pag")
           )
        );
    //Comentar
    /*
        var_dump($envioDatos);

            // Display the request and response
        print "<pre>\n";
        print "Request :\n".htmlspecialchars($oSoap->__getLastRequest()) ."\n";
        print "Response:\n".htmlspecialchars($oSoap->__getLastResponse())."\n";
        print "</pre>";
        /**/
    //Hasta aqui

        if (strtoupper(substr(trim((string)$envioDatos),0,4)) == "SOAP" or strlen((string)$envioDatos)<1000)
            throw new SoapFault('Server', 'respuesta invalida (' . strlen((string)$envioDatos) . ' bytes): ' . substr(trim((string)$envioDatos),0,300));

        $pdf_binario = base64_decode($envioDatos);
        if (substr($pdf_binario,0,4) != "%PDF")
            throw new SoapFault('Server', 'la respuesta decodificada no es un PDF');

        return $pdf_binario;
    } catch (SoapFault $e) { //Captura los errores
        quipux_log_pdf('ws_generar_pdf/soap', 'doc=' . $numDocu . ' plantilla=' . $plantilla
            . ' html=' . strlen((string)$html) . 'b wsdl=' . ($wsdl ?? '') . ' | ' . $e->getMessage());
        return "0";
       }
}
?>
