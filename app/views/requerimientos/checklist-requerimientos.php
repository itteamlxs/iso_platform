<?php
$page_title = 'Requerimientos Base ISO 27001';

// Cargar dependencias
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Requerimiento.php';
require_once __DIR__ . '/../../controllers/RequerimientosController.php';

$controller = new \App\Controllers\RequerimientosController();
$requerimientos = $controller->listar();
$stats = $controller->getEstadisticas();

// Capturar parámetro highlight desde URL
$highlight_id = $_GET['highlight'] ?? null;

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<main class="main-offset">
    <div class="content-wrapper">
        
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Requerimientos Base ISO 27001</h2>
            <p class="text-gray-600 mt-1">Checklist de documentos obligatorios para la certificación</p>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Total</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Completados</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $stats['completados']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">En Proceso</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['en_proceso']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Pendientes</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $stats['pendientes']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">No Aplica</p>
                <p class="text-2xl font-bold text-gray-600"><?php echo $stats['no_aplica']; ?></p>
            </div>
        </div>

        <!-- Barra de progreso general -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <?php 
            $aplicables = $stats['total'] - $stats['no_aplica'];
            $porcentaje = $aplicables > 0 ? round(($stats['completados'] / $aplicables) * 100, 1) : 0;
            ?>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progreso General</span>
                <span class="text-lg font-bold text-blue-600"><?php echo $porcentaje; ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-blue-600 h-4 rounded-full transition-all duration-500" style="width: <?php echo $porcentaje; ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <?php echo $stats['completados']; ?> de <?php echo $aplicables; ?> requerimientos aplicables completados
            </p>
        </div>

        <!-- Lista de requerimientos -->
        <div class="space-y-4">
            <?php if (count($requerimientos) > 0): ?>
                <?php foreach ($requerimientos as $req): 
                    $estado_color = [
                        'pendiente' => 'border-red-500 bg-red-50',
                        'en_proceso' => 'border-yellow-500 bg-yellow-50',
                        'completado' => 'border-green-500 bg-green-50',
                        'no_aplica' => 'border-gray-500 bg-gray-50'
                    ];
                    $badge_color = [
                        'pendiente' => 'bg-red-100 text-red-800',
                        'en_proceso' => 'bg-yellow-100 text-yellow-800',
                        'completado' => 'bg-green-100 text-green-800',
                        'no_aplica' => 'bg-gray-100 text-gray-800'
                    ];
                    $icon = [
                        'pendiente' => 'fa-circle text-red-600',
                        'en_proceso' => 'fa-clock text-yellow-600',
                        'completado' => 'fa-check-circle text-green-600',
                        'no_aplica' => 'fa-ban text-gray-600'
                    ];
                    
                    // CORRECCIÓN: Usar requerimiento_base_id correcto
                    $req_base_id = isset($req['requerimiento_base_id']) ? $req['requerimiento_base_id'] : $req['requerimiento_id'];
                    $controles = $controller->getControlesAsociados($req_base_id);
                    $evidencias = $controller->getEvidenciasDeControles($req_base_id);
                ?>
                
                <div id="req-<?php echo $req['id']; ?>" 
                     class="bg-white rounded-xl shadow-sm border-l-4 <?php echo $estado_color[$req['estado']]; ?> p-6 requerimiento-card transition-all"
                     data-req-id="req-<?php echo $req['id']; ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-3">
                                <i class="fas <?php echo $icon[$req['estado']]; ?> text-xl"></i>
                                <span class="text-xs px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-semibold">
                                    REQ-<?php echo str_pad($req['numero'], 2, '0', STR_PAD_LEFT); ?>
                                </span>
                                <span class="text-xs px-3 py-1 rounded-full font-semibold <?php echo $badge_color[$req['estado']]; ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $req['estado'])); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <?php echo htmlspecialchars($req['identificador']); ?>
                            </h3>
                            
                            <p class="text-sm text-gray-700 mb-3">
                                <?php echo htmlspecialchars($req['descripcion']); ?>
                            </p>
                            
                            <?php if ($req['objetivo']): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                <p class="text-xs font-semibold text-blue-800 mb-1">Objetivo:</p>
                                <p class="text-sm text-blue-900"><?php echo htmlspecialchars($req['objetivo']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Controles asociados -->
                            <div class="mt-4 border-t pt-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-semibold text-gray-700">
                                        <i class="fas fa-link mr-2"></i>Controles Asociados (<?php echo count($controles); ?>)
                                    </h4>
                                    <?php if (count($controles) > 0): ?>
                                    <button onclick="toggleControles('controles-<?php echo $req['id']; ?>')" 
                                            class="text-xs text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-chevron-down" id="icon-controles-<?php echo $req['id']; ?>"></i>
                                        Ver/Ocultar
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (count($controles) > 0): ?>
                                <div id="controles-<?php echo $req['id']; ?>" class="hidden">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-3">
                                        <?php foreach ($controles as $control): 
                                            $control_badge = [
                                                'implementado' => 'bg-green-100 text-green-800',
                                                'parcial' => 'bg-yellow-100 text-yellow-800',
                                                'no_implementado' => 'bg-red-100 text-red-800'
                                            ];
                                        ?>
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200">
                                            <div class="flex-1">
                                                <span class="text-xs font-medium text-gray-900"><?php echo $control['codigo']; ?></span>
                                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars(substr($control['nombre'], 0, 40)); ?>...</p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <?php if ($control['total_evidencias'] > 0): ?>
                                                    <span class="text-xs text-green-600" title="Evidencias aprobadas">
                                                        <i class="fas fa-paperclip"></i> <?php echo $control['total_evidencias']; ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="text-xs px-2 py-1 rounded <?php echo $control_badge[$control['estado']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo ucfirst($control['estado']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <?php if ($req['estado'] === 'completado'): ?>
                                    <form method="POST" action="<?php echo BASE_URL; ?>/public/requerimientos/aplicar-controles" style="display: inline;">
                                        <input type="hidden" name="requerimiento_base_id" value="<?php echo $req_base_id; ?>">
                                        <button type="submit" 
                                                onclick="return confirm('¿Marcar todos los controles asociados como IMPLEMENTADOS?')"
                                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
                                            <i class="fas fa-check-double mr-2"></i>Aplicar Requerimiento a Controles
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                    <p class="text-xs text-gray-500 italic">No hay controles asociados</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Evidencias asociadas -->
                            <?php if (count($evidencias) > 0): ?>
                            <div class="mt-4 border-t pt-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-semibold text-gray-700">
                                        <i class="fas fa-folder-open mr-2"></i>Evidencias Aprobadas (<?php echo count($evidencias); ?>)
                                    </h4>
                                    <button onclick="toggleControles('evidencias-<?php echo $req['id']; ?>')" 
                                            class="text-xs text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-chevron-down" id="icon-evidencias-<?php echo $req['id']; ?>"></i>
                                        Ver/Ocultar
                                    </button>
                                </div>
                                
                                <div id="evidencias-<?php echo $req['id']; ?>" class="hidden">
                                    <div class="space-y-2">
                                        <?php foreach (array_slice($evidencias, 0, 5) as $evidencia): ?>
                                        <div class="flex items-center justify-between p-2 bg-green-50 rounded border border-green-200">
                                            <div class="flex-1">
                                                <span class="text-xs font-medium text-gray-900"><?php echo $evidencia['codigo']; ?></span>
                                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars(substr($evidencia['descripcion'], 0, 50)); ?>...</p>
                                            </div>
                                            <a href="<?php echo BASE_URL; ?>/public/descargar-evidencia.php?file=<?php echo urlencode($evidencia['archivo']); ?>" 
                                               target="_blank"
                                               class="text-xs text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if (count($evidencias) > 5): ?>
                                        <p class="text-xs text-gray-500 text-center">... y <?php echo count($evidencias) - 5; ?> más</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Información adicional -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mt-4 pt-4 border-t">
                                <div>
                                    <p class="text-gray-600">Evidencia</p>
                                    <p class="font-medium text-gray-900">
                                        <?php echo $req['evidencia_documento'] ? htmlspecialchars($req['evidencia_documento']) : 'Sin evidencia'; ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Fecha de Entrega</p>
                                    <p class="font-medium text-gray-900">
                                        <?php echo $req['fecha_entrega'] ? date('d/m/Y', strtotime($req['fecha_entrega'])) : 'No definida'; ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Observaciones</p>
                                    <p class="font-medium text-gray-900">
                                        <?php echo $req['observaciones'] ? htmlspecialchars(substr($req['observaciones'], 0, 50)) . '...' : '-'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <button onclick="abrirModalEditar(<?php echo $req['id']; ?>)"
                                class="ml-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                            <i class="fas fa-edit mr-2"></i>Actualizar
                        </button>
                    </div>
                </div>
                
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <i class="fas fa-clipboard-list text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay requerimientos registrados</h3>
                    <p class="text-gray-600">Los requerimientos se crean automáticamente al registrar la empresa</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- Modal: Actualizar Requerimiento -->
<div id="modal-editar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Actualizar Requerimiento</h3>
        
        <form method="POST" action="<?php echo BASE_URL; ?>/public/requerimientos/actualizar" id="form-actualizar">
            <input type="hidden" name="requerimiento_id" id="modal-requerimiento-id" value="">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Estado <span class="text-red-600">*</span>
                </label>
                <select name="estado" required 
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="completado">Completado</option>
                    <option value="no_aplica">No Aplica</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Evidencia/Documento
                </label>
                <input type="text" name="evidencia_documento"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Nombre del documento o evidencia">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Fecha de Entrega
                </label>
                <input type="date" name="fecha_entrega"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Observaciones
                </label>
                <textarea name="observaciones" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                          placeholder="Comentarios u observaciones adicionales..."></textarea>
            </div>

            <div class="flex space-x-3">
                <button type="submit" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
                <button type="button" onclick="cerrarModalEditar()" 
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Estilo para highlight animado */
@keyframes highlightPulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    }
    50% {
        box-shadow: 0 0 0 15px rgba(59, 130, 246, 0);
    }
}

