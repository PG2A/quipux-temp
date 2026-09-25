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
 * @package    usuarios
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// session_start(); // Removed to avoid conflict with rec_session.php
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__) ."/funciones_interfaz.php");

echo "<!DOCTYPE html>".html_head();
?>

<script type="text/javascript">
    function llamaCuerpo(parametros){
        if (window.bloquearPantalla) bloquearPantalla('Cargando...');
        top.frames['mainFrame'].location.href=parametros;
    }
</script>

<body>
    <?php dibujar_loader_pantalla('Cambiando de institución...', '/imagenes/escudo_blanco.png'); ?>
    <center>
    <br><br>
<?php 
/**
* Si el usuario que ingresa al sistema es el usuario super-administrador cargar el combo con la lista de las 
* instituciones.
**/
    if($_SESSION["usua_codi"]==0 or $_SESSION["admin_institucion"]==1) {
        $inst_actu = isset($_SESSION["inst_codi"]) ? $_SESSION["inst_codi"] : 0; // Default initialization

        if (isset($_POST["inst_actu"])) {
            $inst_codi = (int)$_POST["inst_actu"];
            if ($inst_codi != 0) {
                $_SESSION["inst_codi"] = $inst_codi;
                $_SESSION["inst_nombre"] = (string)$db->conn->GetOne("select inst_nombre from institucion where inst_codi=$inst_codi");
                $inst_actu = $inst_codi;
            }
        }
   
    $sql = "select inst_nombre, inst_codi from institucion where inst_estado =1 order by inst_nombre asc";
    $rs = $db->conn->Execute($sql); // Use Execute instead of query for consistency
    
    // Ensure we have numeric indices for GetMenu to work reliably if global ADODB_FETCH_MODE isn't set correctly
    $prevMode = $db->conn->fetchMode;
    $db->conn->SetFetchMode(ADODB_FETCH_NUM);
    // Re-execute or just rely on GetMenu handling it if we pass a recordset?
    // Actually, SetFetchMode affects future fetches. But GetMenu iterates the recordset.
    // If we already executed, the recordset might be bound to the old mode.
    // Let's re-execute with NUM mode.
    $rs = $db->conn->Execute($sql);
    
    $menu_institucion =  $rs->GetMenu2("inst_actu", $inst_actu, "0:&lt;&lt seleccione &gt;&gt;", false, 0, "class='select' Onchange='bloquearPantalla();document.formulario.submit()'");
    
    // Restore mode
    $db->conn->SetFetchMode($prevMode);

?>
    <form name="formulario" id="formulario" method="post" action="">
        <table width="50%" border="0" cellpadding="0" cellspacing="5" class="borde_tab admin-card">
            <tr>
                <td colspan="2" class="titulos4"><center><strong>Instituciones para Administrar</strong></center></td>
            </tr>
            <tr>
                <td align="center" class="listado2"><?= $menu_institucion ?></td>
            </tr>
        </table>
    </form>
    <br>
<?php } ?>

    <table width="50%" align="center" border="0" cellpadding="0" cellspacing="5" class="borde_tab admin-card">
        <tr>
            <td colspan="2" class="titulos4"><center><strong>M&oacute;dulo de Administraci&oacute;n</strong></center></td>
        </tr>
