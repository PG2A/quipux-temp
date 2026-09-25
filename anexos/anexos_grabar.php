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

// Función que se ejecutará el finalizar la carga de archivos en la pantalla principal
$funciones_js = " window.parent.anexos_cargar_archivo_nuevo_finalizar(); ";

// Verificar si los archivos sobrepasaron el tamaño del $_POST
if (count($_FILES) == 0) {
    die ("ERROR <script>alert('No se pudo subir los archivos.\\nPor favor verifique el tamaño de los mismos.'); $funciones_js</script>");
}

session_start();
require_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/funciones.php');
include_once(dirname(__DIR__).'/include/tx/Historico.php');
include_once(dirname(__DIR__).'/include/tx/Firma_Digital.php');
include_once(dirname(__DIR__).'/seguridad/obtener_nivel_seguridad.php');

$db_bodega = new ConnectionHandler(dirname(__DIR__),"bodega");
$hist = new Historico($db);
$radi_nume = limpiar_numero($_POST["txt_radi_nume"]);

$nivel_seguridad_documento = obtener_nivel_seguridad_documento($db, $radi_nume);
if ($nivel_seguridad_documento<4 and !($_SESSION["usua_perm_digitalizar"]==1 and isset($_POST["asocImgRad"]))) {
    die ("ERROR <script>alert('Usted no tiene los permisos suficientes para anexar archivos a este documento.$nivel_seguridad_documento-".$_POST["asocImgRad"]."'); $funciones_js</script>");
}

