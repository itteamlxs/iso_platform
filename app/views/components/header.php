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