<?php
    $num_menu = 0;
    echo dibujar_opcion_menu("usuarios/cambiar_password.php","Cambio de contrase&ntilde;a","Opci&oacute;n para cambiar la contrase&ntilde;a del usuario actual");

    if($_SESSION["tipo_usuario"]==1 or $_SESSION["usua_codi"]==0) { // Valido si es funcionario publico o super administrador
        echo dibujar_opcion_menu("listas/listas.php", "Listas de env&iacute;o", "Opci&oacute;n para Administrar Lista de usuarios para env&iacute;o de correspondencia");

        if($_SESSION["usua_admin_sistema"]==1 or $_SESSION["usua_perm_ciudadano"]==1)
            echo dibujar_opcion_menu("ciudadanos/cuerpoUsuario_ext.php?accion=2", "Ciudadanos", "Opci&oacute;n para administrar Usuarios Ciudadanos");

        if($_SESSION["usua_admin_sistema"]==1) {
            echo dibujar_opcion_menu("usuarios/mnuUsuarios.php", "Usuarios internos", "Opci&oacute;n para administrar Usuarios del Sistema de la Instituci&oacute;n Actual");
            echo dibujar_opcion_menu("dependencias/mnu_dependencias.php", "&Aacute;reas", "Opci&oacute;n para administrar &Aacute;reas de la Instituci&oacute;n");
            echo dibujar_opcion_menu("tbasicas/adm_instituciones.php", "Instituciones", "Opci&oacute;n para administrar Instituciones");            
            echo dibujar_opcion_menu("tbasicas/adm_formato_doc.php", "Numeraci&oacute;n de documentos", "Opci&oacute;n para administrar la numeraci&oacute;n de los documentos");
        }

        if($_SESSION["usua_codi"]==0) {
            echo dibujar_opcion_menu("../tx/revertir_firma_digital.php", "Regeneraci&oacute;n de archivo PDF", "Revertir la firma digital en los documentos");
            echo dibujar_opcion_menu("mensajes_alerta/mensajes_alerta_menu.php", "Administrar Alertas del Sistema", "Opci&oacute;n para administrar alertas.");
            echo dibujar_opcion_menu("catalogos/ciudad.php", "Ciudad", "Opci&oacute;n para administrar las ciudades");
            echo dibujar_opcion_menu("catalogos/titulo_usuario.php", "Título Acad&eacute;mico", "Opci&oacute;n para administrar los títulos académicos");
            echo dibujar_opcion_menu("catalogos/contenido.php", "Administración de Contenidos", "Opci&oacute;n para administrar los contenidos del sistema");
            if(date("m")==1 and date("d")==1)
                echo dibujar_opcion_menu("confirmar_inicio_secuencias();", "Inicializar Secuencias para el a&ntilde;o ". date("Y"), "Opci&oacute;n para inicializar secuencias al comenzar un nuevo a&ntilde;o", true);
        }

        if($_SESSION["usua_codi"]!=0)
            echo dibujar_opcion_menu("/backup/respaldo_menu.php", "Respaldo de Documentos", "Opci&oacute;n para solicitar el respaldo de documentos del usuario actual.");

        if($_SESSION["usua_admin_sistema"]==1 or $_SESSION["usua_codi"]==0) {
            echo dibujar_opcion_menu("../metadatos/metadatos_menu.php", "Metadatos de Documentos", "Opci&oacute;n para administrar metadatos.");
        }
        
        if ($_SESSION["perm_actualizar_sistema"] == 1) {
            echo dibujar_opcion_menu("archivos/archivos_menu.php", "Administrar repositorio de archivos", "Administra el repositorio para los archivos anexos y generados en Quipux");
        }
        if($_SESSION["usua_admin_sistema"]==1)
            echo dibujar_opcion_menu("membretes/mnu_membretes.php", "Hojas Membretadas", "Opci&oacute;n para administrar las hojas membretadas con las que se generan los documentos");       
    } // IF Si es funcionario publico


    if($_SESSION["tipo_usuario"]==0 and date("m")==1 and date("d")==1) { ?>
        <script type="text/Javascript">
            function confirmar_inicio_secuencias() {
                var texto = prompt('Por favor ingrese el siguiente texto:\n"QuiPux 2012"');
                if (texto == 'QuiPux 2012') {
                    if (confirm('¿Seguro que desea inicializar todas las secuencias del sistema?')) {
                        window.location = 'tbasicas/cambio_de_anio.php';
                    }
                }else {
                    if (texto != null)
                    alert ('Error en el texto de validacion.\nUsted ingreso la cadena: "'+texto+'"');
                }
            }
        </script>
<?php } ?>


  </table>
</center>
</body>
</html>

<?php

function dibujar_opcion_menu ($pagina, $nombre, $descripcion="", $flag_javascript=false) {
    global $num_menu;
    $funcion = "llamaCuerpo('$pagina');";
    if ($flag_javascript) $funcion=$pagina;
    $texto = "<tr>
                <td class=\"listado2\">
                    <a onclick=\"$funcion\" href='javascript:void(0);' target='mainFrame' class='vinculos' title='$descripcion'>".(++$num_menu).". $nombre</a>
                </td>
              </tr>";
    return $texto;
}
?>