-- ============================================
-- SCRIPT DE LIMPIEZA TOTAL
-- VERSIÓN FINAL - Usando DELETE en lugar de TRUNCATE
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- PASO 1: BACKUP DE SEGURIDAD
-- ============================================

DROP TABLE IF EXISTS empresas_backup_clean;
CREATE TABLE empresas_backup_clean AS SELECT * FROM empresas;

-- ============================================
-- PASO 2: ELIMINAR DATOS (de hijos a padres)
-- ============================================

-- Tablas sin FK (TRUNCATE)
TRUNCATE TABLE comentarios_control;
TRUNCATE TABLE historial_estado;
TRUNCATE TABLE auditorias;
TRUNCATE TABLE riesgos;
TRUNCATE TABLE evidencias;

-- Tablas con FK (DELETE)
DELETE FROM acciones;
DELETE FROM gap_items;
DELETE FROM soa_entries;
DELETE FROM empresa_requerimientos;
DELETE FROM empresas;

-- Opcional: eliminar usuarios
-- DELETE FROM usuarios;

-- ============================================
-- PASO 3: REINICIAR AUTO_INCREMENT
-- ============================================

ALTER TABLE empresas AUTO_INCREMENT = 1;
ALTER TABLE evidencias AUTO_INCREMENT = 1;
ALTER TABLE acciones AUTO_INCREMENT = 1;
ALTER TABLE gap_items AUTO_INCREMENT = 1;
ALTER TABLE soa_entries AUTO_INCREMENT = 1;
ALTER TABLE empresa_requerimientos AUTO_INCREMENT = 1;
ALTER TABLE comentarios_control AUTO_INCREMENT = 1;
ALTER TABLE historial_estado AUTO_INCREMENT = 1;
ALTER TABLE auditorias AUTO_INCREMENT = 1;
ALTER TABLE riesgos AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- VERIFICACIÓN
-- ============================================

SELECT 'VERIFICACIÓN DE LIMPIEZA TOTAL' as resultado;

SELECT 
    'Empresas' as tabla,
    COUNT(*) as registros,
    'Debe ser 0' as esperado
FROM empresas

UNION ALL

SELECT 
    'Controles' as tabla,
    COUNT(*) as registros,
    'Debe ser 93' as esperado
FROM controles

UNION ALL

SELECT 
    'Dominios' as tabla,
    COUNT(*) as registros,
    'Debe ser 4' as esperado
FROM controles_dominio

UNION ALL

SELECT 
    'Requerimientos Base' as tabla,
    COUNT(*) as registros,
    'Debe ser 7' as esperado
FROM requerimientos_base
WHERE activo = 1

UNION ALL

SELECT 
    'Requerimientos-Controles' as tabla,
    COUNT(*) as registros,
    'Debe ser 126' as esperado
FROM requerimientos_controles

UNION ALL

SELECT 
    'Tipos Evidencia' as tabla,
    COUNT(*) as registros,
    'Debe ser 10+' as esperado
FROM tipos_evidencia
WHERE activo = 1

UNION ALL

SELECT 
    'SOA Entries' as tabla,
    COUNT(*) as registros,
    'Debe ser 0' as esperado
FROM soa_entries

UNION ALL

SELECT 
    'Evidencias' as tabla,
    COUNT(*) as registros,
    'Debe ser 0' as esperado
FROM evidencias

UNION ALL

SELECT 
    'GAPs' as tabla,
    COUNT(*) as registros,
    'Debe ser 0' as esperado
FROM gap_items

UNION ALL

SELECT 
    'Acciones' as tabla,
    COUNT(*) as registros,
    'Debe ser 0' as esperado
FROM acciones;

SELECT '✅ LIMPIEZA TOTAL COMPLETADA - Sistema en estado inicial' as mensaje;

-- ============================================
-- ROLLBACK (solo si necesario)
-- ============================================
/*
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM empresas;
INSERT INTO empresas SELECT * FROM empresas_backup_clean;
SET FOREIGN_KEY_CHECKS = 1;

-- Recrear datos automáticamente
CALL sp_inicializar_soa_empresa(1);
CALL sp_inicializar_requerimientos_empresa(1);

DROP TABLE empresas_backup_clean;
*/