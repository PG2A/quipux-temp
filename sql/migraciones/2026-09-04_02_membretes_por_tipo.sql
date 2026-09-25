-- RQ-T8 Hojas membretadas: asignacion por tipo de documento para todas las areas (depe_codi nulo).
-- Idempotente: se puede ejecutar mas de una vez.

alter table membrete_asignacion alter column depe_codi drop not null;

do $$ begin
    alter table membrete_asignacion add constraint ck_membrete_asignacion_ambito check (depe_codi is not null or trad_codigo is not null);
exception when duplicate_object then null;
end $$;

drop index if exists ux_membrete_asignacion;
create unique index if not exists ux_membrete_asignacion
    on membrete_asignacion (coalesce(depe_codi, 0), coalesce(trad_codigo, 0), masi_uso) where masi_estado = 1;

create or replace function validar_membrete_asignacion_institucion()
returns trigger language plpgsql as $$
declare
    institucion_membrete integer;
    institucion_area integer;
    tipo_valido integer;
begin
    select inst_codi into institucion_membrete from membrete where memb_codi = new.memb_codi;
    if institucion_membrete is null then
        raise exception 'El membrete no existe';
    end if;
    if new.depe_codi is not null then
        select inst_codi into institucion_area from dependencia where depe_codi = new.depe_codi;
        if institucion_area is null or institucion_membrete <> institucion_area then
            raise exception 'El membrete y el area deben pertenecer a la misma institucion';
        end if;
    end if;
    if new.trad_codigo is not null then
        select 1 into tipo_valido from tiporad where trad_codigo = new.trad_codigo and trad_inst_codi in (0, institucion_membrete);
        if tipo_valido is null then
            raise exception 'El tipo de documento no pertenece a la institucion del membrete';
        end if;
    end if;
    return new;
end;
$$;

drop trigger if exists trg_membrete_asignacion_institucion on membrete_asignacion;
create trigger trg_membrete_asignacion_institucion
before insert or update of memb_codi, depe_codi, trad_codigo on membrete_asignacion
for each row execute function validar_membrete_asignacion_institucion();

-- Reversion (requiere que no existan filas con depe_codi nulo):
-- delete from membrete_asignacion where depe_codi is null;
-- alter table membrete_asignacion drop constraint if exists ck_membrete_asignacion_ambito;
-- alter table membrete_asignacion alter column depe_codi set not null;
-- drop index if exists ux_membrete_asignacion;
-- create unique index ux_membrete_asignacion on membrete_asignacion (depe_codi, coalesce(trad_codigo, 0), masi_uso) where masi_estado = 1;
-- (la funcion del trigger vuelve a la version de 2026-09-03_01_membretes.sql)
