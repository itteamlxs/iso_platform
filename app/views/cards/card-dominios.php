<?php
/**
 * Card: Controles por Dominio
 */

try {
    require_once __DIR__ . '/../../models/Database.php';
    require_once __DIR__ . '/../../controllers/DashboardController.php';
    
    $controller = new \App\Controllers\DashboardController();
    $result = $controller->getControlsPorDominio();
    
    if ($result['success']) {
?>

<div class="space-y-3">
    <?php foreach ($result['data'] as $dominio): 
        $porcentaje = $dominio['total'] > 0 ? round(($dominio['implementados'] / $dominio['total']) * 100) : 0;
    ?>
    <div>
        <div class="flex justify-between items-center mb-1">
            <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($dominio['dominio']); ?></span>
            <span class="text-sm text-gray-600"><?php echo $dominio['implementados']; ?>/<?php echo $dominio['total']; ?></span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="progress-bar bg-blue-600 h-2 rounded-full" style="width: <?php echo $porcentaje; ?>%"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
    } else {
        echo '<div class="text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>Error al cargar</div>';
    }
} catch (Exception $e) {
    echo '<div class="text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>