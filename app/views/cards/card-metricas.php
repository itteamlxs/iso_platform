<?php
/**
 * Card: Métricas Generales
 */

try {
    // Cargar dependencias
    require_once __DIR__ . '/../../models/Database.php';
    require_once __DIR__ . '/../../controllers/DashboardController.php';
    
    $controller = new \App\Controllers\DashboardController();
    $result = $controller->getMetricas();
    
    if ($result['success']) {
        $data = $result['data'];
        $porcentaje = $result['porcentaje'];
?>

<div class="space-y-6">
    <!-- Porcentaje principal -->
    <div class="text-center">
        <div class="relative inline-flex items-center justify-center w-32 h-32">
            <svg class="transform -rotate-90 w-32 h-32">
                <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-200"/>
                <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="transparent" 
                        class="text-blue-600" stroke-dasharray="351.86" 
                        stroke-dashoffset="<?php echo 351.86 - (351.86 * $porcentaje / 100); ?>" 
                        stroke-linecap="round"/>
            </svg>
            <span class="absolute text-3xl font-bold text-gray-800"><?php echo $porcentaje; ?>%</span>
        </div>
        <p class="mt-2 text-sm text-gray-600">Cumplimiento General</p>
    </div>
    
    <!-- Detalles -->
    <div class="space-y-3">
        <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Implementados</span>
            <span class="font-semibold text-green-600"><?php echo $data['implementados']; ?></span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Parciales</span>
            <span class="font-semibold text-yellow-600"><?php echo $data['parciales']; ?></span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">No Implementados</span>
            <span class="font-semibold text-red-600"><?php echo $data['no_implementados']; ?></span>
        </div>
        <div class="flex justify-between items-center pt-3 border-t">
            <span class="text-sm text-gray-600">Total Controles</span>
            <span class="font-semibold text-gray-800"><?php echo $data['total_controles']; ?></span>
        </div>
    </div>
</div>

<?php
    } else {
        echo '<div class="text-red-600 text-sm p-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error al cargar métricas
              </div>';
    }
} catch (Exception $e) {
    echo '<div class="text-red-600 text-sm p-4">
            <i class="fas fa-exclamation-circle mr-2"></i>
            ' . htmlspecialchars($e->getMessage()) . '
          </div>';
}
?>