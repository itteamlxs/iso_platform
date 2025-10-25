<?php
$page_title = 'Subir Evidencia';

// Cargar dependencias
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Control.php';
require_once __DIR__ . '/../../models/Evidencia.php';
require_once __DIR__ . '/../../controllers/ControlesController.php';
require_once __DIR__ . '/../../controllers/EvidenciasController.php';

// Obtener controles (solo aplicables) y tipos
$controlesController = new \App\Controllers\ControlesController();
$filtros = ['aplicable' => 1]; // Solo controles aplicables
$controles = $controlesController->listar($filtros);

// Control pre-seleccionado desde detalle-control.php
$control_preseleccionado = isset($_GET['control_id']) ? (int)$_GET['control_id'] : null;

$evidenciasController = new \App\Controllers\EvidenciasController();
$tipos = $evidenciasController->getTipos();

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<main class="main-offset">
    <div class="content-wrapper">
        
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-600">
                <li><a href="<?php echo BASE_URL; ?>/public/evidencias" class="hover:text-blue-600">Evidencias</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-gray-900 font-medium">Subir Evidencia</li>
            </ol>
        </nav>

        <!-- Formulario -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 max-w-4xl">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Subir Nueva Evidencia</h2>
            
            <form method="POST" action="<?php echo BASE_URL; ?>/public/evidencias/procesar" enctype="multipart/form-data" id="form-subir-evidencia">
                
                <?php echo \App\Helpers\Security::csrfField(); ?>
                
                <!-- Seleccionar control -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Control ISO 27001 <span class="text-red-600">*</span>
                    </label>
                    <select name="control_id" required 
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Seleccione un control --</option>
                        <?php foreach ($controles as $control): ?>
                            <option value="<?php echo $control['id']; ?>"
                                    <?php echo ($control_preseleccionado == $control['id']) ? 'selected' : ''; ?>>
                                <?php echo $control['codigo']; ?> - <?php echo htmlspecialchars($control['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Seleccione el control al que pertenece esta evidencia</p>
                </div>

                <!-- Tipo de evidencia -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Evidencia
                    </label>
                    <select name="tipo_evidencia_id" 
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Seleccione tipo --</option>
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>">
                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                                <?php if ($tipo['descripcion']): ?>
                                    - <?php echo htmlspecialchars($tipo['descripcion']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Descripción -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción de la Evidencia <span class="text-red-600">*</span>
                    </label>
                    <textarea name="descripcion" rows="4" required
                              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                              placeholder="Describa brevemente esta evidencia..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Explique qué documenta este archivo y cómo respalda el control</p>
                </div>

                <!-- Upload de archivo -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Archivo <span class="text-red-600">*</span>
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition">
                        <input type="file" name="archivo" id="archivo" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               class="hidden" onchange="mostrarNombreArchivo(this)">
                        <label for="archivo" class="cursor-pointer">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="text-sm text-gray-600 mb-2">Haga clic para seleccionar un archivo</p>
                            <p class="text-xs text-gray-500">PDF, DOCX, XLSX, JPG, PNG (Máx. <?php echo UPLOAD_MAX_SIZE / 1024 / 1024; ?>MB)</p>
                        </label>
                        <p id="nombre-archivo" class="text-sm text-blue-600 font-medium mt-3 hidden"></p>
                    </div>
                </div>

                <!-- Info adicional -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold mb-1">Recomendaciones:</p>
                            <ul class="list-disc list-inside space-y-1 text-xs">
                                <li>Use nombres de archivo descriptivos</li>
                                <li>Asegúrese de que el documento sea legible</li>
                                <li>Incluya fechas o versiones en la descripción</li>
                                <li>Una vez subido, el archivo entrará en estado "Pendiente" para validación</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex space-x-3 pt-6 border-t">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                        <i class="fas fa-upload mr-2"></i>Subir Evidencia
                    </button>
                    <a href="<?php echo BASE_URL; ?>/public/evidencias" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                </div>

            </form>