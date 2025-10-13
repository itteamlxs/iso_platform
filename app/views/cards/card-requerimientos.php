<?php
/**
 * Card: Requerimientos Base
 */

try {
    require_once __DIR__ . '/../../models/Database.php';
    require_once __DIR__ . '/../../controllers/DashboardController.php';
    
    $controller = new \App\Controllers\DashboardController();
    $result = $controller->getRequerimientos();
    
    if ($result['success']) {
        $data = $result['data'];
        $porcentaje = $data['total'] > 0 ? round(($data['completados'] / $data['total']) * 100) : 0;
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-3xl font-bold text-gray-800"><?php echo $data['completados']; ?>/<?php echo $data['total']; ?></p>
            <p class="text-sm text-gray-600">Completados</p>
        </div>
        <div class="text-right">
            <p class="text-2xl font-bold text-blue-600"><?php echo $porcentaje; ?>%</p>
        </div>
    </div>
    
    <div class="w-full bg-gray-200 rounded-full h-3">
        <div class="progress-bar bg-green-600 h-3 rounded-full" style="width: <?php echo $porcentaje; ?>%"></div>
    </div>
    
    <div class="grid grid-cols-3 gap-2 text-center pt-2">
        <div>
            <p class="text-lg font-semibold text-yellow-600"><?php echo $data['en_proceso']; ?></p>
            <p class="text-xs text-gray-600">En Proceso</p>
        </div>
        <div>
            <p class="text-lg font-semibold text-gray-600"><?php echo $data['pendientes']; ?></p>
            <p class="text-xs text-gray-600">Pendientes</p>
        </div>
        <div>
            <p class="text-lg font-semibold text-green-600"><?php echo $data['completados']; ?></p>
            <p class="text-xs text-gray-600">Listos</p>
        </div>
    </div>
</div>

<?php
    } else {
        echo '<div class="text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>Error al cargar</div>';
    }
} catch (Exception $e) {
    echo '<div class="text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>