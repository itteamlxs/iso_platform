<?php
$page_title = 'Análisis de Brechas (GAP)';

// Cargar dependencias
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Gap.php';
require_once __DIR__ . '/../../controllers/GapController.php';
require_once __DIR__ . '/../../helpers/Security.php';

use App\Helpers\Security;

// Obtener filtros
$filtros = [
    'prioridad' => $_GET['prioridad'] ?? '',
    'estado' => $_GET['estado'] ?? ''
];

$controller = new \App\Controllers\GapController();
$gaps = $controller->listar($filtros);
$stats = $controller->getEstadisticas();

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<main class="main-offset">
    <div class="content-wrapper">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Análisis de Brechas (GAP)</h2>
                <p class="text-gray-600 mt-1">Identificación y seguimiento de brechas de cumplimiento</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/public/gap/crear" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition">
                <i class="fas fa-plus"></i>
                <span>Nuevo GAP</span>
            </a>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Total GAPs</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo Security::sanitizeOutput($stats['total']); ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Alta Prioridad</p>
                <p class="text-2xl font-bold text-red-600"><?php echo Security::sanitizeOutput($stats['alta']); ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Media Prioridad</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo Security::sanitizeOutput($stats['media']); ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Baja Prioridad</p>
                <p class="text-2xl font-bold text-blue-600"><?php echo Security::sanitizeOutput($stats['baja']); ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Avance Promedio</p>
                <p class="text-2xl font-bold text-green-600"><?php echo Security::sanitizeOutput($stats['avance_promedio']); ?>%</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                    <select name="prioridad" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas</option>
                        <option value="alta" <?php echo $filtros['prioridad'] == 'alta' ? 'selected' : ''; ?>>Alta</option>
                        <option value="media" <?php echo $filtros['prioridad'] == 'media' ? 'selected' : ''; ?>>Media</option>
                        <option value="baja" <?php echo $filtros['prioridad'] == 'baja' ? 'selected' : ''; ?>>Baja</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="estado" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="pendiente" <?php echo $filtros['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="cerrado" <?php echo $filtros['estado'] == 'cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-filter mr-2"></i>Filtrar
                    </button>
                    <a href="<?php echo BASE_URL; ?>/public/gap" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Lista de GAPs -->
        <?php if (count($gaps) > 0): ?>
            <div class="space-y-4">
                <?php foreach ($gaps as $gap): 
                    $prioridad_color = [
                        'alta' => 'border-red-500 bg-red-50',
                        'media' => 'border-yellow-500 bg-yellow-50',
                        'baja' => 'border-blue-500 bg-blue-50'
                    ];
                    $badge_color = [
                        'alta' => 'bg-red-100 text-red-800',
                        'media' => 'bg-yellow-100 text-yellow-800',
                        'baja' => 'bg-blue-100 text-blue-800'
                    ];
                    $es_cerrado = $gap['avance'] >= 100 || $gap['fecha_real_cierre'];
                ?>
                
                <div class="bg-white rounded-xl shadow-sm border-l-4 <?php echo $prioridad_color[$gap['prioridad']]; ?> p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <span class="text-xs px-3 py-1 rounded-full font-semibold <?php echo $badge_color[$gap['prioridad']]; ?>">
                                    <?php echo Security::sanitizeOutput(strtoupper($gap['prioridad'])); ?>
                                </span>
                                <span class="text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded-full">
                                    <?php echo Security::sanitizeOutput($gap['codigo']); ?>
                                </span>
                                <?php if ($es_cerrado): ?>
                                    <span class="text-xs px-3 py-1 bg-green-100 text-green-800 rounded-full font-semibold">
                                        <i class="fas fa-check mr-1"></i>CERRADO
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-1"><?php echo Security::sanitizeOutput($gap['brecha']); ?></h3>
                            <p class="text-sm text-gray-600 mb-2"><?php echo Security::sanitizeOutput($gap['control']); ?></p>
                            <?php if ($gap['objetivo']): ?>
                                <p class="text-sm text-gray-700 mt-2">
                                    <strong>Objetivo:</strong> <?php echo Security::sanitizeOutput($gap['objetivo']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/public/gap/<?php echo Security::sanitizeOutput($gap['id']); ?>" 
                           class="text-blue-600 hover:text-blue-800 ml-4">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Barra de progreso -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-600">Avance</span>
                            <span class="text-xs font-semibold text-gray-900"><?php echo Security::sanitizeOutput($gap['avance']); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: <?php echo Security::sanitizeOutput($gap['avance']); ?>%"></div>
                        </div>
                    </div>

                    <!-- Info adicional -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Responsable</p>
                            <p class="font-medium text-gray-900"><?php echo Security::sanitizeOutput($gap['responsable'] ?? 'Sin asignar'); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">Fecha compromiso</p>
                            <p class="font-medium text-gray-900">
                                <?php echo $gap['fecha_estimada_cierre'] ? Security::sanitizeOutput(date('d/m/Y', strtotime($gap['fecha_estimada_cierre']))) : '-'; ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600">Acciones</p>
                            <p class="font-medium text-gray-900">
                                <?php echo Security::sanitizeOutput($gap['acciones_completadas']); ?>/<?php echo Security::sanitizeOutput($gap['total_acciones']); ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600">Dominio</p>
                            <p class="font-medium text-gray-900"><?php echo Security::sanitizeOutput($gap['dominio']); ?></p>
                        </div>
                    </div>
                </div>

                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <i class="fas fa-clipboard-check text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay GAPs registrados</h3>
                <p class="text-gray-600 mb-6">Comienza identificando las brechas de cumplimiento</p>
                <a href="<?php echo BASE_URL; ?>/public/gap/crear" 
                   class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Crear Primer GAP
                </a>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>