<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/sidebar.php';
?>

<!-- Contenido principal -->
<main class="main-offset">
    <div class="content-wrapper">
        
        <!-- Título y acciones -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Panel de Control</h2>
                <p class="text-gray-600 mt-1">Resumen del cumplimiento ISO 27001</p>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition">
                <i class="fas fa-download"></i>
                <span>Exportar Reporte</span>
            </button>
        </div>

        <!-- Grid de Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Card: Métricas Generales -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 card-hover">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Cumplimiento General</h3>
                </div>
                <div class="p-6">
                    <?php include __DIR__ . '/cards/card-metricas.php'; ?>
                </div>
            </div>

            <!-- Card: Controles por Dominio -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 card-hover">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Por Dominio</h3>
                </div>
                <div class="p-6">
                    <?php include __DIR__ . '/cards/card-dominios.php'; ?>
                </div>
            </div>

            <!-- Card: Requerimientos Base -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 card-hover">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Requerimientos Base</h3>
                </div>
                <div class="p-6">
                    <?php include __DIR__ . '/cards/card-requerimientos.php'; ?>
                </div>
            </div>

            <!-- Card: GAPs Prioritarios -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 card-hover">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">GAPs Alta Prioridad</h3>
                </div>
                <div class="p-6">
                    <?php include __DIR__ . '/cards/card-gaps.php'; ?>
                </div>
            </div>

            <!-- Card: Últimas Evidencias -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 card-hover">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Últimas Evidencias</h3>
                </div>
                <div class="p-6">
                    <?php include __DIR__ . '/cards/card-evidencias.php'; ?>
                </div>
            </div>

            <!-- Card: Acciones Pendientes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 card-hover">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Acciones Pendientes</h3>
                </div>
                <div class="p-6">
                    <?php include __DIR__ . '/cards/card-acciones.php'; ?>
                </div>
            </div>
            

            <!-- Card: Mapa de Calor -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 card-hover lg:col-span-3">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-fire text-orange-500"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Mapa de Calor de Cumplimiento</h3>
                    </div>
                </div>
                <div class="p-6">
                    <?php include __DIR__ . '/cards/card-heatmap.php'; ?>
                </div>
            </div>


        </div>

    </div>
</main>

<?php require_once __DIR__ . '/components/footer.php'; ?>