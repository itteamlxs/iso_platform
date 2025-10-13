<?php
// Obtener la ruta actual para marcar el menú activo
$current_page = $_SERVER['REQUEST_URI'];
$current_page = str_replace('/iso_platform/public', '', $current_page);
$current_page = strtok($current_page, '?');

function is_active($path) {
    global $current_page;
    return (strpos($current_page, $path) === 0) ? 'bg-blue-50 text-blue-600 border-l-4 border-blue-600' : 'text-gray-600 hover:bg-gray-100';
}
?>

<!-- Sidebar fijo -->
<aside id="sidebar" class="sidebar-fixed bg-white border-r border-gray-200 shadow-sm overflow-y-auto custom-scrollbar">
    
    <!-- Logo -->
    <div class="h-16 flex items-center justify-center border-b border-gray-200 px-4">
        <div class="flex items-center space-x-2">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-shield-alt text-white text-xl"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-800 text-sm">ISO 27001</h2>
                <p class="text-xs text-gray-500">Compliance</p>
            </div>
        </div>
    </div>

    <!-- Navegación -->
    <nav class="p-4 space-y-1">
        
        <!-- Dashboard -->
        <a href="<?php echo BASE_URL; ?>/public/" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo is_active('/') || is_active('/dashboard'); ?>">
            <i class="fas fa-home w-5"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <!-- Separador -->
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Evaluación</p>
        </div>

        <!-- Controles -->
        <a href="<?php echo BASE_URL; ?>/public/controles" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo is_active('/controles'); ?>">
            <i class="fas fa-check-circle w-5"></i>
            <span class="font-medium">Controles ISO</span>
            <span class="ml-auto bg-gray-200 text-gray-700 text-xs font-semibold px-2 py-1 rounded-full">93</span>
        </a>

        <!-- SOA -->
        <a href="<?php echo BASE_URL; ?>/public/soa" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo is_active('/soa'); ?>">
            <i class="fas fa-file-alt w-5"></i>
            <span class="font-medium">SOA</span>
        </a>

        <!-- GAP Analysis -->
        <a href="<?php echo BASE_URL; ?>/public/gap" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo is_active('/gap'); ?>">
            <i class="fas fa-exclamation-triangle w-5"></i>
            <span class="font-medium">GAP Analysis</span>
        </a>

        <!-- Separador -->
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Documentación</p>
        </div>

        <!-- Evidencias -->
        <a href="<?php echo BASE_URL; ?>/public/evidencias" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo is_active('/evidencias'); ?>">
            <i class="fas fa-folder-open w-5"></i>
            <span class="font-medium">Evidencias</span>
        </a>

        <!-- Requerimientos -->
        <a href="<?php echo BASE_URL; ?>/public/requerimientos" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo is_active('/requerimientos'); ?>">
            <i class="fas fa-clipboard-list w-5"></i>
            <span class="font-medium">Requerimientos</span>
            <span class="ml-auto bg-gray-200 text-gray-700 text-xs font-semibold px-2 py-1 rounded-full">7</span>
        </a>

        <!-- Separador -->
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Gestión</p>
        </div>

        <!-- Reportes -->
        <a href="<?php echo BASE_URL; ?>/public/reportes" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo is_active('/reportes'); ?>">
            <i class="fas fa-chart-bar w-5"></i>
            <span class="font-medium">Reportes</span>
        </a>

        <!-- Auditorías -->
        <a href="<?php echo BASE_URL; ?>/public/auditorias" 
           class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo is_active('/auditorias'); ?>">
            <i class="fas fa-search w-5"></i>
            <span class="font-medium">Auditorías</span>
        </a>

    </nav>

    <!-- Footer del sidebar -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-gray-50">
        <div class="text-center">
            <p class="text-xs text-gray-500">v<?php echo APP_VERSION; ?></p>
        </div>
    </div>

</aside>