<?php
/**
 * Index.php - Router Principal
 * ISO 27001 Compliance Platform
 */

// Cargar configuración primero
require_once __DIR__ . '/../app/config/database.php';

// Autoload de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar helpers
require_once __DIR__ . '/../app/helpers/utils.php';

// Iniciar sesión
session_start();

// Capturar la ruta solicitada
$request = $_SERVER['REQUEST_URI'];
$request = str_replace('/iso_platform/public', '', $request);
$request = strtok($request, '?'); // Remover query strings

// Router básico - ORDEN IMPORTANTÍSIMO: específicas ANTES de genéricas
switch (true) {
    case ($request === '/' || $request === '' || $request === '/dashboard'):
        require_once '../app/views/dashboard.php';
        break;
    
    // ==================== API ENDPOINTS ====================
    
    case (strpos($request, '/api/dashboard/') === 0):
        $action = str_replace('/api/dashboard/', '', $request);
        $_GET['action'] = $action;
        require_once '../app/controllers/api/dashboardApi.php';
        break;
    
    // ==================== MÓDULOS ====================
    
    // Módulo: Controles
    case (preg_match('/^\/controles\/(\d+)\/actualizar$/', $request, $matches)):
        $control_id = $matches[1];
        require_once __DIR__ . '/../app/controllers/actualizarControl.php';
        break;
    
    case (preg_match('/^\/controles\/(\d+)$/', $request, $matches)):
        $control_id = $matches[1];
        require_once __DIR__ . '/../app/views/controles/detalle-control.php';
        break;
    
    case ($request === '/controles'):
        require_once __DIR__ . '/../app/views/controles/lista-controles.php';
        break;
    
    // Módulo: SOA
    case ($request === '/soa'):
        require_once __DIR__ . '/../app/views/soa/declaracion-aplicabilidad.php';
        break;
    
    // Módulo: GAP Analysis - RUTAS ESPECÍFICAS PRIMERO
    case ($request === '/gap/eliminar'):
        require_once __DIR__ . '/../app/controllers/eliminarGap.php';
        break;
    
    case ($request === '/gap/accion/actualizar-estado'):
        require_once __DIR__ . '/../app/controllers/cambiarEstadoAccion.php';
        break;
    
    case ($request === '/gap/accion/guardar'):
        require_once __DIR__ . '/../app/controllers/guardarAccion.php';
        break;
    
    case ($request === '/gap/actualizar'):
        require_once __DIR__ . '/../app/controllers/guardarGapActualizar.php';
        break;
    
    case (preg_match('/^\/gap\/(\d+)\/editar$/', $request, $matches)):
        $gap_id = $matches[1];
        require_once __DIR__ . '/../app/views/gap/editar-gap.php';
        break;
    
    case (preg_match('/^\/gap\/(\d+)$/', $request, $matches)):
        $gap_id = $matches[1];
        require_once __DIR__ . '/../app/views/gap/detalle-gap.php';
        break;
    
    case ($request === '/gap/guardar'):
        require_once __DIR__ . '/../app/controllers/guardarGap.php';
        break;
    
    case ($request === '/gap/crear'):
        require_once __DIR__ . '/../app/views/gap/crear-gap.php';
        break;
    
    case ($request === '/gap'):
        require_once __DIR__ . '/../app/views/gap/analisis-brechas.php';
        break;
    
    // Módulo: Evidencias
    case ($request === '/evidencias'):
        require_once '../app/views/evidencias/repositorio-evidencias.php';
        break;
    
    // Módulo: Requerimientos
    case ($request === '/requerimientos'):
        require_once '../app/views/requerimientos/checklist-requerimientos.php';
        break;
    
    // Módulo: Reportes
    case ($request === '/reportes'):
        require_once '../app/views/reportes/dashboard-reportes.php';
        break;
    
    // 404
    default:
        http_response_code(404);
        echo '<h1>404 - Página no encontrada</h1>';
        echo '<p>Ruta solicitada: ' . htmlspecialchars($request) . '</p>';
        break;
}