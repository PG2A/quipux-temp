-- Landing publico: permite elegir que instituciones aparecen en el menu "Ingresar a Quipux".
-- Se usa una tabla propia porque la tabla institucion pertenece al rol postgres
-- y el rol de la aplicacion no puede alterarla.
-- Idempotente: se puede ejecutar mas de una vez.

create table if not exists institucion_portal (
    inst_codi       integer primary key,
    inst_mostrar    smallint not null default 1,
    usua_codi_modi  integer,
    fecha_modi      timestamp default now()
);

comment on table institucion_portal is 'Visibilidad de cada institucion en el menu de ingreso del portal publico';
