<?php
/**
 * Recordset pagination with First/Prev/Next/Last links
 *
 * This file is part of ADOdb, a Database Abstraction Layer library for PHP.
 *
 * @package ADOdb
 * @link https://adodb.org Project's web site and documentation
 * @link https://github.com/ADOdb/ADOdb Source code and issue tracker
 *
 * The ADOdb Library is dual-licensed, released under both the BSD 3-Clause
 * and the GNU Lesser General Public Licence (LGPL) v2.1 or, at your option,
 * any later version. This means you can use it in proprietary products.
 * See the LICENSE.md file distributed with this source code for details.
 * @license BSD-3-Clause
 * @license LGPL-2.1-or-later
 *
 * @copyright 2000-2013 John Lim
 * @copyright 2014 Damien Regad, Mark Newnham and the ADOdb community
 */

class ADODB_Pager {
	var $id; 	// unique id for pager (defaults to 'adodb')
	var $db; 	// ADODB connection object
	var $sql; 	// sql used
	var $rs;	// recordset generated
	var $curr_page;	// current page number before Render() called, calculated in constructor
	var $rows;		// number of rows per page
    var $linksPerPage=10; // number of links per page in navigation bar
    var $showPageLinks;

	var $gridAttributes = 'width=100% border=1 bgcolor=white';

	// Localize text strings here
	var $first = '<code>|&lt;</code>';
	var $prev = '<code>&lt;&lt;</code>';
	var $next = '<code>>></code>';
	var $last = '<code>>|</code>';
	var $moreLinks = '...';
	var $startLinks = '...';
	var $gridHeader = false;
	var $htmlSpecialChars = true;
	var $page = 'Page';
	var $linkSelectedColor = 'red';
	var $cache = 0;  #secs to cache with CachePageExecute()

	var $toRefVar;
    var $toRefVars; // Added to prevent dynamic property creation warning
    var $toRefLinks; // Added to prevent dynamic property creation warning

	// Variables Orfeo
	var $toRefLink;
	var $ordenActual;
	var $orderTipo;
	var $rutaRaiz;
	var $checkAll;
	var $checkTitulo;
	var $descCarpetasGen; // Trae las Carpetas Generales del Usuario
	var $descCarpetasPer; // Trae las Carpetas Personales del Usuario
	var $linkCabecera =true; // Trae las Carpetas Personales del Usuario


	// Paginador con AJAX (actualizado por Pedro Gutierrez A. pedro.gutierrez@exducereonline.com)
	// Requiere que se incluya previamente el archivo
	var $paginador_ajax=false;
	var $link_ajax; // pagina que se llamará
	var $div_name_ajax; // Nombre del div que se cargara
	var $num_rows = 0; //Numero total de registros encontrados

	//----------------------------------------------
	// constructor
	//
	// $db	adodb connection object
	// $sql	sql statement
	// $id	optional id to identify which pager,
	//		if you have multiple on 1 page.
	//		$id should be only be [a-z0-9]*
	//
	function __construct(&$db,$sql,$id = 'adodb', $showPageLinks = false, $ordenActual="",$orderTipo="asc",$ajax=false,$div_name_ajax="div")
	{
		$this->ADODB_Pager($db,$sql,$id,$showPageLinks,$ordenActual,$orderTipo,$ajax,$div_name_ajax);
	}

	function ADODB_Pager(&$db,$sql,$id = 'adodb', $showPageLinks = false, $ordenActual="",$orderTipo="asc",$ajax=false,$div_name_ajax="div")
	{

		$this->db = $db;
		$this->ordenActual = $ordenActual;
		$this->orderTipo = $orderTipo;
		$this->div_name_ajax = $div_name_ajax;
		global $_SERVER,$PHP_SELF,$_GET;

		$curr_page = $id.'_curr_page';
		if (empty($PHP_SELF)) $PHP_SELF = $_SERVER['PHP_SELF'];

		$this->sql = $sql;
		$this->id = $id;
		$this->db = $db;
		$this->rutaRaiz = isset($db->rutaRaiz) ? $db->rutaRaiz : '';
		$this->showPageLinks = $showPageLinks;
		$this->paginador_ajax = $ajax;
		$this->link_ajax = "javascript:paginador_reload_$div_name_ajax('orderNo=$this->ordenActual&orderTipo=$this->orderTipo&$this->id"."_next_page=";

		//$this->rutaRaiz = $_GET['ruta_raiz'];

		$next_page = $id.'_next_page';

		if (isset($_GET[$next_page])) {
			$_GET[$curr_page] = $_GET[$next_page];
		}
		if (empty($_GET[$curr_page])) $_GET[$curr_page] = "1"; ## at first page

		$this->curr_page = $_GET[$curr_page];

	}

