<?php
// Este archivo debe estar en el cliente. 
// Contiene la función generar_pdf y recibe los parámetros $html(codigo html) y $plantilla (archivo PDF)

function ws_generar_pdf($html, $plantilla, $servidor)
{
    try
    { 
    	$wsdl = "$servidor/html_a_pdf.php?wsdl";
        if(!@file_get_contents($wsdl)) {
            throw new SoapFault('Server', 'No WSDL found at ' . $wsdl);
        }

        //Lamado a la clase SOAP PHP para instanciar clienteSOAP
        ini_set('soap.wsdl_cache_enabled', '0');
        $archivo = "";
        if (trim($plantilla) != "") {
            if (is_file($plantilla))
                $archivo = base64_encode(file_get_contents($plantilla));
        }
        $oSoap = new SoapClient("$wsdl",array("trace" => 1, "exceptions" => 0));

        $envioDatos=$oSoap->__soapcall('html_a_pdf',
            array(
                new SoapParam(base64_encode($html), "set_html"),
                new SoapParam($archivo, "set_pdf"),
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

    	return base64_decode($envioDatos);
    } catch (SoapFault $e) { //Captura los errores 
//        var_dump($e);
        printf("No se generó correctamente el archivo PDF.");
        return "0";
    }  
} 

?> 
