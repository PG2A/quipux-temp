-- RQ-T8 Hojas membretadas: catalogo, asignacion por area y auditoria.
-- Idempotente: se puede ejecutar mas de una vez.
-- Ejecutar con el usuario duenio de la base quipux_ucuenca.

create table if not exists membrete (
    memb_codi        serial primary key,
    inst_codi        integer not null,
    memb_nombre      varchar(100) not null,
    memb_descripcion varchar(250),
    memb_archivo     varchar(200) not null,
    memb_defecto     smallint not null default 0,
    memb_estado      smallint not null default 1,
    usua_codi_crea   integer,
    memb_fecha_crea  timestamp default now(),
    usua_codi_modi   integer,
    memb_fecha_modi  timestamp
);

create unique index if not exists ux_membrete_defecto
    on membrete (inst_codi) where memb_defecto = 1 and memb_estado = 1;

create table if not exists membrete_asignacion (
    masi_codi        serial primary key,
    memb_codi        integer not null references membrete (memb_codi),
    depe_codi        integer not null,
    trad_codigo      numeric,
    masi_uso         varchar(15) not null default 'documento',
    masi_estado      smallint not null default 1,
    usua_codi_crea   integer,
    masi_fecha_crea  timestamp default now()
);

create unique index if not exists ux_membrete_asignacion
    on membrete_asignacion (depe_codi, coalesce(trad_codigo, 0), masi_uso) where masi_estado = 1;

create index if not exists ix_membrete_asignacion_memb on membrete_asignacion (memb_codi);
create index if not exists ix_membrete_institucion on membrete (inst_codi, memb_estado);
create index if not exists ix_membrete_asignacion_area on membrete_asignacion (depe_codi, masi_estado);

do $$ begin
    alter table membrete add constraint ck_membrete_estado check (memb_estado in (0, 1));
exception when duplicate_object then null;
end $$;

do $$ begin
    alter table membrete add constraint ck_membrete_defecto check (memb_defecto in (0, 1));
exception when duplicate_object then null;
end $$;

do $$ begin
    alter table membrete_asignacion add constraint ck_membrete_asignacion_estado check (masi_estado in (0, 1));
exception when duplicate_object then null;
end $$;

create or replace function validar_membrete_asignacion_institucion()
returns trigger language plpgsql as $$
declare
    institucion_membrete integer;
    institucion_area integer;
begin
    select inst_codi into institucion_membrete from membrete where memb_codi = new.memb_codi;
    select inst_codi into institucion_area from dependencia where depe_codi = new.depe_codi;
    if institucion_membrete is null or institucion_area is null or institucion_membrete <> institucion_area then
        raise exception 'El membrete y el area deben pertenecer a la misma institucion';
    end if;
    return new;
end;
$$;

drop trigger if exists trg_membrete_asignacion_institucion on membrete_asignacion;
create trigger trg_membrete_asignacion_institucion
before insert or update of memb_codi, depe_codi on membrete_asignacion
for each row execute function validar_membrete_asignacion_institucion();

create table if not exists log_membrete (
    log_codi     serial primary key,
    memb_codi    integer,
    usua_codi    integer,
    log_accion   varchar(15) not null,
    log_fecha    timestamp default now(),
    log_detalle  varchar(500)
);

-- Reversion:
-- drop trigger if exists trg_membrete_asignacion_institucion on membrete_asignacion;
-- drop function if exists validar_membrete_asignacion_institucion();
-- drop table if exists log_membrete;
-- drop table if exists membrete_asignacion;
-- drop table if exists membrete;
