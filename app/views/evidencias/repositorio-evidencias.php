<?php
$page_title = 'Repositorio de Evidencias';

// Cargar dependencias
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Evidencia.php';
require_once __DIR__ . '/../../models/Control.php';
require_once __DIR__ . '/../../controllers/EvidenciasController.php';
require_once __DIR__ . '/../../controllers/ControlesController.php';

// Obtener filtros
$filtros = [
    'control_id' => $_GET['control_id'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'tipo' => $_GET['tipo'] ?? ''
];

$controller = new \App\Controllers\EvidenciasController();
$evidencias = $controller->listar($filtros);
$tipos = $controller->getTipos();
$stats = $controller->getEstadisticas();

// Obtener controles para filtro (solo aplicables)
$controlesController = new \App\Controllers\ControlesController();
$filtros_controles = ['aplicable' => 1];
$controles = $controlesController->listar($filtros_controles);

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<main class="main-offset">
    <div class="content-wrapper">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Repositorio de Evidencias</h2>
                <p class="text-gray-600 mt-1">Gestión de documentos y archivos de cumplimiento</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/public/evidencias/subir" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition">
                <i class="fas fa-upload"></i>
                <span>Subir Evidencia</span>
            </a>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Total</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Pendientes</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['pendientes']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Aprobadas</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $stats['aprobadas']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Rechazadas</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $stats['rechazadas']; ?></p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Control</label>
                    <select name="control_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <?php foreach ($controles as $control): ?>
                        <option value="<?php echo $control['id']; ?>" <?php echo $filtros['control_id'] == $control['id'] ? 'selected' : ''; ?>>
                            <?php echo $control['codigo']; ?> - <?php echo htmlspecialchars(substr($control['nombre'], 0, 30)); ?>...
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="estado" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="pendiente" <?php echo $filtros['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="aprobada" <?php echo $filtros['estado'] == 'aprobada' ? 'selected' : ''; ?>>Aprobada</option>
                        <option value="rechazada" <?php echo $filtros['estado'] == 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                    <select name="tipo" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $tipo): ?>
                        <option value="<?php echo $tipo['id']; ?>" <?php echo $filtros['tipo'] == $tipo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-filter mr-2"></i>Filtrar
                    </button>
                    <a href="<?php echo BASE_URL; ?>/public/evidencias" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabla de evidencias -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Control</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archivo</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($evidencias) > 0): ?>
                            <?php foreach ($evidencias as $evidencia): 
                                $estado_color = [
                                    'pendiente' => 'bg-yellow-100 text-yellow-800',
                                    'aprobada' => 'bg-green-100 text-green-800',
                                    'rechazada' => 'bg-red-100 text-red-800'
                                ];
                                $extension = pathinfo($evidencia['archivo'], PATHINFO_EXTENSION);
                                $icono = [
                                    'pdf' => 'fa-file-pdf text-red-600',
                                    'doc' => 'fa-file-word text-blue-600',
                                    'docx' => 'fa-file-word text-blue-600',
                                    'xls' => 'fa-file-excel text-green-600',
                                    'xlsx' => 'fa-file-excel text-green-600',
                                    'jpg' => 'fa-file-image text-purple-600',
                                    'jpeg' => 'fa-file-image text-purple-600',
                                    'png' => 'fa-file-image text-purple-600'
                                ];
                                $icon_class = $icono[$extension] ?? 'fa-file text-gray-600';
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-gray-900"><?php echo $evidencia['codigo']; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($evidencia['descripcion']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($evidencia['control']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">
                                        <?php echo htmlspecialchars($evidencia['tipo_evidencia'] ?? 'Sin tipo'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?php echo BASE_URL; ?>/public/descargar-evidencia.php?file=<?php echo urlencode($evidencia['archivo']); ?>" 
                                       target="_blank"
                                       class="flex items-center space-x-2 text-blue-600 hover:text-blue-800">
                                        <i class="fas <?php echo $icon_class; ?>"></i>
                                        <span class="text-sm"><?php echo htmlspecialchars(basename($evidencia['archivo'])); ?></span>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full <?php echo $estado_color[$evidencia['estado_validacion']]; ?>">
                                        <?php echo ucfirst($evidencia['estado_validacion']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm text-gray-600"><?php echo date('d/m/Y', strtotime($evidencia['fecha_subida'])); ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="abrirModalValidar(<?php echo $evidencia['id']; ?>)"
                                                class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                        <button onclick="confirmarEliminar(<?php echo $evidencia['id']; ?>)"
                                                class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-folder-open text-4xl mb-3"></i>
                                    <p>No hay evidencias registradas</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Modal: Validar Evidencia -->
<div id="modal-validar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-8 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Validar Evidencia</h3>
        
        <form method="POST" action="<?php echo BASE_URL; ?>/public/evidencias/validar">
            <?php echo \App\Helpers\Security::csrfField(); ?>
            <input type="hidden" name="evidencia_id" id="modal-evidencia-id" value="">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select name="estado" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="aprobada">Aprobar</option>
                    <option value="rechazada">Rechazar</option>
                    <option value="pendiente">Pendiente</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Comentarios</label>
                <textarea name="comentarios" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                          placeholder="Comentarios de validación..."></textarea>
            </div>

            <div class="flex space-x-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-check mr-2"></i>Guardar
                </button>
                <button type="button" onclick="cerrarModalValidar()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalValidar(evidenciaId) {
    document.getElementById('modal-evidencia-id').value = evidenciaId;
    document.getElementById('modal-validar').classList.remove('hidden');
}

function cerrarModalValidar() {
    document.getElementById('modal-validar').classList.add('hidden');
}

function confirmarEliminar(evidenciaId) {
    if (confirm('¿Está seguro de eliminar esta evidencia? Esta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/public/evidencias/eliminar';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'evidencia_id';
        inputId.value = evidenciaId;
        
        const inputCsrf = document.createElement('input');
        inputCsrf.type = 'hidden';
        inputCsrf.name = '<?php echo CSRF_TOKEN_NAME; ?>';
        inputCsrf.value = '<?php echo \App\Helpers\Security::generateCSRFToken(); ?>';
        
        form.appendChild(inputId);
        form.appendChild(inputCsrf);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>