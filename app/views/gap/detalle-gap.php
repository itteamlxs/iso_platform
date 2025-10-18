<?php
$page_title = 'Detalle GAP';

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

// Calcular avance automático basado en acciones
$total_acciones = count($acciones);
$acciones_completadas = count(array_filter($acciones, fn($a) => $a['estado'] === 'completada'));
$avance_calculado = $total_acciones > 0 ? round(($acciones_completadas / $total_acciones) * 100) : 0;

$prioridad_color = [
    'alta' => 'border-red-500 bg-red-50',
    'media' => 'border-yellow-500 bg-yellow-50',
    'baja' => 'border-blue-500 bg-blue-50'
];

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
                <li class="text-gray-900 font-medium">GAP #<?php echo $gap['id']; ?></li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Columna principal -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Info del GAP -->
                <div class="bg-white rounded-xl shadow-sm border-l-4 <?php echo $prioridad_color[$gap['prioridad']]; ?> p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-3">
                                <span class="text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded-full font-semibold">
                                    <?php echo $gap['codigo']; ?>
                                </span>
                                <span class="text-xs px-3 py-1 bg-blue-100 text-blue-800 rounded-full">
                                    <?php echo htmlspecialchars($gap['dominio']); ?>
                                </span>
                                <?php if ($avance_calculado >= 100): ?>
                                    <span class="text-xs px-3 py-1 bg-green-100 text-green-800 rounded-full font-semibold">
                                        <i class="fas fa-check mr-1"></i>CERRADO
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($gap['control']); ?></h2>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Descripción de la Brecha</h3>
                        <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($gap['brecha'])); ?></p>
                    </div>

                    <?php if ($gap['objetivo']): ?>
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Objetivo de Mejora</h3>
                        <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($gap['objetivo'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Barra de progreso automática -->
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Avance de Implementación</span>
                            <span class="text-lg font-bold text-blue-600"><?php echo $avance_calculado; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-blue-600 h-4 rounded-full transition-all" style="width: <?php echo $avance_calculado; ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>Calculado automáticamente: <?php echo $acciones_completadas; ?>/<?php echo $total_acciones; ?> acciones completadas
                        </p>
                    </div>
                </div>

                <!-- Acciones Correctivas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Acciones Correctivas</h3>
                        <button onclick="document.getElementById('modal-accion').classList.remove('hidden')"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-plus mr-2"></i>Nueva Acción
                        </button>
                    </div>

                    <?php if (count($acciones) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($acciones as $accion): 
                                $estado_color = [
                                    'pendiente' => 'bg-gray-100 text-gray-800',
                                    'en_progreso' => 'bg-yellow-100 text-yellow-800',
                                    'completada' => 'bg-green-100 text-green-800'
                                ];
                                $siguiente_estado = [
                                    'pendiente' => 'en_progreso',
                                    'en_progreso' => 'completada',
                                    'completada' => 'pendiente'
                                ];
                                $siguiente_label = [
                                    'pendiente' => 'Iniciar',
                                    'en_progreso' => 'Completar',
                                    'completada' => 'Reiniciar'
                                ];
                            ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($accion['descripcion']); ?></p>
                                        <div class="flex items-center space-x-4 text-xs text-gray-600">
                                            <span><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($accion['responsable'] ?? 'Sin asignar'); ?></span>
                                            <span><i class="fas fa-calendar mr-1"></i><?php echo date('d/m/Y', strtotime($accion['fecha_compromiso'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 ml-4">
                                        <span class="text-xs px-3 py-1 rounded-full font-semibold <?php echo $estado_color[$accion['estado']]; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $accion['estado'])); ?>
                                        </span>
                                        <form method="POST" action="<?php echo BASE_URL; ?>/public/gap/accion/actualizar-estado" style="display: inline;">
                                            <input type="hidden" name="accion_id" value="<?php echo $accion['id']; ?>">
                                            <input type="hidden" name="gap_id" value="<?php echo $gap['id']; ?>">
                                            <input type="hidden" name="nuevo_estado" value="<?php echo $siguiente_estado[$accion['estado']]; ?>">
                                            <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs px-2 py-1 rounded bg-blue-50 hover:bg-blue-100 transition">
                                                <i class="fas fa-arrow-right mr-1"></i><?php echo $siguiente_label[$accion['estado']]; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-tasks text-4xl mb-3"></i>
                            <p>No hay acciones correctivas definidas</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Columna lateral -->
            <div class="space-y-6">
                
                <!-- Info rápida -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Información</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-600">Prioridad</p>
                            <span class="inline-block mt-1 text-xs px-3 py-1 rounded-full font-semibold 
                                <?php echo ['alta' => 'bg-red-100 text-red-800', 'media' => 'bg-yellow-100 text-yellow-800', 'baja' => 'bg-blue-100 text-blue-800'][$gap['prioridad']]; ?>">
                                <?php echo strtoupper($gap['prioridad']); ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Responsable</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($gap['responsable'] ?? 'Sin asignar'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Fecha Compromiso</p>
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo $gap['fecha_estimada_cierre'] ? date('d/m/Y', strtotime($gap['fecha_estimada_cierre'])) : 'No definida'; ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Total Acciones</p>
                            <p class="text-sm font-medium text-gray-900"><?php echo $total_acciones; ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Avance Automático</p>
                            <p class="text-sm font-medium text-blue-600"><?php echo $avance_calculado; ?>%</p>
                        </div>
                        <?php if ($gap['fecha_real_cierre']): ?>
                        <div>
                            <p class="text-xs text-gray-600">Fecha Real de Cierre</p>
                            <p class="text-sm font-medium text-green-600">
                                <?php echo date('d/m/Y', strtotime($gap['fecha_real_cierre'])); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Acciones</h3>
                    <div class="space-y-2">
                        <a href="<?php echo BASE_URL; ?>/public/gap/<?php echo $gap['id']; ?>/editar"
                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition text-center">
                            <i class="fas fa-edit mr-2"></i>Editar GAP
                        </a>
                        <button onclick="confirmarEliminar(<?php echo $gap['id']; ?>)"
                                class="block w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition text-center">
                            <i class="fas fa-trash mr-2"></i>Eliminar GAP
                        </button>
                        <a href="<?php echo BASE_URL; ?>/public/gap" 
                           class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition text-center">
                            <i class="fas fa-arrow-left mr-2"></i>Volver a Lista
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>
</main>

<!-- Modal: Nueva Acción -->
<div id="modal-accion" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-8 max-w-2xl w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Nueva Acción Correctiva</h3>
        
        <form method="POST" action="<?php echo BASE_URL; ?>/public/gap/accion/guardar">
            <input type="hidden" name="gap_id" value="<?php echo $gap['id']; ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción <span class="text-red-600">*</span></label>
                <textarea name="descripcion" rows="3" required
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                          placeholder="Describa la acción a realizar..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Responsable</label>
                    <input type="text" name="responsable"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Compromiso</label>
                    <input type="date" name="fecha_compromiso" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="flex space-x-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>Guardar Acción
                </button>
                <button type="button" onclick="document.getElementById('modal-accion').classList.add('hidden')"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Confirmar Eliminación -->
<div id="modal-eliminar" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-8 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Confirmar Eliminación</h3>
        <p class="text-gray-700 mb-6">¿Está seguro de que desea eliminar este GAP? Esta acción marcará el GAP y sus acciones como inactivas.</p>
        
        <form method="POST" action="<?php echo BASE_URL; ?>/public/gap/eliminar">
            <input type="hidden" name="gap_id" id="modal-gap-id" value="">
            
            <div class="flex space-x-3">
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-trash mr-2"></i>Eliminar
                </button>
                <button type="button" onclick="cerrarModalEliminar()"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmarEliminar(gapId) {
    document.getElementById('modal-gap-id').value = gapId;
    document.getElementById('modal-eliminar').classList.remove('hidden');
}

function cerrarModalEliminar() {
    document.getElementById('modal-eliminar').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>