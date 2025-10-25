<?php
$page_title = 'Detalle de Control';

// Cargar dependencias
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Control.php';
require_once __DIR__ . '/../../models/Evidencia.php';
require_once __DIR__ . '/../../controllers/ControlesController.php';
require_once __DIR__ . '/../../controllers/EvidenciasController.php';
require_once __DIR__ . '/../../helpers/Security.php';

use App\Helpers\Security;

// Obtener ID del control desde la URL
$control_id = isset($control_id) ? $control_id : null;

if (!$control_id) {
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

$controller = new \App\Controllers\ControlesController();
$control = $controller->detalle($control_id);

if (!$control) {
    header('Location: ' . BASE_URL . '/public/controles');
    exit;
}

// Obtener evidencias del control
$evidenciasController = new \App\Controllers\EvidenciasController();
$evidencias = $evidenciasController->listar(['control_id' => $control_id]);
$total_evidencias = count($evidencias);

// Obtener requerimientos asociados
$requerimientos = $controller->getRequerimientosAsociados($control_id);

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<main class="main-offset">
    <div class="content-wrapper">
        
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-600">
                <li><a href="<?php echo BASE_URL; ?>/public/controles" class="hover:text-blue-600">Controles</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-gray-900 font-medium"><?php echo Security::sanitizeOutput($control['codigo']); ?></li>
            </ol>
        </nav>

        <!-- Header del control -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="text-xs px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-semibold">
                            <?php echo Security::sanitizeOutput($control['codigo']); ?>
                        </span>
                        <span class="text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded-full">
                            <?php echo Security::sanitizeOutput($control['dominio']); ?>
                        </span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo Security::sanitizeOutput($control['nombre']); ?></h2>
                    <p class="text-gray-600 mt-2"><?php echo Security::sanitizeOutput($control['descripcion']); ?></p>
                </div>
            </div>
            
            <!-- Requerimientos que respaldan este control -->
            <?php if (count($requerimientos) > 0): ?>
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700">
                        <i class="fas fa-link mr-2 text-blue-600"></i>
                        Respalda a Requerimientos Base (<?php echo Security::sanitizeOutput(count($requerimientos)); ?>)
                    </h3>
                    <button onclick="toggleSection('requerimientos-section')" 
                            class="text-xs text-blue-600 hover:text-blue-800">
                        <i class="fas fa-chevron-down" id="icon-requerimientos-section"></i>
                        Ver/Ocultar
                    </button>
                </div>
                
                <div id="requerimientos-section" class="hidden">
                    <div class="grid grid-cols-1 gap-2 mb-3">
                        <?php foreach ($requerimientos as $req): ?>
                        <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-xs px-2 py-1 bg-blue-600 text-white rounded font-semibold">
                                        REQ-<?php echo Security::sanitizeOutput(str_pad($req['numero'], 2, '0', STR_PAD_LEFT)); ?>
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">
                                        <?php echo Security::sanitizeOutput($req['identificador']); ?>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 ml-14">
                                    <?php echo Security::sanitizeOutput(substr($req['descripcion'], 0, 100)); ?>...
                                </p>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/public/requerimientos?highlight=req-<?php echo Security::sanitizeOutput($req['id']); ?>" 
                               class="ml-4 text-blue-600 hover:text-blue-800 text-sm whitespace-nowrap">
                                <i class="fas fa-arrow-right mr-1"></i>Ver Requerimiento
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-start space-x-2">
                            <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                            <div class="text-xs text-blue-800">
                                <p class="font-semibold mb-1">Relación bidireccional:</p>
                                <p>Este control respalda los requerimientos listados arriba. Al implementar este control y subir evidencias aprobadas, contribuye automáticamente a completar esos requerimientos. Los requerimientos se marcarán como "Completado" cuando <strong>todos</strong> sus controles asociados estén implementados con evidencias aprobadas.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Columna izquierda: Formulario de evaluación -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Evaluación del Control</h3>
                    
                    <form id="form-evaluacion" method="POST" action="<?php echo BASE_URL; ?>/public/controles/<?php echo Security::sanitizeOutput($control['id']); ?>/actualizar">
                        <?php echo Security::csrfField(); ?>
                        <input type="hidden" name="soa_id" value="<?php echo Security::sanitizeOutput($control['soa_id']); ?>">
                        <input type="hidden" name="control_id" value="<?php echo Security::sanitizeOutput($control['id']); ?>">
                        
                        <!-- Aplicabilidad -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">¿Este control es aplicable a su organización?</label>
                            <div class="flex space-x-4">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="aplicable" value="1" 
                                           <?php echo $control['aplicable'] ? 'checked' : ''; ?>
                                           class="w-4 h-4 text-blue-600" required>
                                    <span class="text-gray-700">Sí, es aplicable</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="aplicable" value="0" 
                                           <?php echo !$control['aplicable'] ? 'checked' : ''; ?>
                                           class="w-4 h-4 text-blue-600">
                                    <span class="text-gray-700">No aplicable</span>
                                </label>
                            </div>
                        </div>

                        <!-- Estado de implementación -->
                        <div id="div-estado" class="mb-6" <?php echo !$control['aplicable'] ? 'style="display:none;"' : ''; ?>>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Estado de Implementación</label>
                            <div class="space-y-2">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="estado" value="implementado" 
                                           <?php echo $control['estado'] == 'implementado' ? 'checked' : ''; ?>
                                           class="w-4 h-4 text-blue-600">
                                    <div class="flex-1">
                                        <span class="text-gray-700 font-medium">Implementado</span>
                                        <p class="text-xs text-gray-500">El control está completamente implementado y operativo</p>
                                    </div>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="estado" value="parcial" 
                                           <?php echo $control['estado'] == 'parcial' ? 'checked' : ''; ?>
                                           class="w-4 h-4 text-blue-600">
                                    <div class="flex-1">
                                        <span class="text-gray-700 font-medium">Parcialmente Implementado</span>
                                        <p class="text-xs text-gray-500">El control está en proceso o implementado parcialmente</p>
                                    </div>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input type="radio" name="estado" value="no_implementado" 
                                           <?php echo $control['estado'] == 'no_implementado' ? 'checked' : ''; ?>
                                           class="w-4 h-4 text-blue-600">
                                    <div class="flex-1">
                                        <span class="text-gray-700 font-medium">No Implementado</span>
                                        <p class="text-xs text-gray-500">El control aún no ha sido implementado</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Justificación -->
                        <div id="div-justificacion" class="mb-6" <?php echo $control['aplicable'] ? 'style="display:none;"' : ''; ?>>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Justificación de No Aplicabilidad</label>
                            <textarea name="justificacion" rows="4" 
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                                      placeholder="Explique por qué este control no aplica a su organización..."><?php echo Security::sanitizeOutput($control['justificacion'] ?? ''); ?></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="flex space-x-3">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                                <i class="fas fa-save mr-2"></i>Guardar Evaluación
                            </button>
                            <a href="<?php echo BASE_URL; ?>/public/controles" 
                               class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg transition">
                                <i class="fas fa-arrow-left mr-2"></i>Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Columna derecha: Info adicional -->
            <div class="space-y-6">
                
                <!-- Estado actual -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Estado Actual</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-600">Aplicabilidad</p>
                            <?php if ($control['aplicable']): ?>
                                <span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>Aplicable</span>
                            <?php else: ?>
                                <span class="text-gray-600 font-medium"><i class="fas fa-times-circle mr-1"></i>No Aplicable</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Estado</p>
                            <?php
                            $badges = [
                                'implementado' => '<span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded-full">Implementado</span>',
                                'parcial' => '<span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">Parcial</span>',
                                'no_implementado' => '<span class="text-xs px-2 py-1 bg-red-100 text-red-800 rounded-full">No Implementado</span>'
                            ];
                            echo $badges[$control['estado']] ?? '-';
                            ?>
                        </div>
                        <?php if ($control['fecha_evaluacion']): ?>
                        <div>
                            <p class="text-xs text-gray-600">Última evaluación</p>
                            <p class="text-sm text-gray-800"><?php echo Security::sanitizeOutput(date('d/m/Y', strtotime($control['fecha_evaluacion']))); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Evidencias -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Evidencias</h3>
                    <p class="text-xs text-gray-600 mb-3"><?php echo Security::sanitizeOutput($total_evidencias); ?> archivo<?php echo $total_evidencias != 1 ? 's' : ''; ?> adjunto<?php echo $total_evidencias != 1 ? 's' : ''; ?></p>
                    <a href="<?php echo BASE_URL; ?>/public/evidencias/subir?control_id=<?php echo Security::sanitizeOutput($control['id']); ?>"
                       class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition text-center">
                        <i class="fas fa-upload mr-2"></i>Subir Evidencia
                    </a>
                </div>

            </div>

        </div>

    </div>
</main>

<script>
// Mostrar/ocultar campos según aplicabilidad
document.querySelectorAll('input[name="aplicable"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const esAplicable = this.value === '1';
        document.getElementById('div-estado').style.display = esAplicable ? 'block' : 'none';
        document.getElementById('div-justificacion').style.display = esAplicable ? 'none' : 'block';
        
        document.querySelectorAll('input[name="estado"]').forEach(r => {
            r.required = esAplicable;
        });
    });
});

// Toggle section
function toggleSection(id) {
    const element = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    element.classList.toggle('hidden');
    icon.classList.toggle('fa-chevron-down');
    icon.classList.toggle('fa-chevron-up');
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>