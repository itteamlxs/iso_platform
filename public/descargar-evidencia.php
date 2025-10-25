<?php
/**
 * Descargar/Visualizar evidencias
 * VERSIÓN 3.0 - Enhanced security with IDOR protection
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/security.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/helpers/Logger.php';
require_once __DIR__ . '/../app/middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

use App\Helpers\Security;
use App\Helpers\Logger;
use App\Middleware\RateLimitMiddleware;
use App\Controllers\AuthController;

// Verificar autenticación
AuthController::requireAuth();

// Rate limiting para descargas
$identifier = $_SESSION['usuario_id'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

if (!RateLimitMiddleware::check('download', $identifier)) {
    Logger::security('Download rate limit exceeded', [
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $blockInfo = RateLimitMiddleware::isBlocked('download', $identifier);
    mostrarError('Demasiadas descargas. Espere ' . $blockInfo['time_left'] . ' minuto(s).');
}

// Registrar intento de descarga
RateLimitMiddleware::record('download', $identifier);

$file = $_GET['file'] ?? null;

if (!$file) {
    Logger::warning('Download attempt without file parameter', [
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    mostrarError('Archivo no especificado');
}

// Sanitizar nombre de archivo (prevenir path traversal)
$file = basename($file);

// Validación adicional: solo caracteres alfanuméricos, guiones, puntos y underscores
if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
    Logger::security('Download attempt with invalid filename', [
        'file' => $file,
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    mostrarError('Nombre de archivo inválido');
}

// Usar Security helper para prevenir path traversal
$filepath = Security::sanitizeFilePath($file, UPLOAD_PATH);

if ($filepath === false) {
    Logger::security('Path traversal attempt detected', [
        'file' => $file,
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    mostrarError('Ruta de archivo inválida');
}

// Verificar que existe
if (!file_exists($filepath)) {
    Logger::warning('Download attempt for non-existent file', [
        'file' => $file,
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    mostrarError('El archivo solicitado no fue encontrado en el servidor');
}

// IDOR PROTECTION: Verificar ownership del archivo
try {
    $db = \App\Models\Database::getInstance()->getConnection();
    
    // Obtener información de la evidencia asociada al archivo
    $sql = "SELECT e.empresa_id, e.control_id, c.codigo, c.nombre, e.descripcion
            FROM evidencias e
            INNER JOIN controles c ON e.control_id = c.id
            WHERE e.archivo = :archivo
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':archivo' => $file]);
    $evidencia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evidencia) {
        Logger::security('Download attempt for file not in database', [
            'file' => $file,
            'user_id' => $_SESSION['usuario_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        mostrarError('Archivo no encontrado en el sistema');
    }
    
    // Validar ownership: el archivo debe pertenecer a la empresa del usuario
    if (!Security::validateOwnership($evidencia['empresa_id'])) {
        Logger::security('IDOR attempt: user tried to access file from another company', [
            'file' => $file,
            'user_id' => $_SESSION['usuario_id'] ?? null,
            'user_empresa_id' => $_SESSION['empresa_id'] ?? null,
            'file_empresa_id' => $evidencia['empresa_id'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        mostrarError('No tiene permisos para acceder a este archivo');
    }
    
} catch (\Exception $e) {
    Logger::error('Error validating file ownership', [
        'file' => $file,
        'error' => $e->getMessage(),
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    mostrarError('Error al validar permisos del archivo');
}

// Obtener extensión
$extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

// Verificar que sea un tipo permitido
if (!in_array($extension, UPLOAD_ALLOWED_TYPES)) {
    Logger::security('Download attempt for disallowed file type', [
        'file' => $file,
        'extension' => $extension,
        'user_id' => $_SESSION['usuario_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    mostrarError('Tipo de archivo no permitido');
}

// MIME types
$mimes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg'
];

$mime = $mimes[$extension] ?? 'application/octet-stream';

// Logging de descarga exitosa
Logger::info('File downloaded successfully', [
    'file' => $file,
    'control_codigo' => $evidencia['codigo'],
    'control_nombre' => $evidencia['nombre'],
    'user_id' => $_SESSION['usuario_id'] ?? null,
    'user_email' => $_SESSION['usuario_email'] ?? null,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'size' => filesize($filepath)
]);

// Headers de seguridad adicionales
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

// Headers de descarga
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filepath));
header('Content-Disposition: inline; filename="' . basename($file) . '"');
header('Cache-Control: private, max-age=3600, must-revalidate');
header('Pragma: private');

// Prevenir ejecución de scripts en archivos descargados
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; sandbox');

// Limpiar buffer de salida
if (ob_get_level()) {
    ob_end_clean();
}

// Leer y enviar archivo en chunks (para archivos grandes)
$handle = fopen($filepath, 'rb');
if ($handle === false) {
    Logger::error('Failed to open file for reading', [
        'file' => $file,
        'user_id' => $_SESSION['usuario_id'] ?? null
    ]);
    mostrarError('Error al leer el archivo');
}

while (!feof($handle)) {
    echo fread($handle, 8192);
    flush();
}

fclose($handle);
exit;

/**
 * Mostrar modal de error con seguridad mejorada
 */
function mostrarError($mensaje) {
    // Sanitizar mensaje para prevenir XSS
    $mensaje = htmlspecialchars($mensaje, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Acceso Denegado</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com;">
    </head>
    <body class="bg-gray-900 bg-opacity-50">
        
        <div class="min-h-screen flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-8 animate-fade-in">
                
                <!-- Icono de error -->
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-red-600 text-4xl"></i>
                    </div>
                </div>
                
                <!-- Título -->
                <h2 class="text-2xl font-bold text-gray-900 text-center mb-4">
                    Acceso Denegado
                </h2>
                
                <!-- Mensaje -->
                <p class="text-gray-600 text-center mb-8">
                    <?php echo $mensaje; ?>
                </p>
                
                <!-- Botones -->
                <div class="space-y-3">
                    <button onclick="window.close()" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition font-medium">
                        <i class="fas fa-times mr-2"></i>Cerrar ventana
                    </button>
                    
                    <button onclick="window.history.back()" 
                            class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg transition font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>Volver atrás
                    </button>
                </div>
                
            </div>
        </div>
        
        <style>
            @keyframes fade-in {
                from {
                    opacity: 0;
                    transform: scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
            
            .animate-fade-in {
                animation: fade-in 0.3s ease-out;
            }
        </style>
        
        <script>
            // Auto-cerrar después de 15 segundos de inactividad
            setTimeout(function() {
                const confirmed = confirm('¿Desea cerrar esta ventana?');
                if (confirmed) {
                    window.close();
                    // Si no se puede cerrar, volver atrás
                    setTimeout(function() {
                        window.history.back();
                    }, 100);
                }
            }, 15000);
        </script>
        
    </body>
    </html>
    <?php
    exit;
}
?>