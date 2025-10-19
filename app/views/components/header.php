<?php
// Cargar config BD
require_once __DIR__ . '/../../../app/config/database.php';

// Cargar autoload
require_once __DIR__ . '/../../../vendor/autoload.php';

// Obtener empresa actual
require_once __DIR__ . '/../../../app/models/Empresa.php';
$empresa_model = new \App\Models\Empresa();
$empresa_id = isset($_SESSION['empresa_id']) ? $_SESSION['empresa_id'] : 1;
$empresa = $empresa_model->obtenerPorId($empresa_id);
$nombre_empresa = $empresa ? htmlspecialchars($empresa['nombre']) : 'Sin empresa';

// Obtener usuario de sesión
$usuario_nombre = isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Admin User';
$usuario_rol = isset($_SESSION['usuario_rol']) ? htmlspecialchars($_SESSION['usuario_rol']) : 'Administrador';
$usuario_iniciales = strtoupper(substr($usuario_nombre, 0, 1) . (strpos($usuario_nombre, ' ') !== false ? substr(explode(' ', $usuario_nombre)[1], 0, 1) : ''));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Views CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/views.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Configuración Tailwind personalizada -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#64748b',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

<!-- Header fijo -->
<header class="header-fixed bg-white border-b border-gray-200 shadow-sm">
    <div class="flex items-center justify-between h-full px-6">
        
        <!-- Logo y título -->
        <div class="flex items-center space-x-4">
            <button id="sidebar-toggle" class="lg:hidden text-gray-600 hover:text-gray-900">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-xl font-semibold text-gray-800">
                <?php echo $page_title ?? 'Dashboard'; ?>
            </h1>
        </div>

        <!-- Acciones de header -->
        <div class="flex items-center space-x-4">
            
            <!-- Empresa actual -->
            <div class="hidden md:flex items-center space-x-2 px-4 py-2 bg-blue-50 rounded-lg">
                <i class="fas fa-building text-blue-600"></i>
                <span class="text-sm font-medium text-gray-700"><?php echo $nombre_empresa; ?></span>
            </div>

            <!-- Notificaciones -->
            <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">
                <i class="fas fa-bell text-xl"></i>
                <span class="notification-badge bg-red-500 text-white rounded-full text-xs">3</span>
            </button>

            <!-- Usuario -->
            <div class="flex items-center space-x-3">
                <div class="hidden md:block text-right">
                    <p class="text-sm font-medium text-gray-700"><?php echo $usuario_nombre; ?></p>
                    <p class="text-xs text-gray-500"><?php echo ucfirst(str_replace('_', ' ', $usuario_rol)); ?></p>
                </div>
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                    <?php echo $usuario_iniciales; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<?php
// Mostrar mensajes de sesión si existen
if (isset($_SESSION['mensaje'])):
    $tipo = $_SESSION['mensaje_tipo'] ?? 'info';
    $colores = [
        'success' => 'bg-green-50 border-green-500 text-green-800',
        'error' => 'bg-red-50 border-red-500 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-500 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-500 text-blue-800'
    ];
    $iconos = [
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle'
    ];
?>
<div class="fixed top-20 right-6 z-50 max-w-md animate-slide-in">
    <div class="<?php echo $colores[$tipo]; ?> border-l-4 rounded-lg shadow-lg p-4 flex items-start space-x-3">
        <i class="fas <?php echo $iconos[$tipo]; ?> text-xl mt-0.5"></i>
        <div class="flex-1">
            <p class="text-sm font-medium"><?php echo $_SESSION['mensaje']; ?></p>
        </div>
        <button onclick="this.parentElement.parentElement.remove()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
<script>
    // Auto-remover mensaje después de 5 segundos
    setTimeout(function() {
        const alert = document.querySelector('.animate-slide-in');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(100%)';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
</script>
<?php
    unset($_SESSION['mensaje']);
    unset($_SESSION['mensaje_tipo']);
endif;
?>