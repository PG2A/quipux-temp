select -- Busqueda Avanzada Tramites - USR 39082 - 2026-09-01 20:19:40<br>
        radi_nume_text as "No. Documento"
        ,substr(radi_fech_ofic::text,1,19) || '' as "SCR_Fecha Documento"
        ,'mostrar_documento("'||radi_nume_radi||'","'||radi_nume_text||'")' as "HID_RADI_NUME_RADI"
        ,radi_cuentai as "No. de Referencia"
        ,ver_usuarios(radi_usua_rem,',<br>') AS "De"
        ,ver_usuarios(radi_usua_dest,',<br>') AS "Para"
        ,radi_asunto  as "Asunto"
        ,resp1_radi_nume_text as "No. Respuesta"
        ,substr(resp1_radi_fech_ofic::text,1,19) || '' as "SCR_Fecha Respuesta"
        ,'mostrar_documento("'||resp1_radi_nume_radi||'","'||resp1_radi_nume_text||'")' as "HID_RESP1_RADI_NUME_RADI"
        ,ver_usuarios(resp1_radi_usua_rem,',<br>') AS "De"
        ,ver_usuarios(resp1_radi_usua_dest,',<br>') AS "Para"
        ,resp1_radi_asunto  as "Asunto Respuesta"
        ,case when resp1_esta_codi is not null then (case when resp1_esta_codi=6 then 'Manual' else (case when resp1_esta_codi in (0,2) then 'Electr&oacute;nico' else 'Pendiente' end) end) end as "Tipo Envío"
        ,resp2_radi_nume_text as "No. Respuesta"
        ,substr(resp2_radi_fech_ofic::text,1,19) || '' as "SCR_Fecha Respuesta"
        ,'mostrar_documento("'||resp2_radi_nume_radi||'","'||resp2_radi_nume_text||'")' as "HID_RESP2_RADI_NUME_RADI"
        ,ver_usuarios(resp2_radi_usua_rem,',<br>') AS "De"
        ,ver_usuarios(resp2_radi_usua_dest,',<br>') AS "Para"
        ,resp2_radi_asunto  as "Asunto Respuesta"
        from (
            select r.radi_nume_text, r.radi_fech_ofic, r.radi_nume_radi, r.radi_cuentai, r.radi_usua_rem, r.radi_usua_dest, r.radi_asunto
            , rr1.radi_nume_text as resp1_radi_nume_text
            , rr1.radi_fech_ofic as resp1_radi_fech_ofic
            , rr1.radi_nume_radi as resp1_radi_nume_radi
            , rr1.radi_usua_rem  as resp1_radi_usua_rem
            , rr1.radi_usua_dest as resp1_radi_usua_dest
            , rr1.radi_asunto as resp1_radi_asunto
            , '1' as tmp1
            , rr2.radi_nume_text as resp2_radi_nume_text
            , rr2.radi_fech_ofic as resp2_radi_fech_ofic
            , rr2.radi_nume_radi as resp2_radi_nume_radi
            , rr2.radi_usua_rem  as resp2_radi_usua_rem
            , rr2.radi_usua_dest as resp2_radi_usua_dest
            , rr2.radi_asunto as resp2_radi_asunto
            , rr1.esta_codi as resp1_esta_codi
            from (
                select r1.radi_nume_radi, r1.radi_nume_text, r1.radi_cuentai, r1.radi_fech_ofic, r1.radi_asunto
                    , r1.radi_usua_rem, r1.radi_usua_dest
                from radicado r1
                where r1.radi_inst_actu=3
                    and r1.radi_nume_radi::text like '%1' and esta_codi in (0,2)  and r1.radi_nume_radi in (select radi_nume_radi from hist_eventos where usua_codi_ori in (39082) or usua_codi_dest in (39082))
                    and r1.radi_fech_ofic::date >= '2026-08-02'::date
                    and r1.radi_fech_ofic::date <= '2026-09-01'::date
                    
                    
            ) as r
            left outer join hist_eventos h1 on r.radi_nume_radi=h1.radi_nume_radi and h1.sgd_ttr_codigo in (12)
            left outer join radicado rr1 on (rr1.radi_nume_temp=coalesce(h1.hist_referencia::numeric,0) and rr1.radi_nume_radi::text like '%1' and rr1.esta_codi in (0,2,4,5,6)) or (rr1.radi_nume_radi=coalesce(h1.hist_referencia::numeric,0) and rr1.esta_codi in (1))
            left outer join hist_eventos h2 on rr1.radi_nume_radi=h2.radi_nume_radi and h2.sgd_ttr_codigo in (12)
            left outer join radicado rr2 on (rr2.radi_nume_temp=coalesce(h2.hist_referencia::numeric,0) and rr2.radi_nume_radi::text like '%1' and rr2.esta_codi in (0,2,4,5,6)) or (rr2.radi_nume_radi=coalesce(h2.hist_referencia::numeric,0) and rr2.esta_codi in (1))
            order by 1 desc
        ) as a