<?php

/*
  SecureSession class
  Written by Vagharshak Tozalakyan <vagh@armdex.com>
  Released under GNU Public License
  Recomendacion: Cambiar al formato de clases estandar en php5 con OO
*/

class SecureSession
{
    // Include browser name in fingerprint?
    public $check_browser = true;

    // How many numbers from IP use in fingerprint?
    public $check_ip_blocks = 0;

    // Control word - any word you want.
    public $secure_word = 'SECURESTAFF';

    // Regenerate session ID to prevent fixation attacks?
    public $regenerate_id = true;

    // Call this when init session.
    public function Open()
    {
        $_SESSION['ss_fprint'] = $this->_Fingerprint();
        $this->_RegenerateId();
    }

    // Call this to check session.
    public function Check()
    {
        $this->_RegenerateId();
        return (isset($_SESSION['ss_fprint']) && $_SESSION['ss_fprint'] == $this->_Fingerprint());
    }

    // Internal function. Returns MD5 from fingerprint.
    private function _Fingerprint()
    {
        $fingerprint = $this->secure_word;
        if ($this->check_browser) {
            $fingerprint .= $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        if ($this->check_ip_blocks) {
            $num_blocks = abs(intval($this->check_ip_blocks));
            if ($num_blocks > 4) {
                $num_blocks = 4;
            }
            $blocks = explode('.', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            for ($i = 0; $i < $num_blocks; $i++) {
                $fingerprint .= ($blocks[$i] ?? '0') . '.';
            }
        }
        return md5($fingerprint);
    }

    // Internal function. Regenerates session ID if possible.
    private function _RegenerateId()
    {
        if ($this->regenerate_id && function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
            $this->_ActualizaSessionBD();
        }
    }
    /**
     * _ActualizaSessionBD Esta funcion actualiza la session en la tabla usuarios_session de quipux
     */
    function _ActualizaSessionBD(){
      //grabar la session regenerada en una tabla orfeo
      $ruta_raiz = ".";
       require_once ("$ruta_raiz/include/db/ConnectionHandler.php");
        $db = new ConnectionHandler("$ruta_raiz");
        $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);
        //Actualiza la session del usuario
        $nueva_session = "E'".session_id()."'";
        $query = "UPDATE usuarios_sesion set usua_sesion=".$nueva_session ." WHERE usua_codi ='".$_SESSION["usua_codi"]."'";
        $ejecuta_update = $db->query($query);

    }
}

?>

