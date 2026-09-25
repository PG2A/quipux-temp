<?php
// Clase que realiza la mezcla de dos archivos PDF poniendo a uno como plantilla del otro.

// Unicamente se debe llamar a la función  mezclar_pdf y se envía como parámetros el archivo y la plantilla en ese orden
require_once("tcpdf/tcpdf.php");
require_once("fpdi/fpdi.php");

class UnirPDFQ extends FPDI {
    /* Definimos el encabezado, en este caso hacemos que imprima el pdf plantilla */
    function Header() {
    }
    
    /* Definimos el pie de página, en este caso que aparezca el numero de pagina */
    function Footer() {
    }

    /* Realiza la mezcla del archivo original con la plantilla */
    function unir_pdf($archivos) {
        foreach ($archivos as $nombre_archivo) {
            $pagecount = $this->setSourceFile($nombre_archivo);
            for ($i = 1; $i <= $pagecount; $i++) {
                $tplidx = $this->ImportPage($i);
                $s = $this->getTemplatesize($tplidx);
                $this->AddPage('P', array($s['w'], $s['h']));
                $this->useTemplate($tplidx);
            }
        }
    } 
}
?>