	//---------------------------
	// Display link to first page
	function Render_First($anchor=true)
	{
	global $PHP_SELF;
		if ($anchor) {
            if ($this->paginador_ajax) {
                // Determine link
                $link = $this->link_ajax . "1');";
                echo "<a href=\"$link\">$this->first</a> &nbsp;";
            } else {
        ?>
		<a href="<?php echo $PHP_SELF,'?',$this->id;?>_next_page=1"><?php echo $this->first;?></a> &nbsp;
	<?php
            }
		} else {
			print "$this->first &nbsp; ";
		}
	}

	//--------------------------
	// Display link to next page
	function render_next($anchor=true)
	{
	global $PHP_SELF;

		if ($anchor) {
            if ($this->paginador_ajax) {
                 $link = $this->link_ajax . ($this->rs->AbsolutePage() + 1) . "');";
                 echo "<a href=\"$link\">$this->next</a> &nbsp;";
            } else {
		?>
		<a href="<?php echo $PHP_SELF,'?',$this->id,'_next_page=',$this->rs->AbsolutePage() + 1 ?>"><?php echo $this->next;?></a> &nbsp;
		<?php
            }
		} else {
			print "$this->next &nbsp; ";
		}
	}

	//------------------
	// Link to last page
	//
	// for better performance with large recordsets, you can set
	// $this->db->pageExecuteCountRows = false, which disables
	// last page counting.
	function render_last($anchor=true)
	{
	global $PHP_SELF;

		if (!$this->db->pageExecuteCountRows) return;

		if ($anchor) {
            if ($this->paginador_ajax) {
                $link = $this->link_ajax . $this->rs->LastPageNo() . "');";
                echo "<a href=\"$link\">$this->last</a> &nbsp;";
            } else {
		?>
			<a href="<?php echo $PHP_SELF,'?',$this->id,'_next_page=',$this->rs->LastPageNo() ?>"><?php echo $this->last;?></a> &nbsp;
		<?php
            }
		} else {
			print "$this->last &nbsp; ";
		}
	}

	//---------------------------------------------------
	// original code by "Pablo Costa" <pablo@cbsp.com.br>
        function render_pagelinks()
        {
        global $PHP_SELF;
            $pages        = $this->rs->LastPageNo();
            $linksperpage = $this->linksPerPage ? $this->linksPerPage : $pages;
            $start = 1;
            for($i=1; $i <= $pages; $i+=$linksperpage)
            {
                if($this->rs->AbsolutePage() >= $i)
                {
                    $start = $i;
                }
            }
			$numbers = '';
            $end = $start+$linksperpage-1;
			$link = $this->id . "_next_page";
            if($end > $pages) $end = $pages;


			if ($this->startLinks && $start > 1) {
				$pos = $start - 1;
                if ($this->paginador_ajax) {
                    $jsLink = $this->link_ajax . $pos . "');";
                    $numbers .= "<a href=\"$jsLink\">$this->startLinks</a>  ";
                } else {
				    $numbers .= "<a href=$PHP_SELF?$link=$pos>$this->startLinks</a>  ";
                }
            }

			for($i=$start; $i <= $end; $i++) {
                if ($this->rs->AbsolutePage() == $i)
                    $numbers .= "<font color=$this->linkSelectedColor><b>$i</b></font>  ";
                else {
                    if ($this->paginador_ajax) {
                        $jsLink = $this->link_ajax . $i . "');";
                        $numbers .= "<a href=\"$jsLink\">$i</a>  ";
                    } else {
                        $numbers .= "<a href=$PHP_SELF?$link=$i>$i</a>  ";
                    }
                }

            }
			if ($this->moreLinks && $end < $pages) {
                if ($this->paginador_ajax) {
                    $jsLink = $this->link_ajax . $i . "');";
                    $numbers .= "<a href=\"$jsLink\">$this->moreLinks</a>  ";
                } else {
				    $numbers .= "<a href=$PHP_SELF?$link=$i>$this->moreLinks</a>  ";
                }
            }
            print $numbers . ' &nbsp; ';
        }
	// Link to previous page
	function render_prev($anchor=true)
	{
	global $PHP_SELF;
		if ($anchor) {
            if ($this->paginador_ajax) {
                $link = $this->link_ajax . ($this->rs->AbsolutePage() - 1) . "');";
                echo "<a href=\"$link\">$this->prev</a> &nbsp;";
            } else {
	?>
		<a href="<?php echo $PHP_SELF,'?',$this->id,'_next_page=',$this->rs->AbsolutePage() - 1 ?>"><?php echo $this->prev;?></a> &nbsp;
	<?php
            }
		} else {
			print "$this->prev &nbsp; ";
		}
	}

