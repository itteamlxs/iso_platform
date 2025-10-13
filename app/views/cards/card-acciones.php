<?php
/**
 * Card: Acciones Pendientes
 */

try {
    require_once __DIR__ . '/../../models/Database.php';
    require_once __DIR__ . '/../../controllers/DashboardController.php';
    
    $controller = new \App\Controllers\DashboardController();
    $result = $controller->getAccionesPendientes();
    
    if ($result['success']) {
        if (count($result['data']) > 0) {
?>

<div class="space-y-3">
    <?php foreach ($result['data'] as $accion): 
        $fecha = new DateTime($accion['fecha_compromiso']);
        $hoy = new DateTime();
        $diff = $hoy->diff($fecha);
        $es_urgente = $fecha < $hoy;
    ?>
    <div class="border-l-4 <?php echo $es_urgente ? 'border-red-500' : 'border-blue-500'; ?> pl-3 py-2">
        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($accion['descripcion']); ?></p>
        <div class="flex items-center justify-between mt-2">
            <span class="text-xs text-gray-600">
                <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($accion['responsable'] ?? 'Sin asignar'); ?>
            </span>
            <span class="text-xs <?php echo $es_urgente ? 'text-red-600 font-semibold' : 'text-gray-600'; ?>">
                <i class="fas fa-calendar mr-1"></i><?php echo date('d/m/Y', strtotime($accion['fecha_compromiso'])); ?>
            </span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
        } else {
?>
<div class="text-center py-8">
    <i class="fas fa-clipboard-check text-4xl text-green-500 mb-3"></i>
    <p class="text-gray-600">No hay acciones pendientes</p>
    <p class="text-sm text-gray-500 mt-1">Todo al día</p>
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