.requerimiento-highlight {
    animation: highlightPulse 1.5s ease-in-out 3;
    border-color: #3b82f6 !important;
    background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%) !important;
}
</style>

<script>
function abrirModalEditar(requerimientoId) {
    document.getElementById('modal-requerimiento-id').value = requerimientoId;
    document.getElementById('modal-editar').classList.remove('hidden');
}

function cerrarModalEditar() {
    document.getElementById('modal-editar').classList.add('hidden');
}

function toggleControles(id) {
    const element = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    element.classList.toggle('hidden');
    icon.classList.toggle('fa-chevron-down');
    icon.classList.toggle('fa-chevron-up');
}

// Sistema de scroll automático y highlight
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');
    
    if (highlightId) {
        // Buscar el elemento
        const targetElement = document.getElementById(highlightId);
        
        if (targetElement) {
            // Esperar a que se cargue completamente la página
            setTimeout(function() {
                // Scroll suave al elemento
                targetElement.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Agregar clase de highlight después del scroll
                setTimeout(function() {
                    targetElement.classList.add('requerimiento-highlight');
                    
                    // Remover highlight después de 5 segundos
                    setTimeout(function() {
                        targetElement.classList.remove('requerimiento-highlight');
                    }, 5000);
                }, 800);
            }, 300);
        }
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>