	//--------------------------------------------------------
	// Simply rendering of grid. You should override this for
	// better control over the format of the grid
	//
	// We use output buffering to keep code clean and readable.
	function RenderGrid()
	{
	global $gSQLBlockRows; // used by rs2html to indicate how many rows to display
		if (!function_exists('rs2html')) { include_once(dirname(__FILE__).'/tohtml.inc.php'); }
		ob_start();
		$gSQLBlockRows = $this->rows;
		rs2html($this->rs,$this->gridAttributes,$this->gridHeader,$this->htmlSpecialChars,true,!empty($this->checkAll));
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}

	//-------------------------------------------------------
	// Navigation bar
	//
	// we use output buffering to keep the code easy to read.
	function RenderNav()
	{
		ob_start();
		if (!$this->rs->AtFirstPage()) {
			$this->Render_First();
			$this->Render_Prev();
		} else {
			$this->Render_First(false);
			$this->Render_Prev(false);
		}
        if ($this->showPageLinks){
            $this->Render_PageLinks();
        }
		if (!$this->rs->AtLastPage()) {
			$this->Render_Next();
			$this->Render_Last();
		} else {
			$this->Render_Next(false);
			$this->Render_Last(false);
		}
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}

	//-------------------
	// This is the footer
	function RenderPageCount()
	{
		if (!$this->db->pageExecuteCountRows) return '';
		$lastPage = $this->rs->LastPageNo();
		if ($lastPage == -1) $lastPage = 1; // check for empty rs.
		if ($this->curr_page > $lastPage) $this->curr_page = 1;
		return "<font size=-1>$this->page ".$this->curr_page."/".$lastPage."</font>";
	}

	//-----------------------------------
	// Call this class to draw everything.
	function Render($rows=10, $toRefLink='', $checkbox='')
	{
	global $ADODB_COUNTRECS;

		$this->rows = $rows;

		if ($this->db->dataProvider == 'informix') $this->db->cursorType = IFX_SCROLL;

		$savec = $ADODB_COUNTRECS;
		if ($this->db->pageExecuteCountRows) $ADODB_COUNTRECS = true;
		if ($this->cache)
			$rs = $this->db->CachePageExecute($this->cache,$this->sql,$rows,$this->curr_page);
		else
			$rs = $this->db->PageExecute($this->sql,$rows,$this->curr_page);
		$ADODB_COUNTRECS = $savec;

		$this->rs = $rs;
		if (!$rs) {
			print "<h3>Query failed: $this->sql</h3>";
			return;
		}

		$total = (int)$rs->_maxRecordCount;
		if ($total < 0) $total = (int)$rs->RecordCount();
		$this->num_rows = ($total < 0) ? 0 : $total;

		if (!$rs->EOF && (!$rs->AtFirstPage() || !$rs->AtLastPage()))
			$header = $this->RenderNav();
		else
			$header = "&nbsp;";

		$grid = $this->RenderGrid();
		$footer = $this->RenderPageCount();

		$this->RenderLayout($header,$grid,$footer);

		$rs->Close();
		$this->rs = false;
	}

	//------------------------------------------------------
	// override this to control overall layout and formatting
	function RenderLayout($header,$grid,$footer,$attributes='border=1 bgcolor=beige')
	{
		echo "<table class='pager-wrap' ".$attributes."><tr><td class='pager-nav'>",
				$header,
			"</td></tr><tr><td>",
				$grid,
			"</td></tr><tr><td class='pager-foot'>",
				$footer,
			"</td></tr></table>";
	}
}


/**
 * Clase auxiliar para paginación vía AJAX.
 *
 * Genera e inserta el JavaScript necesario para recargar un `div`
 * con contenido paginado mediante peticiones AJAX. Usa el archivo
 * `ajax.js` incluido por el constructor para realizar las llamadas.
 *
 * Constructor:
 *  - $ruta_raiz: ruta base para recursos (imágenes, scripts).
 *  - $div: identificador del contenedor HTML que se actualizará.
 *  - $pagina: URL que responderá con el contenido paginado.
 *  - $variables: lista separada por comas de nombres de campos cuyos valores
 *    se añadirán a la petición.
 *  - $constantes: parámetros fijos añadidos a cada petición.
 *
 * @package ADOdb
 */
class ADODB_Pager_Ajax {

