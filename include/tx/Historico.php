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
 * @package    tx
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Historico
{
 /**
   * Clase que maneja los Historicos de los documentos
   * @db Objeto conexion
   */
    var $db;
    //var $FechaEnvioFisico;

    function __construct($db)
    {
 	//Constructor de la clase Historico
	$this->db = $db;
    }

//  Clase que inserta el recorrido de los documentos
    function insertarHistorico($radicado, $usua_ori, $usua_dest, $observacion, $tipoTx, $referencia="")
    {
        $ruta_raiz = $this->db->rutaRaiz;
        include_once(dirname(__DIR__,2).'/funciones.php');

        $observacion = str_replace("\n", "<br>", limpiar_sql($observacion)); // Para que conserve los saltos de línea

        $record["RADI_NUME_RADI"] = limpiar_sql($radicado);
        $record["USUA_CODI_ORI"] = $usua_ori ? (int)limpiar_sql($usua_ori) : 0;
        $record["USUA_CODI_DEST"] = $usua_dest ? (int)limpiar_sql($usua_dest) : 0;
        $record["SGD_TTR_CODIGO"] = (int)limpiar_sql($tipoTx);
        $record["HIST_OBSE"] = substr($observacion,0,600);
        $record["HIST_FECH"] = date("Y-m-d H:i:s");
        
        if (trim($referencia ?? '') != "")
            $record["HIST_REFERENCIA"] = limpiar_sql(substr($referencia,0,50));
        
        $ok = $this->db->insert("HIST_EVENTOS", $record, "true");
        if (!$ok) {
            echo "<pre style='background:#f8d7da;color:red;padding:10px;font-weight:bold'>"
               . "ERROR in insertarHistorico:\n"
               . htmlspecialchars($this->db->conn->ErrorMsg()) . "\n"
               . htmlspecialchars($this->db->querySql ?? 'unknown sql')
               . "</pre>";
        }

        if ($ok) $this->registrarSumillas($radicado, $usua_ori);

        $this->auditarSubrogacion($radicado, $tipoTx);

        return ($radicado);
    }

    /**
     * Deja registradas, ligadas a este evento, las sumillas que el usuario eligió
     * en el árbol de la pantalla de reasignación.
     *
     * Se engancha aquí porque insertarHistorico() es el punto por el que pasan
     * todas las transacciones, y porque es el único momento en que se conoce el
     * hist_codi contra el que hay que colgarlas para que salgan en la hoja de ruta.
     *
     * Sólo se registran en el evento del usuario que las eligió: una reasignación
     * desde bandeja compartida inserta antes el traspaso jefe -> asistente, y la
     * sumilla no pertenece a ese salto.
     */
    function registrarSumillas($radicado, $usua_ori)
    {
        include_once(dirname(__DIR__).'/sumillas/Sumillas.php');
        if (count(sumillas_seleccion()) == 0) return;
        if ((int)$usua_ori !== (int)($_SESSION["usua_codi"] ?? 0)) return;

        $hist_codi = sumillas_ultimo_hist_codi($this->db, $radicado, $usua_ori);

        sumillas_registrar($this->db, $radicado, $hist_codi, $usua_ori);
    }

    /**
     * Deja constancia de la persona real cuando la acción se ejecuta bajo un
     * cargo subrogado.
     *
     * hist_eventos registra la autoría del CARGO (que es lo correcto de cara al
     * documento), de modo que sin este registro complementario se perdería quién
     * actuó realmente. Se engancha aquí porque insertarHistorico() es el punto
     * por el que pasan todas las transacciones del sistema.
     */
    function auditarSubrogacion($radicado, $tipoTx)
    {
        if (empty($_SESSION['subrogacion_codi'])) return;

        $real     = (int)($_SESSION['usua_codi_real'] ?? 0);
        $actuando = (int)($_SESSION['usua_codi'] ?? 0);
        if ($real <= 0 || $real === $actuando) return;

        include_once(dirname(__DIR__) . '/subrogacion/Subrogacion.php');
        $subrogacion = new Subrogacion($this->db);
        $subrogacion->auditar(
            (int)$_SESSION['subrogacion_codi'],
            $real,
            $actuando,
            'TX_' . $tipoTx,
            $radicado);
    }


    function insertarHistoricoTarea($tarea, $radicado, $observacion, $tipoTx, $referencia="")
    {
        $ruta_raiz = $this->db->rutaRaiz;
        include_once(dirname(__DIR__,2).'/funciones.php');

        $hist_codi = $this->db->nextId("sec_tarea_hist_eventos");
        $observacion = str_replace("\n", "<br>", limpiar_sql($observacion));

        $record["tarea_hist_codi"] = $hist_codi;
        $record["tarea_codi"] = 0 + $tarea;
        $record["radi_nume_radi"] = limpiar_sql($radicado);
        $record["usua_codi_ori"] = is_scalar($_SESSION["usua_codi"] ?? 0) ? $_SESSION["usua_codi"] ?? 0 : 0;
        $record["accion"] = limpiar_sql($tipoTx);
        $record["comentario"] = $observacion;
        $record["fecha"] = date("Y-m-d H:i:s");
        if (trim($referencia) != "")
            $record["referencia"] = limpiar_sql(substr($referencia,0,50));
        
        $this->db->insert("tarea_hist_eventos", $record, "true");
        
        $sql = "update tarea set comentario_fin=$hist_codi where tarea_codi=$tarea";
        $this->db->conn->Execute($sql);

        return $hist_codi;
    }

    //  Clase que inserta el historico de envios fìsicos
    function insertarHistoricoFisico($radicado,$secuencial,$fecha, $usua_ori, $usua_dest, $observacion, $estado, $usua_resp,$estadoEnv)
    {
        $ruta_raiz = $this->db->rutaRaiz;
        include_once(dirname(__DIR__,2).'/funciones.php');

        
        if (trim($fecha) != "")
           $record["HIST_FECH_ENVIO"] = limpiar_sql($fecha);

        $record["HIST_CODI"] =  limpiar_sql($secuencial);
        $record["RADI_NUME_RADI"] = limpiar_sql($radicado);
        $record["USUA_CODI_ENVIADO"] = $usua_ori ? (int)limpiar_sql($usua_ori) : 0;

        if (trim($usua_resp ?? '') != "")
            $record["USUA_RESPONSABLE"] = limpiar_sql($usua_resp);

        if (trim($estado) != "")
            $record["ESTADO"] = limpiar_sql($estado);

        if (trim($estadoEnv) != "")
            $record["ESTADOENVIO"] = limpiar_sql($estadoEnv);
        
        $this->db->insert("HIST_ENVIO_FISICO", $record, "true");
        

        return ($radicado);
    }


//  Clase que inserta el recorrido en todos los documentos que tienen el mismo temporal, 
//  es decir, en el documento original y en las copias que se envían a los distintos destinatarios del documento
    function insertarHistoricoTemporal($radicado, $usua_ori, $usua_dest, $observacion, $tipoTx, $referencia="")
    {
        $sql = "select radi_nume_radi from radicado where radi_nume_temp=$radicado";
        $rs = $this->db->conn->Execute($sql);
        while (!$rs->EOF) {
            $this->insertarHistorico($rs->fields["RADI_NUME_RADI"], $usua_ori, $usua_dest, $observacion, $tipoTx, $referencia);
            $rs->MoveNext();
        }
        return ($radicado);
    } 

} // end of Historico
?>
