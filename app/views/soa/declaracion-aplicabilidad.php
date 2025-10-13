<?php
$page_title = 'Declaración de Aplicabilidad (SOA)';

// Cargar dependencias
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Control.php';
require_once __DIR__ . '/../../controllers/ControlesController.php';

$controller = new \App\Controllers\ControlesController();
$controles = $controller->listar();
$dominios = $controller->getDominios();
$stats = $controller->getEstadisticas();

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<main class="main-offset">
    <div class="content-wrapper">
        
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Declaración de Aplicabilidad (SOA)</h2>
            <p class="text-gray-600 mt-1">Statement of Applicability - ISO 27001:2022 Anexo A</p>
        </div>

        <!-- Resumen ejecutivo -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
                <div class="text-center">
                    <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                    <p class="text-sm text-gray-600 mt-1">Total Controles</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600"><?php echo $stats['implementados']; ?></p>
                    <p class="text-sm text-gray-600 mt-1">Implementados</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['parciales']; ?></p>
                    <p class="text-sm text-gray-600 mt-1">Parciales</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-red-600"><?php echo $stats['no_implementados']; ?></p>
                    <p class="text-sm text-gray-600 mt-1">No Implementados</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-gray-600"><?php echo $stats['no_aplicables']; ?></p>
                    <p class="text-sm text-gray-600 mt-1">No Aplicables</p>
                </div>
            </div>

            <!-- Barra de progreso -->
            <div class="mt-6">
                <?php 
                $aplicables = $stats['total'] - $stats['no_aplicables'];
                $porcentaje = $aplicables > 0 ? round(($stats['implementados'] / $aplicables) * 100, 1) : 0;
                ?>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Progreso de Implementación</span>
                    <span class="text-sm font-bold text-blue-600"><?php echo $porcentaje; ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-blue-600 h-4 rounded-full transition-all duration-500" style="width: <?php echo $porcentaje; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-print mr-2"></i>Imprimir SOA
                </button>
                <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition ml-2">
                    <i class="fas fa-file-excel mr-2"></i>Exportar Excel
                </button>
            </div>
            <div>
                <button onclick="expandirTodos()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">
                    <i class="fas fa-chevron-down mr-2"></i>Expandir Todos
                </button>
                <button onclick="colapsarTodos()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition ml-2">
                    <i class="fas fa-chevron-up mr-2"></i>Colapsar Todos
                </button>
            </div>
        </div>

        <!-- Acordeón SOA por dominio -->
        <?php foreach ($dominios as $index => $dominio): 
            $controles_dominio = array_filter($controles, function($c) use ($dominio) {
                return $c['dominio_codigo'] == $dominio['codigo'];
            });
            
            if (count($controles_dominio) == 0) continue;
            
            $dominio_id = 'dominio-' . $dominio['id'];
        ?>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-4 overflow-hidden">
            <!-- Header del dominio (clickeable) -->
            <button onclick="toggleAccordion('<?php echo $dominio_id; ?>')" 
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 px-6 py-4 flex items-center justify-between transition">
                <div class="text-left">
                    <h3 class="text-lg font-bold text-white">
                        <?php echo $dominio['codigo']; ?>. <?php echo htmlspecialchars($dominio['nombre']); ?>
                    </h3>
                    <p class="text-blue-100 text-sm mt-1"><?php echo count($controles_dominio); ?> controles</p>
                </div>
                <i id="icon-<?php echo $dominio_id; ?>" class="fas fa-chevron-down text-white text-xl accordion-icon"></i>
            </button>

            <!-- Contenido del acordeón -->
            <div id="<?php echo $dominio_id; ?>" class="accordion-content">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Control</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">Aplicable</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-40">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Justificación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($controles_dominio as $control): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo $control['codigo']; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($control['nombre']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(substr($control['descripcion'], 0, 100)); ?>...</p>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <?php if ($control['aplicable']): ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                            <i class="fas fa-check mr-1"></i> SÍ
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded-full">
                                            <i class="fas fa-times mr-1"></i> NO
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <?php if ($control['aplicable']): 
                                        $badges = [
                                            'implementado' => '<span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full"><i class="fas fa-check-circle mr-1"></i> Implementado</span>',
                                            'parcial' => '<span class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full"><i class="fas fa-exclamation-circle mr-1"></i> Parcial</span>',
                                            'no_implementado' => '<span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full"><i class="fas fa-times-circle mr-1"></i> No Implementado</span>'
                                        ];
                                        echo $badges[$control['estado']] ?? '-';
                                    else:
                                        echo '<span class="text-gray-400 text-xs">N/A</span>';
                                    endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!$control['aplicable'] && $control['justificacion']): ?>
                                        <p class="text-xs text-gray-700"><?php echo htmlspecialchars($control['justificacion']); ?></p>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php endforeach; ?>

        <!-- Pie de página -->
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-6 text-center text-sm text-gray-600">
            <p>Documento generado el <?php echo date('d/m/Y H:i'); ?></p>
            <p class="mt-1">ISO 27001:2022 - Anexo A: Controles de Seguridad de la Información</p>
        </div>

    </div>
</main>

<script>
function toggleAccordion(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    
    content.classList.toggle('active');
    icon.classList.toggle('rotated');
}

// Expandir todos
function expandirTodos() {
    document.querySelectorAll('[id^="dominio-"]').forEach(el => {
        el.classList.add('active');
        document.getElementById('icon-' + el.id).classList.add('rotated');
    });
}

// Colapsar todos
function colapsarTodos() {
    document.querySelectorAll('[id^="dominio-"]').forEach(el => {
        el.classList.remove('active');
        document.getElementById('icon-' + el.id).classList.remove('rotated');
    });
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>