$numero_archivos_cargados = 0;
for ($file=0 ; $file<10 ; ++$file ) {
    if (trim($_FILES["fil_archivo_nuevo_$file"]["tmp_name"].$_FILES["fil_archivo_nuevo_$file"]["name"])!="") {
        $descripcion = limpiar_sql($_POST["txt_descripcion_nuevo_$file"]);
        $medio_almacenamiento = 0 + $_POST["chk_medio_nuevo_$file"];
        $asociar_imagen = (isset ($_POST["chk_asociar_imagen_$file"])) ? (0 + $_POST["chk_asociar_imagen_$file"]) : 0;

        // Ruta temporal generada por PHP: NO se sanitiza, en Windows contiene "\" y limpiar_sql los elimina
        $archivo_path = $_FILES["fil_archivo_nuevo_$file"]["tmp_name"];
        $archivo_tamanio = 0+$_FILES["fil_archivo_nuevo_$file"]["size"];
        $archivo_nombre = trim(limpiar_sql($_FILES["fil_archivo_nuevo_$file"]["name"]));

        // Si falló la carga del archivo o es un archivo muy grande
        if ($archivo_tamanio == 0) {
            echo "<script>alert('No se pudo subir el archivo \"$archivo_nombre\".\\nPor favor verifique el tamaño del mismo.');</script>";
            continue;
        }

        //Obtenermos el nombre del archivo
        if (strpos($archivo_nombre, "/") !== false)
            $archivo_nombre = substr($archivo_nombre,(1+strrpos($archivo_nombre, "/")));
        if (strpos($archivo_nombre, "\\") !== false)
            $archivo_nombre = substr($archivo_nombre,(1+strrpos($archivo_nombre, "\\")));

        //Validamos la extensión del archivo
        $tmp = explode(".", $archivo_nombre);
        $flag_firma = false;
        $i = 1;
        $archivo_extension = "";
        do {
            $archivo_extension_tmp = strtoupper(trim($tmp[count($tmp)-$i]));
            $archivo_extension = ".".trim($tmp[count($tmp)-$i]) . $archivo_extension;
            if ($archivo_extension_tmp == "P7M") $flag_firma = true;
            ++$i;
        } while ($archivo_extension_tmp=="P7M");

        $rs = $db->query("select anex_tipo_codi from anexos_tipo where upper(anex_tipo_ext)='$archivo_extension_tmp'");
        if (!$rs or $rs->EOF) {
            echo "<script>alert('No esta permitido subir documentos con extensión \"$archivo_extension_tmp\".');</script>";
            continue;
        }
        $archivo_tipo = $rs->fields["ANEX_TIPO_CODI"];

        //Numero de anexo
        $rs = $db->query("select coalesce(max(anex_numero),0)+1 as num from anexos where anex_radi_nume=$radi_nume");
        $archivo_numero = $rs->fields["NUM"];
        $archivo_codigo = $radi_nume."_".str_pad(($rs->fields["NUM"]),5,"0",STR_PAD_LEFT);

        // Grabo el archivo en la bodega de disco duro (filesystem migration)
        $archivo_arch_codi = 0;
        $ano_carpeta = substr($radi_nume, 0, 4);
        $archivo_anex_path = "/anexos/$ano_carpeta/$radi_nume/";
        $dir_path = dirname(__DIR__) . "/bodega" . $archivo_anex_path;
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0777, true);
        }
        // Se limpian los caracteres no válidos para un nombre de archivo en disco (Windows/Linux).
        // El nombre original se conserva intacto en ANEX_NOMBRE.
        $archivo_nombre_disco = preg_replace('/[\\\\\/:*?"<>|]/', "_", $archivo_nombre);
        $nuevo_nombre_archivo = $archivo_codigo . "_" . $archivo_nombre_disco;
        if (!is_uploaded_file($archivo_path) or !copy($archivo_path, $dir_path . $nuevo_nombre_archivo)) {
            error_log("QUIPUX anexos: no se pudo copiar '$archivo_path' a '".$dir_path.$nuevo_nombre_archivo."'");
            echo "<script>alert('No se pudo copiar el archivo \"$archivo_nombre\" al repositorio de archivos del servidor.');</script>";
            continue;
        }
        $archivo_base64 = base64_encode(file_get_contents($archivo_path)); // Requerido para verificación de firma si aplica

        // Verifico si esta firmado electronicamente y guardo el archivo descifrado
        $archivo_arch_codi_firma = 0;
        $archivo_datos_firma = "";
        $archivo_fecha_firma = "null";
        if ($flag_firma) {
            $firma = verificar_firma_archivo($archivo_base64);
            if ($firma["flag"] == 1) {
                $archivo_nombre_sin_firma = str_ireplace(".p7m", "", $archivo_nombre_disco);
                $nuevo_nombre_archivo_sin = $archivo_codigo . "_" . $archivo_nombre_sin_firma;
                if (file_put_contents($dir_path . $nuevo_nombre_archivo_sin, base64_decode($firma["archivo"]))) {
                    $archivo_datos_firma = $firma["datos_firma"];
                    $archivo_fecha_firma = $db->conn->sysTimeStamp;
                    
                    // El archivo base servido al usuario regular será el des-firmado. El original está tmbn en disco.
                    $nuevo_nombre_archivo = $nuevo_nombre_archivo_sin;
                }
            } else {
                echo "<script>alert('".$firma["mensaje"]."');</script>";
            }
        }

        //Guardamos los datos en la tabla anexos
        $db->conn->BeginTrans();
        $recordSet["ANEX_RADI_NUME"] = $radi_nume;
        $recordSet["ANEX_CODIGO"] = $db->conn->qstr($archivo_codigo);
        $recordSet["ANEX_TIPO"] = $archivo_tipo;
        $recordSet["ANEX_TAMANO"] = round($archivo_tamanio/1024,2);
        $recordSet["ANEX_DESC"] = $db->conn->qstr(substr($descripcion,0,512));
        $recordSet["ANEX_NUMERO"] = $archivo_numero;
        $recordSet["ANEX_PATH"] = $db->conn->qstr($archivo_anex_path . $nuevo_nombre_archivo);
        $recordSet["ANEX_BORRADO"] = $db->conn->qstr("N");
        $recordSet["ANEX_FECHA"] = $db->conn->sysTimeStamp;
        $recordSet["ANEX_NOMBRE"] = $db->conn->qstr(substr($archivo_nombre,-100));
        $recordSet["ANEX_USUA_CODI"] = $_SESSION["usua_codi"];
        $recordSet["ANEX_FISICO"] = $medio_almacenamiento;
        $recordSet["ARCH_CODI"] = $archivo_arch_codi;
        $recordSet["ARCH_CODI_FIRMA"] = $archivo_arch_codi_firma;
        $recordSet["ANEX_DATOS_FIRMA"] = $db->conn->qstr($archivo_datos_firma);
        $recordSet["ANEX_FECHA_FIRMA"] = $archivo_fecha_firma;
        $ok1 = $db->conn->Replace("ANEXOS", $recordSet, "ANEX_CODIGO", false,false,true,false);//true al final para ver la cadena del insert
        if ($ok1==2) { //Si inserto correctamente
            $observacion = $archivo_nombre;
            $hist->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observacion, 66);
            $db->conn->CommitTrans();
        } else {
            $db->conn->RollbackTrans();
            echo "<script>alert('Existieron errores al grabar los datos del archivo.');</script>";
            continue;
        }

        if ($asociar_imagen==1) {
            $funciones_js .= " window.parent.fjs_anexos_acciones('$radi_nume','$archivo_codigo','2');";
        }
        ++$numero_archivos_cargados;
    } // end IF
} // end FOR


die ("OK <script>$funciones_js</script>");

?>