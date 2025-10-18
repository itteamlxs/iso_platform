<?php
/**
 * Descargar/Visualizar evidencias
 */

require_once __DIR__ . '/../app/config/database.php';

$file = $_GET['file'] ?? null;

if (!$file) {
    mostrarError('Archivo no especificado');
}

// Sanitizar nombre de archivo
$file = basename($file);
$filepath = UPLOAD_PATH . '/' . $file;

// Verificar que existe
if (!file_exists($filepath)) {
    mostrarError('El archivo solicitado no fue encontrado en el servidor');
}

// Obtener extensión
$extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

// Verificar que sea un tipo permitido
if (!in_array($extension, UPLOAD_ALLOWED_TYPES)) {
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

// Headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filepath));
header('Content-Disposition: inline; filename="' . basename($file) . '"');
header('Cache-Control: private, max-age=3600');

// Leer y enviar archivo
readfile($filepath);
exit;

/**
 * Mostrar modal de error
 */
function mostrarError($mensaje) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Archivo no encontrado</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    Archivo no disponible
                </h2>
                
                <!-- Mensaje -->
                <p class="text-gray-600 text-center mb-8">
                    <?php echo htmlspecialchars($mensaje); ?>
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
            // Intentar cerrar automáticamente después de 10 segundos si no hay interacción
            setTimeout(function() {
                const confirmed = confirm('¿Desea cerrar esta ventana?');
                if (confirmed) {
                    window.close();
                    // Si no se puede cerrar, volver atrás
                    setTimeout(function() {
                        window.history.back();
                    }, 100);
                }
            }, 10000);
        </script>
        
    </body>
    </html>
    <?php
    exit;
}
?>