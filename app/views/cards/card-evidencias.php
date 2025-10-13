<?php
/**
 * Card: Últimas Evidencias
 */

try {
    require_once __DIR__ . '/../../models/Database.php';
    require_once __DIR__ . '/../../controllers/DashboardController.php';
    
    $controller = new \App\Controllers\DashboardController();
    $result = $controller->getUltimasEvidencias();
    
    if ($result['success']) {
        if (count($result['data']) > 0) {
?>

<div class="space-y-3">
    <?php foreach ($result['data'] as $evidencia): 
        $badge_color = [
            'aprobada' => 'bg-green-100 text-green-800',
            'pendiente' => 'bg-yellow-100 text-yellow-800',
            'rechazada' => 'bg-red-100 text-red-800'
        ];
        $color = $badge_color[$evidencia['estado_validacion']] ?? 'bg-gray-100 text-gray-800';
    ?>
    <div class="border-b border-gray-100 pb-2">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($evidencia['codigo']); ?></p>
                <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($evidencia['descripcion']); ?></p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full <?php echo $color; ?> ml-2">
                <?php echo ucfirst($evidencia['estado_validacion']); ?>
            </span>
        </div>
        <p class="text-xs text-gray-500 mt-1"><?php echo date('d/m/Y', strtotime($evidencia['fecha_subida'])); ?></p>
    </div>
    <?php endforeach; ?>
</div>

<?php
        } else {
?>
<div class="text-center py-8">
    <i class="fas fa-folder-open text-4xl text-gray-400 mb-3"></i>
    <p class="text-gray-600">No hay evidencias subidas</p>
    <p class="text-sm text-gray-500 mt-1">Sube tus primeros documentos</p>
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