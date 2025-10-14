<?php
$page_title = 'Editar GAP';

// Cargar dependencias
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Gap.php';
require_once __DIR__ . '/../../controllers/GapController.php';

// Obtener ID del GAP
$gap_id = isset($gap_id) ? $gap_id : null;

if (!$gap_id) {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

$controller = new \App\Controllers\GapController();
$gap = $controller->detalle($gap_id);
$acciones = $controller->getAcciones($gap_id);

if (!$gap) {
    header('Location: ' . BASE_URL . '/public/gap');
    exit;
}

// Calcular avance automático
$total_acciones = count($acciones);
$acciones_completadas = count(array_filter($acciones, fn($a) => $a['estado'] === 'completada'));
$avance_automatico = $total_acciones > 0 ? round(($acciones_completadas / $total_acciones) * 100) : 0;

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<main class="main-offset">
    <div class="content-wrapper">
        
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-600">
                <li><a href="<?php echo BASE_URL; ?>/public/gap" class="hover:text-blue-600">GAP Analysis</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li><a href="<?php echo BASE_URL; ?>/public/gap/<?php echo $gap['id']; ?>" class="hover:text-blue-600">GAP #<?php echo $gap['id']; ?></a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-gray-900 font-medium">Editar</li>
            </ol>
        </nav>

        <!-- Formulario de edición -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 max-w-4xl">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Editar GAP #<?php echo $gap['id']; ?></h2>
            
            <form method="POST" action="<?php echo BASE_URL; ?>/public/gap/actualizar" id="form-editar-gap">
                
                <input type="hidden" name="gap_id" value="<?php echo $gap['id']; ?>">
                <input type="hidden" name="avance_modificado" id="avance_modificado" value="0">
                
                <!-- Info del control (solo lectura) -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-600">Control</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo $gap['codigo']; ?> - <?php echo htmlspecialchars($gap['control']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Dominio</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($gap['dominio']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Acciones</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo $acciones_completadas; ?>/<?php echo $total_acciones; ?> completadas</p>
                        </div>
                    </div>
                </div>

                <!-- Descripción de la brecha -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción de la Brecha <span class="text-red-600">*</span>
                    </label>
                    <textarea name="brecha" rows="4" required
                              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                              placeholder="Describa la brecha..."><?php echo htmlspecialchars($gap['brecha']); ?></textarea>
                </div>

                <!-- Objetivo -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Objetivo de Mejora
                    </label>
                    <textarea name="objetivo" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                              placeholder="¿Qué se espera lograr?"><?php echo htmlspecialchars($gap['objetivo'] ?? ''); ?></textarea>
                </div>

                <!-- Grid: Prioridad, Responsable, Avance -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    
                    <!-- Prioridad -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Prioridad <span class="text-red-600">*</span>
                        </label>
                        <select name="prioridad" required
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500">
                            <option value="alta" <?php echo $gap['prioridad'] == 'alta' ? 'selected' : ''; ?>>Alta</option>
                            <option value="media" <?php echo $gap['prioridad'] == 'media' ? 'selected' : ''; ?>>Media</option>
                            <option value="baja" <?php echo $gap['prioridad'] == 'baja' ? 'selected' : ''; ?>>Baja</option>
                        </select>
                    </div>

                    <!-- Responsable -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Responsable
                        </label>
                        <input type="text" name="responsable"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                               placeholder="Nombre del responsable"
                               value="<?php echo htmlspecialchars($gap['responsable'] ?? ''); ?>">
                    </div>

                    <!-- Avance de implementación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Avance Manual (%) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" name="avance" id="campo-avance" min="0" max="100" required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                               value="<?php echo $gap['avance']; ?>"
                               data-avance-inicial="<?php echo $gap['avance']; ?>">
                        <p class="text-xs text-gray-500 mt-1">Automático: <?php echo $avance_automatico; ?>% (<?php echo $acciones_completadas; ?>/<?php echo $total_acciones; ?>)</p>
                    </div>

                </div>

                <!-- Fechas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Fecha estimada -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha Estimada de Cierre
                        </label>
                        <input type="date" name="fecha_estimada_cierre"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                               value="<?php echo $gap['fecha_estimada_cierre'] ? date('Y-m-d', strtotime($gap['fecha_estimada_cierre'])) : ''; ?>">
                    </div>

                    <!-- Fecha real de cierre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha Real de Cierre
                        </label>
                        <input type="date" name="fecha_real_cierre"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                               value="<?php echo $gap['fecha_real_cierre'] ? date('Y-m-d', strtotime($gap['fecha_real_cierre'])) : ''; ?>">
                        <p class="text-xs text-gray-500 mt-1">Se completa automáticamente al llegar a 100%</p>
                    </div>

                </div>

                <!-- Botones -->
                <div class="flex space-x-3 pt-6 border-t">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                    <a href="<?php echo BASE_URL; ?>/public/gap/<?php echo $gap['id']; ?>" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                </div>

            </form>
        </div>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campoAvance = document.getElementById('campo-avance');
    const flagModificado = document.getElementById('avance_modificado');
    const avanceInicial = campoAvance.getAttribute('data-avance-inicial');
    
    campoAvance.addEventListener('change', function() {
        if (this.value !== avanceInicial) {
            flagModificado.value = '1';
        } else {
            flagModificado.value = '0';
        }
    });
    
    document.getElementById('form-editar-gap').addEventListener('submit', function(e) {
        if (campoAvance.value !== avanceInicial) {
            flagModificado.value = '1';
        } else {
            flagModificado.value = '0';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>