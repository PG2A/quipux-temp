<?php
try {
    $wsdl = "https://docs.ucuenca.edu.ec/html_a_pdf/html_a_pdf.php?wsdl";
    $oSoap = new SoapClient($wsdl, array("trace" => 1, "exceptions" => 1));
    $envioDatos = $oSoap->__soapcall('html_a_pdf', array(
              new SoapParam(base64_encode("<h1>test</h1>"), "set_html"),
              new SoapParam("", "set_pdf"),
              new SoapParam("1", "set_estado"),
              new SoapParam("123", "set_num_docu"),
              new SoapParam("2026", "set_fech_docu"),
              new SoapParam("1", "set_num_pag"),
              new SoapParam("V", "set_orient_pag")
    ));
    var_dump($envioDatos);
    echo "\nHEX: " . bin2hex($envioDatos) . "\n";
    echo "CONTENT: " . $envioDatos . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
