<?php

function ext_hash_clave($clave)
{
    return substr(md5($clave), 1, 26);
}

function ext_cedula_valida($cedula)
{
    if (!preg_match('/^[0-9]{10}$/', $cedula)) {
        return false;
    }
    $provincia = (int)substr($cedula, 0, 2);
    if ($provincia < 1 || $provincia > 24) {
        return false;
    }
    if ((int)$cedula[2] > 5) {
        return false;
    }
    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $valor = (int)$cedula[$i];
        if ($i % 2 === 0) {
            $valor *= 2;
            if ($valor > 9) {
                $valor -= 9;
            }
        }
        $suma += $valor;
    }
    $verificador = (10 - ($suma % 10)) % 10;

    return $verificador === (int)$cedula[9];
}

function ext_pasaporte_valido($documento)
{
    return (bool)preg_match('/^[A-Za-z0-9\-]{6,20}$/', $documento);
}

function ext_identificacion_registrada($conn, $identificacion)
{
    $sql = "SELECT ciu_codigo FROM ciudadano WHERE TRIM(ciu_cedula) = ? AND COALESCE(ciu_estado,1) <> 0 LIMIT 1";
    $rs = $conn->Execute($sql, array($identificacion));
    if ($rs && !$rs->EOF) {
        return 'ciudadano';
    }
    $sql = "SELECT usua_codi FROM usuarios WHERE TRIM(usua_cedula) = ? OR UPPER(TRIM(usua_login)) = ? LIMIT 1";
    $rs = $conn->Execute($sql, array($identificacion, 'U' . strtoupper($identificacion)));
    if ($rs && !$rs->EOF) {
        return 'usuario';
    }

    return '';
}

function ext_correo_registrado($conn, $correo)
{
    $correo = strtolower($correo);
    $sql = "SELECT ciu_codigo FROM ciudadano WHERE LOWER(TRIM(ciu_email)) = ? AND COALESCE(ciu_estado,1) <> 0 LIMIT 1";
    $rs = $conn->Execute($sql, array($correo));
    if ($rs && !$rs->EOF) {
        return 'ciudadano';
    }
    $sql = "SELECT usua_codi FROM usuarios WHERE LOWER(TRIM(usua_email)) = ? LIMIT 1";
    $rs = $conn->Execute($sql, array($correo));
    if ($rs && !$rs->EOF) {
        return 'usuario';
    }

    return '';
}

function ext_autenticar($conn, $identificacion, $clave)
{
    $sql = "SELECT ciu_codigo, ciu_cedula, ciu_nombre, ciu_apellido, ciu_email, ciu_pasw, ciu_estado
            FROM ciudadano
            WHERE TRIM(ciu_cedula) = ?
              AND COALESCE(ciu_estado,1) = 1
              AND COALESCE(ciu_pasw,'') <> ''
            ORDER BY ciu_codigo DESC
            LIMIT 1";
    $rs = $conn->Execute($sql, array(trim($identificacion)));
    if (!$rs || $rs->EOF) {
        return null;
    }
    $fila = $rs->fields;
    $guardado = trim((string)($fila['CIU_PASW'] ?? $fila['ciu_pasw'] ?? ''));
    $md5 = md5($clave);
    $validos = array(substr($md5, 1, 26), substr($md5, 0, 26), $md5);
    if (!in_array($guardado, $validos, true)) {
        return null;
    }

    return array(
        'ciu_codigo'   => (int)($fila['CIU_CODIGO'] ?? $fila['ciu_codigo'] ?? 0),
        'ciu_cedula'   => trim((string)($fila['CIU_CEDULA'] ?? $fila['ciu_cedula'] ?? '')),
        'ciu_nombre'   => trim((string)($fila['CIU_NOMBRE'] ?? $fila['ciu_nombre'] ?? '')),
        'ciu_apellido' => trim((string)($fila['CIU_APELLIDO'] ?? $fila['ciu_apellido'] ?? '')),
        'ciu_email'    => trim((string)($fila['CIU_EMAIL'] ?? $fila['ciu_email'] ?? ''))
    );
}
