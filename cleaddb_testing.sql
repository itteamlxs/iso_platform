-- ============================================
-- SCRIPT DE LIMPIEZA: Mantener Empresa
-- VERSIÓN FINAL - Usando DELETE en lugar de TRUNCATE
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- PASO 1: ELIMINAR DATOS (de hijos a padres)
-- ============================================

-- Tablas sin FK (TRUNCATE es más rápido)
TRUNCATE TABLE comentarios_control;
TRUNCATE TABLE historial_estado;
TRUNCATE TABLE auditorias;
TRUNCATE TABLE riesgos;
TRUNCATE TABLE evidencias;

-- Tablas con FK (usar DELETE)
DELETE FROM acciones;
DELETE FROM gap_items;
DELETE FROM soa_entries WHERE empresa_id = 1;
DELETE FROM empresa_requerimientos WHERE empresa_id = 1;

-- ============================================
-- PASO 2: REINICIAR AUTO_INCREMENT
-- ============================================

ALTER TABLE evidencias AUTO_INCREMENT = 1;
ALTER TABLE acciones AUTO_INCREMENT = 1;
ALTER TABLE gap_items AUTO_INCREMENT = 1;
ALTER TABLE soa_entries AUTO_INCREMENT = 1;
ALTER TABLE empresa_requerimientos AUTO_INCREMENT = 1;
ALTER TABLE comentarios_control AUTO_INCREMENT = 1;
ALTER TABLE historial_estado AUTO_INCREMENT = 1;
ALTER TABLE auditorias AUTO_INCREMENT = 1;
ALTER TABLE riesgos AUTO_INCREMENT = 1;

-- ============================================
-- PASO 3: RECREAR SOA_ENTRIES PARA EMPRESA 1
-- ============================================

INSERT INTO soa_entries (empresa_id, control_id, aplicable, estado, fecha_evaluacion, evaluador)
SELECT 
    1 as empresa_id,
    id as control_id,
    1 as aplicable,
    'no_implementado' as estado,
    NOW() as fecha_evaluacion,
    'Sistema' as evaluador
FROM controles
ORDER BY codigo;

-- ============================================
-- PASO 4: RECREAR REQUERIMIENTOS PARA EMPRESA 1
-- ============================================

INSERT INTO empresa_requerimientos (empresa_id, requerimiento_id, estado)
SELECT 
    1 as empresa_id,
    id as requerimiento_id,
    'pendiente' as estado
FROM requerimientos_base
WHERE activo = 1
ORDER BY numero;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- VERIFICACIÓN
-- ============================================

SELECT 'VERIFICACIÓN DE LIMPIEZA' as resultado;

SELECT 
    'Empresa' as tabla,
    COUNT(*) as registros,
    'Debe ser 1' as esperado
FROM empresas
WHERE id = 1

UNION ALL

SELECT 
    'Controles' as tabla,
    COUNT(*) as registros,
    'Debe ser 93' as esperado
FROM controles

UNION ALL

SELECT 
    'SOA Entries' as tabla,
    COUNT(*) as registros,
    'Debe ser 93' as esperado
FROM soa_entries
WHERE empresa_id = 1

UNION ALL

SELECT 
    'Requerimientos Base' as tabla,
    COUNT(*) as registros,
    'Debe ser 7' as esperado
FROM requerimientos_base
WHERE activo = 1

UNION ALL

SELECT 
    'Empresa Requerimientos' as tabla,
    COUNT(*) as registros,
    'Debe ser 7' as esperado
FROM empresa_requerimientos
WHERE empresa_id = 1

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

SELECT '✅ LIMPIEZA COMPLETADA - Empresa 1 lista para testing' as mensaje;