    /**
      * Constructor.
      *
      * Carga `js/ajax.js`, prepara la lista de variables a enviar y genera
      * las funciones JavaScript que realizan la recarga vía AJAX del
      * elemento HTML identificado por $div.
      *
      * Parámetros:
      *  - $ruta_raiz: ruta base para recursos e inclusión de `js/ajax.js`.
      *  - $div: id del contenedor HTML que se actualizará.
      *  - $pagina: URL que devolverá el contenido paginado.
      *  - $variables: nombres de campos separados por comas que se añadirán a la petición.
      *  - $constantes: parámetros fijos añadidos a cada petición (query string).
      *
      * Imprime directamente el bloque \<script\> con las funciones necesarias.
      *
      * @param string $ruta_raiz
      * @param string $div
      * @param string $pagina
      * @param string $variables
      * @param string $constantes
      * @return void
      */
//    public function __construct($ruta_raiz, $div, $pagina, $variables = "", $constants = "") {
//        include_once(dirname(__DIR__).'/js/ajax.js');
//        $params = $variables;
//        $constants = $constantes;
////        $ajax_params = array();
////        if (is_string($params) && $params !== '') {
////            $ajax_params = array_filter(array_map('trim', explode(',', $params)), 'strlen');
////        }
////
////        $js_params = "";
////        if (empty($constants)) {
////            $js_params += "&$constants";
////        }
////
////        if(!empty($ajax_params)){
////            foreach ($ajax_params as $tmp) {
////                if (trim($tmp != "")) {
////                    $js_params += "&$tmp=' + paginador_reload_obtener_dato('$tmp');";
////                }
////            }
////        }
//
////        var_dump($js_params); die();
//
//
////        if ($constantes != "") echo "parametros += '&$constantes';\n";
////        foreach ($ajax_variables as $tmp) {
////            if (trim($tmp != ""))
////                echo "parametros += '&$tmp=' + paginador_reload_obtener_dato('$tmp');\n";
////        }
//
//        $ajax_variables = explode(',',$variables);
//
//        echo "<script>\n
//                function paginador_reload_div(parametros) {\n
//                    paginador_reload_$div(parametros);\n
//                }\n
//                function paginador_reload_$div(parametros) {\n
//                    document.getElementById('$div').innerHTML = '<table width=\"50%\" border=\"0\"><tr><td align=\"center\">".
//                "<br><br>Por favor espere mientras se procesa su petici&oacute;n.<br>&nbsp;<br>".
//                "<img src=\"$ruta_raiz/imagenes/progress_bar.gif\"><br>&nbsp;</td></tr></table>';\n";
//        if ($constantes != "") echo "parametros += '&$constantes';\n";
//        foreach ($ajax_variables as $tmp) {
//            if (trim($tmp != ""))
//                echo "parametros += '&$tmp=' + paginador_reload_obtener_dato('$tmp');\n";
//        }
//        echo "nuevoAjax('$div', 'GET', '$pagina', parametros);\n
//                }\n
//                function paginador_reload_obtener_dato(objeto) {
//                    switch (document.getElementById(objeto).type.toLowerCase()) {
//                        case 'radio':
//                            var i;
//                            var elementos = document.getElementsByName(objeto);
//                            for (i=0 ; i<elementos.length ; i++) {
//                                if (elementos[i].checked)
//                                    return elementos[i].value;
//                            }
//                            break;
//                        case 'span':
//                        case 'div':
//                            return document.getElementById(objeto).innerHTML;
//                            break;
//                        default:
//                            return document.getElementById(objeto).value;
//                            break;
//                    }
//                }
//            </script>\n";
//    }

    /*
        var $ajax_variables; // Array (Se lo llena con add_variable_ajax)
        var $ajax_constantes; // str con datos adicionales
        var $ajax_div; // div en donde se guardará el resultado
        var $ajax_pagina; // pagina que se llamará
    /* */
	function __construct($ruta_raiz, $div, $pagina, $variables="", $constantes="") {
		include_once "$ruta_raiz/js/ajax.js";
		$ajax_variables = explode(',',$variables);
		echo "<script>\n
                function paginador_reload_div(parametros) {\n
                    paginador_reload_$div(parametros);\n
                }\n
                function paginador_reload_$div(parametros) {\n
                    document.getElementById('$div').innerHTML = '<table width=\"50%\" border=\"0\"><tr><td align=\"center\">".
			"<br><br>Por favor espere mientras se procesa su petici&oacute;n.<br>&nbsp;<br>".
			"<img src=\"/imagenes/progress_bar.gif\"><br>&nbsp;</td></tr></table>';\n";
		if ($constantes != "") echo "parametros += '&$constantes';\n";
		foreach ($ajax_variables as $tmp) {
			if (trim($tmp != ""))
				echo "parametros += '&$tmp=' + paginador_reload_obtener_dato('$tmp');\n";
		}
		echo "nuevoAjax('$div', 'GET', '$pagina', parametros);\n
                }\n
                function paginador_reload_obtener_dato(objeto) {
                    var elem = document.getElementById(objeto);
                    if (!elem) return '';
                    switch (elem.type.toLowerCase()) {
                        case 'radio':
                            var i;
                            var elementos = document.getElementsByName(objeto);
                            for (i=0 ; i<elementos.length ; i++) {
                                if (elementos[i].checked)
                                    return elementos[i].value;
                            }
                            break;
                        case 'span':
                        case 'div':
                            return elem.innerHTML;
                            break;
                        default:
                            return elem.value;
                            break;
                    }
                }
            </script>\n";
	}
}
