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
 * @package    anexos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function guardarBackUp($db,$usr_codigo,$permiso,$dependencia,$dependencia_eli,$usr_inst_actual){
  unset($codigosback);
  //echo $dependencia;
  $depe_flag = substr($dependencia,1);
  $depe_flag = substr($depe_flag,0,-1);
  //array nuevas dependencias
  $codigosback = explode(",", $depe_flag);    
  //agregar permiso
  //Array dependencia con usuarios diferentes
  $dependencia_flag = encontrarOcupada($db,$permiso,$usr_codigo,$depe_flag,'valor');
  //print_r($dependencia_flag);
  //array dependencias actuales
  $depe_flag_actual = permisosUsrActual($db,$usr_codigo,$permiso,$usr_inst_actual);
  //PROCESO GUARDAR  
  //recorremos las dependencias nuevas
  foreach($codigosback as $tmp=>$datos){
      //comparo si existe la dependencia con otro usuario
      if (is_array($dependencia_flag)){
          //compruebo que no tenga otro usuario
          if (!in_array($codigosback[$tmp], $dependencia_flag, true)){
              //si no tiene otro usuario, compruebo que no tenga el usuario actual
              //para evitar warning en la base de datos(llave duplicada)              
              if (is_array($depe_flag_actual)){//si es array
                  //si el usuario actual no tiene esa dependencia con ese registro
                  //procedo a guardar
                 if (!in_array($codigosback[$tmp], $depe_flag_actual, true)){                     
                     guardarPermisoBack($db,$permiso,$usr_codigo,$codigosback[$tmp]);
                 }                    
              }//fin si es array
              //si no es array quiere decir que no tiene dependencias el usuario actual
              else{//si no tiene registros el usuario procedo a guardar uno por uno las dependencias                             
                    guardarPermisoBack($db,$permiso,$usr_codigo,$codigosback[$tmp]);               
              }//fin else no tiene registros              
          }//fin comprueba que no tiene otro usuario          
      }//si no es array, quiere decir que nadie tiene permisos, procedo a guardar
      else{
               //si no hay nadie en esa tabla de usuario con institucion y dependencia guardo
               //directamente 
          
          if (is_array($depe_flag_actual)){
                if (!in_array($codigosback[$tmp], $depe_flag_actual, true))
                guardarPermisoBack($db,$permiso,$usr_codigo,$codigosback[$tmp]);
          }else{
              guardarPermisoBack($db,$permiso,$usr_codigo,$codigosback[$tmp]);
          }
                
      }
        
  }//fin for
  
  //PROCESO ELIMINAR
  $depe_flag_eli = substr($dependencia_eli,1);
  $depe_flag_eli = substr($depe_flag_eli,0,-1);
  //array nuevas dependencias
  $codigosback_eli = explode(",", $depe_flag_eli);
  foreach($codigosback_eli as $tmp=>$datos){
      if (is_array($depe_flag_actual)){//si tiene dependencias procedo a revisar una a una para borrar
          if (in_array($codigosback_eli[$tmp], $depe_flag_actual, true)){                
                     eliminarPermiso($db,$permiso,$usr_codigo,$codigosback_eli[$tmp]);
          } 
      }
  }
  //         eliminarPermiso($db,$permiso,$usr_codigo,$codigosbackel[$tmp]);
  
  $mensaje = encontrarOcupada($db,$permiso,$usr_codigo,$depe_flag,'mensaje');
  if (is_array($mensaje) && !empty($mensaje))
    return $mensaje[1];
}
function guardarPermisoBack($db,$id_permisoBack,$usr_codigo,$depe_codigo){
    
         $record["ID_PERMISO"] = $id_permisoBack;
         $record["USUA_CODI"] = $usr_codigo;
         $record["DEPE_CODI"] = $depe_codigo;
         
         $encontrada = ["", ""];
         if ($id_permisoBack!='' && $usr_codigo!='' && $depe_codigo!=''){             
                $insertSQL = $db->conn->Replace("PERMISO_USUARIO_DEP", $record, "", false,false,true,false);
         }
         echo "<tr><td>$encontrada[1]</td></tr>";
}
function eliminarPermiso($db,$id_permisoBack,$usr_codigo,$depe_codigo){
    if ($id_permisoBack!='' && $usr_codigo!='' && $depe_codigo!=''){
        $sqldel = "delete from permiso_usuario_dep where id_permiso = $id_permisoBack
        and usua_codi = $usr_codigo and depe_codi = $depe_codigo";
        $db->conn->Execute($sqldel);
        //echo $sqldel;
    }
}
//permisos en usuarios diferentes
function encontrarOcupada($db,$permiso,$usr_codigo,$dep,$tipo='mensaje'){
    $mensaje=array();
    // Initialize variables
    $nombre_dep = "";
    $dependencia_ex = [];
    $mensaje_val = [];
    
    $sql = "select pd.usua_codi, pd.depe_codi, us.nombre_us, dep.depe_nomb
    from permiso_usuario_dep as pd
    left outer join (select usua_codi, usua_nomb || ' '  || usua_apellido as nombre_us from usuarios) as us
    on pd.usua_codi = us.usua_codi
    left outer join (select depe_nomb, depe_codi from dependencia) as dep
    on pd.depe_codi = dep.depe_codi
    where pd.id_permiso = $permiso
    and pd.depe_codi in ($dep)
    and pd.usua_codi <> $usr_codigo";
  //echo $sql. "<br>";
  $rs = $db->conn->Execute($sql);
  
  // Use RecordCount or check for EOF, _numOfRows is internal/deprecated
  if($rs && !$rs->EOF){
     $nombre_us = isset($rs->fields["NOMBRE_US"]) ? $rs->fields["NOMBRE_US"] : "Usuario Desconocido";
    
     $i = 0;
     while (!$rs->EOF) {
        $nombre_dep = $nombre_dep . "<br>" . (isset($rs->fields["DEPE_NOMB"]) ? $rs->fields["DEPE_NOMB"] : "");
        $dependencia_ex[$i] = $rs->fields["DEPE_CODI"];        
        $rs->MoveNext();
        $i++;
     }
    $mensaje_val[0] = 1;
    $mensaje_val[1] = "No se guardó el permiso para la(s) siguiente(s) área(s), porque el usuario " .$nombre_us . " ya las tiene asignada(s): <br> " . $nombre_dep;
  }
  
  if ($tipo!='mensaje')
      return $dependencia_ex;
  else
      return $mensaje_val;
}
//permisos en usuario actual
function permisosUsrActual($db,$usr_codigo,$permiso,$usr_inst_actual){
    $sql = "select dep.DEPE_CODI, dep.DEPE_NOMB, permiso_usr_dep.* from dependencia as dep
          left outer join (select depe_codi as permiso_asignado
          from permiso_usuario_dep
	  where usua_codi = $usr_codigo
	  and id_permiso = $permiso) as permiso_usr_dep
          on  dep.depe_codi = permiso_usr_dep.permiso_asignado
          where dep.inst_codi = $usr_inst_actual and dep.depe_estado = 1
          order by dep.DEPE_NOMB";
  //echo $sql;
   $rs = $db->conn->Execute($sql);
   $i = 0;
   $nombre_dep = "";
   $dependencia_ex = array();
     while (!$rs->EOF) {
        $nombre_dep = $nombre_dep . "<br>" . $rs->fields["DEPE_NOMB"];
        if ($rs->fields["PERMISO_ASIGNADO"]){
            $dependencia_ex[$i] = $rs->fields["DEPE_CODI"];
            $i++;
        }
        $rs->MoveNext();
        
     }
     return $dependencia_ex;
}
?>