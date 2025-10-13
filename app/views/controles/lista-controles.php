<?php
$page_title = 'Controles ISO 27001';

// Cargar dependencias
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../../models/Control.php';
require_once __DIR__ . '/../../controllers/ControlesController.php';

// Obtener filtros
$filtros = [
    'dominio' => $_GET['dominio'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'aplicable' => isset($_GET['aplicable']) ? $_GET['aplicable'] : null
];

$controller = new \App\Controllers\ControlesController();
$controles = $controller->listar($filtros);
$dominios = $controller->getDominios();
$stats = $controller->getEstadisticas();

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<main class="main-offset">
    <div class="content-wrapper">
        
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Controles ISO 27001:2022</h2>
            <p class="text-gray-600 mt-1">Gestión de los 93 controles del Anexo A</p>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Total</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Implementados</p>
                <p class="text-2xl font-bold text-green-600"><?php echo $stats['implementados']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">Parciales</p>
                <p class="text-2xl font-bold text-yellow-600"><?php echo $stats['parciales']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">No Implementados</p>
                <p class="text-2xl font-bold text-red-600"><?php echo $stats['no_implementados']; ?></p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <p class="text-sm text-gray-600">No Aplicables</p>
                <p class="text-2xl font-bold text-gray-600"><?php echo $stats['no_aplicables']; ?></p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <!-- Filtro por dominio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dominio</label>
                    <select name="dominio" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <?php foreach ($dominios as $dominio): ?>
                        <option value="<?php echo $dominio['id']; ?>" <?php echo $filtros['dominio'] == $dominio['id'] ? 'selected' : ''; ?>>
                            <?php echo $dominio['codigo']; ?> - <?php echo htmlspecialchars($dominio['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filtro por estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="estado" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="implementado" <?php echo $filtros['estado'] == 'implementado' ? 'selected' : ''; ?>>Implementado</option>
                        <option value="parcial" <?php echo $filtros['estado'] == 'parcial' ? 'selected' : ''; ?>>Parcial</option>
                        <option value="no_implementado" <?php echo $filtros['estado'] == 'no_implementado' ? 'selected' : ''; ?>>No Implementado</option>
                    </select>
                </div>

                <!-- Filtro por aplicabilidad -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Aplicabilidad</label>
                    <select name="aplicable" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="1" <?php echo $filtros['aplicable'] === '1' ? 'selected' : ''; ?>>Aplicables</option>
                        <option value="0" <?php echo $filtros['aplicable'] === '0' ? 'selected' : ''; ?>>No Aplicables</option>
                    </select>
                </div>

                <!-- Botones -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-filter mr-2"></i>Filtrar
                    </button>
                    <a href="<?php echo BASE_URL; ?>/public/controles" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabla de controles -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Control</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dominio</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aplicable</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Evidencias</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (count($controles) > 0): ?>
                            <?php foreach ($controles as $control): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo $control['codigo']; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($control['nombre']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(substr($control['descripcion'], 0, 80)); ?>...</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded"><?php echo $control['dominio_codigo']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <?php if ($control['aplicable']): ?>
                                        <span class="text-green-600"><i class="fas fa-check-circle"></i></span>
                                    <?php else: ?>
                                        <span class="text-gray-400"><i class="fas fa-times-circle"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <?php
                                    $badges = [
                                        'implementado' => 'bg-green-100 text-green-800',
                                        'parcial' => 'bg-yellow-100 text-yellow-800',
                                        'no_implementado' => 'bg-red-100 text-red-800'
                                    ];
                                    $badge_class = $badges[$control['estado']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="text-xs px-2 py-1 rounded-full <?php echo $badge_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $control['estado'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="text-sm text-gray-600">
                                        <i class="fas fa-paperclip mr-1"></i><?php echo $control['total_evidencias']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <a href="<?php echo BASE_URL; ?>/public/controles/<?php echo $control['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-search text-4xl mb-3"></i>
                                    <p>No se encontraron controles con los filtros aplicados</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>