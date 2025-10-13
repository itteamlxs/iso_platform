<?php
$page_title = 'Crear GAP';

// Cargar dependencias
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Control.php';
require_once __DIR__ . '/../../controllers/ControlesController.php';

// Obtener controles para el selector
$controlesController = new \App\Controllers\ControlesController();
$controles = $controlesController->listar();

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
                <li class="text-gray-900 font-medium">Crear GAP</li>
            </ol>
        </nav>

        <!-- Formulario -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 max-w-4xl">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Identificar Nueva Brecha</h2>
            
            <form method="POST" action="<?php echo BASE_URL; ?>/public/gap/guardar">
                
                <!-- Seleccionar control -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Control ISO 27001 <span class="text-red-600">*</span>
                    </label>
                    <select name="control_id" required 
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Seleccione un control --</option>
                        <?php foreach ($controles as $control): ?>
                            <option value="<?php echo $control['id']; ?>">
                                <?php echo $control['codigo']; ?> - <?php echo htmlspecialchars($control['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Seleccione el control donde se identificó la brecha</p>
                </div>

                <!-- Descripción de la brecha -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Descripción de la Brecha <span class="text-red-600">*</span>
                    </label>
                    <textarea name="brecha" rows="4" required
                              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                              placeholder="Describa detalladamente la brecha identificada..."></textarea>
                </div>

                <!-- Objetivo -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Objetivo de Mejora
                    </label>
                    <textarea name="objetivo" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                              placeholder="¿Qué se espera lograr al cerrar esta brecha?"></textarea>
                </div>

                <!-- Grid: Prioridad y Responsable -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Prioridad -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Prioridad <span class="text-red-600">*</span>
                        </label>
                        <select name="prioridad" required
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500">
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                            <option value="baja">Baja</option>
                        </select>
                    </div>

                    <!-- Responsable -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Responsable
                        </label>
                        <input type="text" name="responsable"
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                               placeholder="Nombre del responsable">
                    </div>

                </div>

                <!-- Fecha estimada de cierre -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Fecha Estimada de Cierre
                    </label>
                    <input type="date" name="fecha_estimada_cierre"
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>

                <!-- Botones -->
                <div class="flex space-x-3 pt-6 border-t">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Crear GAP
                    </button>
                    <a href="<?php echo BASE_URL; ?>/public/gap" 
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                </div>

            </form>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>