<?php

function portal_tiene_tabla_visibilidad($conn)
{
    static $existe = null;
    if ($existe === null) {
        $existe = (int)$conn->GetOne("SELECT count(*) FROM information_schema.tables WHERE table_schema='public' AND table_name='institucion_portal'") > 0;
    }

    return $existe;
}

function portal_institucion_visible($conn, $inst_codi)
{
    if (!portal_tiene_tabla_visibilidad($conn)) {
        return true;
    }
    $valor = $conn->GetOne("SELECT inst_mostrar FROM institucion_portal WHERE inst_codi = ?", array((int)$inst_codi));

    return $valor === false || $valor === null || (int)$valor === 1;
}

function portal_guardar_visibilidad($conn, $inst_codi, $mostrar, $usua_codi)
{
    if (!portal_tiene_tabla_visibilidad($conn)) {
        return false;
    }
    $inst_codi = (int)$inst_codi;
    $mostrar = $mostrar ? 1 : 0;
    $usua_codi = (int)$usua_codi;
    $existe = (int)$conn->GetOne("SELECT count(*) FROM institucion_portal WHERE inst_codi = ?", array($inst_codi));
    if ($existe) {
        return (bool)$conn->Execute(
            "UPDATE institucion_portal SET inst_mostrar = ?, usua_codi_modi = ?, fecha_modi = now() WHERE inst_codi = ?",
            array($mostrar, $usua_codi, $inst_codi)
        );
    }

    return (bool)$conn->Execute(
        "INSERT INTO institucion_portal (inst_codi, inst_mostrar, usua_codi_modi, fecha_modi) VALUES (?, ?, ?, now())",
        array($inst_codi, $mostrar, $usua_codi)
    );
}
