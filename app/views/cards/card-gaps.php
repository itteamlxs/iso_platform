<?php
/**
 * Card: GAPs Alta Prioridad
 */

try {
    require_once __DIR__ . '/../../models/Database.php';
    require_once __DIR__ . '/../../controllers/DashboardController.php';
    
    $controller = new \App\Controllers\DashboardController();
    $result = $controller->getGapsPrioritarios();
    
    if ($result['success']) {
        if (count($result['data']) > 0) {
?>

<div class="space-y-3">
    <?php foreach ($result['data'] as $gap): ?>
    <div class="border-l-4 border-red-500 pl-3 py-2">
        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($gap['codigo']); ?>: <?php echo htmlspecialchars($gap['control']); ?></p>
        <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($gap['brecha']); ?></p>
        <div class="flex items-center mt-2">
            <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                <div class="bg-yellow-500 h-1.5 rounded-full" style="width: <?php echo $gap['avance']; ?>%"></div>
            </div>
            <span class="text-xs text-gray-600 ml-2"><?php echo $gap['avance']; ?>%</span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
        } else {
?>
<div class="text-center py-8">
    <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
    <p class="text-gray-600">No hay GAPs de alta prioridad</p>
    <p class="text-sm text-gray-500 mt-1">¡Excelente trabajo!</p>
</div>
<?php
        }
    } else {
        echo '<div class="text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>Error al cargar</div>';
    }
} catch (Exception $e) {
    echo '<div class="text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>