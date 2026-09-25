<?php
// Clase que realiza la mezcla de dos archivos PDF poniendo a uno como plantilla del otro.

// Unicamente se debe llamar a la función  mezclar_pdf y se envía como parámetros el archivo y la plantilla en ese orden
require_once(__DIR__."/tcpdf/tcpdf.php");
require_once(__DIR__."/fpdi/fpdi.php");

class PDF extends FPDI {

    var $_plantilla_pdf;        //Variable en la que se guarda la plantilla (archivo pdf)
    var $_plantilla_pdf_borr;	//Variable en la que se guarda la plantilla (archivo pdf borrador)
    var $_total_paginas;        //total de paginas del documento pdf
    var $band;                  //Bandera para determinar si existe o no plantilla
    var $estado_docu;           //Variable en la que se guarda el estado del documento para determinar si se pone la plantilla borrador
    var $numero_docu;           //Variable que guarda el numero de documento para mostrar en todas las paginas
    var $fecha_docu;            //Variable que guarda la fecha de documento para mostrar en todas las paginas
    var $numero_pag;            //Variable que guarda en numero de paginas en la cual se debe presentar el documento
    var $_tipo_sistema;         //Sistema desde donde se genera el archivo pdf
    var $tipo_letra="Times";    //Tipo de letra con la que se imprimirán los datos adicionales
    var $tipo_letra_estilo="B";    //Tipo de letra con la que se imprimirán los datos adicionales
    
    /* Importamos el archivo plantilla */
    function set_archivo_plantilla($archivo) {
            $this->setSourceFile($archivo);
            $this->_plantilla_pdf = $this->importPage(1);
    }

    /* Importamos el archivo plantilla para borrador */
    function set_archivo_pl_borrador($archivo_borr) {
        $this->setSourceFile($archivo_borr);
        $this->_plantilla_pdf_borr = $this->importPage(1);
    }

    /* Definimos el encabezado, en este caso hacemos que imprima el pdf plantilla */
    function Header() {
        if($this->band)
            $this->useTemplate($this->_plantilla_pdf);
        if($this->estado_docu == 1)
            $this->useTemplate($this->_plantilla_pdf_borr);
        // +1.3 mm (~5 px) para que el numero de documento no roce la hoja membretada.
        $this->SetY(41.3);	//Posición 41.3 mm abajo del filo superior de la pagina
        $this->SetFont($this->tipo_letra, $this->tipo_letra_estilo, 11);	   //fuente del texto
        $this->Cell(170,20,$this->numero_docu,0,0,'R');	   //fecha del documento
        $this->SetY(46.3);	  //Posición 46.3 mm abajo del filo superior de la pagina (baja con el numero para no perder la separacion)
        $this->SetFont($this->tipo_letra, $this->tipo_letra_estilo, 11);	   //fuente del texto /* */
        $this->Cell(170,26,$this->fecha_docu,0,0,'R');	   //numero de documento
    }
    
    /* Definimos el pie de página, en este caso que aparezca el numero de pagina */
    function Footer() {
        $this->SetY(-10);	//Posición 15 mm arriba del filo inferior de la pagina
        $this->SetFont('helvetica','I',8);	//fuente del texto
        $this->Cell(0,5,$this->PageNo().'/'.$this->_total_paginas,0,0,'R');	//numero de página a la derecha
        $this->SetXY(4, -8);
        $this->SetFont('helvetica','I',5);	//fuente del texto
        //$this->Write(0, "* Documento generado por Quipux $this->_tipo_sistema");
        $this->Cell(0,5,"* Documento generado por Quipux $this->_tipo_sistema",0,0,'L');	//numero de página a la derecha
        //    $this->Cell(5,10,'Documento generado en el Sistema de Gestión Documental Quipux',0,0,'L');	//numero de página a la derecha
    }

    /* Realiza la mezcla del archivo original con la plantilla */
    function mezclar_pdf($archivo, $plantilla="") {
        $pdf_borr = __DIR__ . "/pdf_borrador/pl_borrador.pdf";
        if($plantilla != "")
            $this->band = true;
        else
            $this->band = false;

        if($this->band)
            $this->set_archivo_plantilla($plantilla);

        if($this->estado_docu == 1)
            $this->set_archivo_pl_borrador($pdf_borr);

        $pagecount = $this->setSourceFile($archivo);
        $this->_total_paginas = $pagecount;
        for ($i = 1; $i <= $pagecount; $i++) {
            $tplidx = $this->ImportPage($i);
            $s = $this->getTemplatesize($tplidx);
            $this->AddPage('P', array($s['w'], $s['h']));
            $this->useTemplate($tplidx);
        } 
    } 
}
?>
