<?php
/**
 * Card: Mapa de Calor - Matriz de Riesgo
 * Probabilidad vs Impacto
 */

try {
    require_once __DIR__ . '/../../models/Database.php';
    
    $db = \App\Models\Database::getInstance()->getConnection();
    $empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
    
    // Obtener controles por estado y dominio
    $sql = "SELECT 
                cd.nombre as dominio,
                s.estado,
                COUNT(*) as cantidad
            FROM soa_entries s
            INNER JOIN controles c ON s.control_id = c.id
            INNER JOIN controles_dominio cd ON c.dominio_id = cd.id
            WHERE s.empresa_id = ? AND s.aplicable = 1
            GROUP BY cd.id, s.estado";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$empresa_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar datos en matriz 5x5
    $matriz = [];
    $impactos = ['Insignificante', 'Menor', 'Crítica', 'Mayor', 'Catastrófico'];
    $probabilidades = ['Improbable', 'Posible', 'Ocasional', 'Moderado', 'Constante'];
    
    // Distribuir controles en la matriz según estado
    $posiciones = [];
    foreach ($data as $item) {
        if ($item['estado'] == 'no_implementado') {
            // Rojo: Mayor probabilidad/impacto
            $posiciones[] = ['prob' => 3, 'imp' => 3, 'cantidad' => $item['cantidad'], 'dominio' => $item['dominio']];
        } elseif ($item['estado'] == 'parcial') {
            // Amarillo: Moderado
            $posiciones[] = ['prob' => 2, 'imp' => 2, 'cantidad' => $item['cantidad'], 'dominio' => $item['dominio']];
        } else {
            // Verde: Bajo
            $posiciones[] = ['prob' => 1, 'imp' => 1, 'cantidad' => $item['cantidad'], 'dominio' => $item['dominio']];
        }
    }
    
    // Calcular resumen
    $total_criticos = 0;
    $total_altos = 0;
    $total_medios = 0;
    $total_bajos = 0;
    
?>

<div class="space-y-4">
    
    <!-- Título y leyenda -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h4 class="font-semibold text-gray-800">Matriz de Riesgo - Probabilidad vs Impacto</h4>
            <p class="text-xs text-gray-600 mt-1">Distribución de controles no implementados</p>
        </div>
    </div>
    
    <!-- Matriz 5x5 -->
    <div class="overflow-x-auto">
        <div class="inline-block min-w-full">
            <div class="flex">
                
                <!-- Etiqueta vertical: PROBABILIDAD -->
                <div class="flex flex-col justify-center items-center pr-3" style="writing-mode: vertical-rl; transform: rotate(180deg);">
                    <span class="text-sm font-bold text-gray-700 tracking-wider">PROBABILIDAD</span>
                </div>
                
                <!-- Tabla -->
                <div class="flex-1">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="w-32 p-2"></th>
                                <?php foreach ($impactos as $imp): ?>
                                <th class="p-2 text-xs font-semibold text-gray-700 text-center"><?php echo $imp; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Invertir orden para que Constante esté arriba
                            $probs_rev = array_reverse($probabilidades);
                            foreach ($probs_rev as $p_idx => $prob): 
                                $prob_index = 4 - $p_idx; // Índice real
                            ?>
                            <tr>
                                <td class="p-2 text-xs font-semibold text-gray-700 text-right pr-4"><?php echo $prob; ?></td>
                                <?php for ($i = 0; $i < 5; $i++): 
                                    // Determinar color según posición
                                    $nivel = $prob_index + $i;
                                    
                                    if ($nivel >= 7) {
                                        $color = 'bg-red-500 hover:bg-red-600';
                                        $text = 'text-white';
                                    } elseif ($nivel >= 5) {
                                        $color = 'bg-orange-400 hover:bg-orange-500';
                                        $text = 'text-white';
                                    } elseif ($nivel >= 3) {
                                        $color = 'bg-yellow-400 hover:bg-yellow-500';
                                        $text = 'text-gray-800';
                                    } else {
                                        $color = 'bg-green-400 hover:bg-green-500';
                                        $text = 'text-white';
                                    }
                                    
                                    // Buscar si hay datos en esta celda
                                    $cantidad = 0;
                                    foreach ($posiciones as $pos) {
                                        if ($pos['prob'] == $prob_index && $pos['imp'] == $i) {
                                            $cantidad += $pos['cantidad'];
                                        }
                                    }
                                ?>
                                <td class="p-1">
                                    <div class="<?php echo $color; ?> <?php echo $text; ?> h-20 flex items-center justify-center rounded cursor-pointer transition-all border-2 border-white hover:border-gray-300">
                                        <?php if ($cantidad > 0): ?>
                                            <span class="text-2xl font-bold"><?php echo $cantidad; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Etiqueta horizontal: IMPACTO -->
                    <div class="text-center mt-2">
                        <span class="text-sm font-bold text-gray-700 tracking-wider">IMPACTO</span>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Leyenda de colores -->
    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="grid grid-cols-4 gap-3 text-xs">
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-green-400 rounded"></div>
                <span class="text-gray-700 font-medium">Bajo (1-2)</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-yellow-400 rounded"></div>
                <span class="text-gray-700 font-medium">Medio (3-4)</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-orange-400 rounded"></div>
                <span class="text-gray-700 font-medium">Alto (5-6)</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-4 h-4 bg-red-500 rounded"></div>
                <span class="text-gray-700 font-medium">Crítico (7+)</span>
            </div>
        </div>
    </div>
    
    <!-- Resumen estadístico -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-green-600">
                <?php 
                $bajos = 0;
                foreach ($posiciones as $pos) {
                    if (($pos['prob'] + $pos['imp']) <= 2) $bajos += $pos['cantidad'];
                }
                echo $bajos;
                ?>
            </p>
            <p class="text-xs text-gray-600 mt-1">Riesgo Bajo</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-yellow-600">
                <?php 
                $medios = 0;
                foreach ($posiciones as $pos) {
                    $nivel = $pos['prob'] + $pos['imp'];
                    if ($nivel >= 3 && $nivel <= 4) $medios += $pos['cantidad'];
                }
                echo $medios;
                ?>
            </p>
            <p class="text-xs text-gray-600 mt-1">Riesgo Medio</p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-orange-600">
                <?php 
                $altos = 0;
                foreach ($posiciones as $pos) {
                    $nivel = $pos['prob'] + $pos['imp'];
                    if ($nivel >= 5 && $nivel <= 6) $altos += $pos['cantidad'];
                }
                echo $altos;
                ?>
            </p>
            <p class="text-xs text-gray-600 mt-1">Riesgo Alto</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-red-600">
                <?php 
                $criticos = 0;
                foreach ($posiciones as $pos) {
                    if (($pos['prob'] + $pos['imp']) >= 7) $criticos += $pos['cantidad'];
                }
                echo $criticos;
                ?>
            </p>
            <p class="text-xs text-gray-600 mt-1">Riesgo Crítico</p>
        </div>
    </div>
    
</div>

<?php
} catch (\Exception $e) {
    error_log('Error en heatmap: ' . $e->getMessage());
    echo '<div class="text-red-600 text-sm p-4">
            <i class="fas fa-exclamation-circle mr-2"></i>
            Error al cargar matriz de riesgo
          </div>';
}
?>