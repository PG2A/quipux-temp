-- ==============================================================================
-- Quipux: Orgánico funcional — verificación del piloto FCM (solo lectura)
-- ==============================================================================
SET client_encoding TO 'UTF8';

\echo
\echo '== 1. Árbol de la Facultad de Ciencias Médicas =='
SELECT d.depe_codi
     , repeat('    ', (SELECT count(*)::int FROM regexp_matches(depe_ruta(d.depe_codi), ' - ', 'g'))) || d.depe_nomb AS arbol
     , d.dep_sigla
     , (SELECT count(*) FROM usuarios u WHERE u.depe_codi = d.depe_codi AND u.usua_esta = 1) AS usuarios
     , (SELECT count(*) FROM cargo c WHERE c.depe_codi = d.depe_codi) AS puestos
  FROM dependencia d
 WHERE d.depe_codi IN (SELECT depe_descendientes(112))
 ORDER BY depe_ruta(d.depe_codi);

\echo
\echo '== 2. Ningún nombre de la FCM conserva el prefijo plano (esperado: 0) =='
SELECT count(*) AS con_prefijo FROM dependencia WHERE depe_codi IN (SELECT depe_descendientes(112)) AND depe_codi <> 112 AND depe_nomb LIKE 'FACULTAD DE CIENCIAS M%';

\echo
\echo '== 3. usuario.depe_nomb recompuesto: las 3 cuentas de la cédula 0301557633 =='
SELECT usua_codi, depe_codi, depe_nomb, usua_cargo FROM usuario WHERE usua_cedula = '0301557633' ORDER BY usua_codi;

\echo
\echo '== 4. Coherencia usuario.depe_nomb = depe_ruta() en toda la FCM (esperado: 0 discrepancias) =='
SELECT count(*) AS discrepancias
  FROM usuario u
 WHERE u.depe_codi IN (SELECT depe_descendientes(112))
   AND u.depe_nomb IS DISTINCT FROM depe_ruta(u.depe_codi);

\echo
\echo '== 5. Usuarios movidos por el piloto =='
SELECT u.usua_codi, left(u.usua_nomb || ' ' || u.usua_apellido, 35) AS nombre, u.depe_codi, d.depe_nomb AS area_nueva, left(u.usua_cargo, 55) AS puesto
  FROM usuarios u JOIN dependencia d ON d.depe_codi = u.depe_codi
 WHERE u.usua_obs_actualiza LIKE 'Orgánico funcional FCM:%'
 ORDER BY u.depe_codi, u.usua_codi;

\echo
\echo '== 6. Usuarios FCM activos sin puesto en el catálogo (esperado: 0) =='
SELECT u.usua_codi, u.depe_codi, u.usua_cargo
  FROM usuarios u
 WHERE u.usua_esta = 1 AND u.depe_codi IN (SELECT depe_descendientes(112)) AND u.cargo_id IS NULL;

\echo
\echo '== 7. Puestos del catálogo que tienen más de un titular =='
SELECT c.cargo_id, c.depe_codi, c.cargo_nombre, c.cargo_tipo, count(u.usua_codi) AS titulares
  FROM cargo c JOIN usuarios u ON u.cargo_id = c.cargo_id AND u.usua_esta = 1
 GROUP BY 1,2,3,4 HAVING count(u.usua_codi) > 1
 ORDER BY titulares DESC, c.depe_codi;

\echo
\echo '== 8. Numeración de las áreas que recibieron usuarios =='
SELECT f.depe_codi, d.depe_nomb, f.fn_tiporad, f.fn_abr_texto, f.depe_numeracion, dn.dep_sigla AS sigla_que_numera, f.fn_contador
  FROM formato_numeracion f
  JOIN dependencia d  ON d.depe_codi = f.depe_codi
  JOIN dependencia dn ON dn.depe_codi = f.depe_numeracion
 WHERE f.depe_codi IN (635, 628, 177, 416, 402, 348, 349, 174, 179)
 ORDER BY f.depe_codi, f.fn_tiporad;

\echo
\echo '== 9. Jefes por área en la FCM (máximo 1 por área) =='
SELECT u.depe_codi, d.depe_nomb, count(*) AS jefes
  FROM usuarios u JOIN dependencia d ON d.depe_codi = u.depe_codi
 WHERE u.usua_esta = 1 AND u.cargo_tipo = 1 AND u.depe_codi IN (SELECT depe_descendientes(112))
 GROUP BY 1,2 HAVING count(*) > 1;

\echo
\echo '== 10. Errores registrados por los triggers durante la migración (esperado: 0) =='
SELECT count(*) AS errores_trigger FROM log_view_usuario WHERE fecha > now() - interval '1 hour';
