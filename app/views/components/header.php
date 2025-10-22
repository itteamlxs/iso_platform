<?php
// Obtener datos del usuario autenticado
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuario_rol = $_SESSION['usuario_rol'] ?? 'invitado';
$usuario_email = $_SESSION['usuario_email'] ?? '';

// Generar iniciales
$nombres = explode(' ', $usuario_nombre);
$usuario_iniciales = '';
foreach ($nombres as $nombre) {
    if (!empty($nombre)) {
        $usuario_iniciales .= strtoupper(substr($nombre, 0, 1));
        if (strlen($usuario_iniciales) >= 2) break;
    }
}
if (empty($usuario_iniciales)) {
    $usuario_iniciales = 'U';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'ISO 27001 Platform'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/views.css">
</head>
<body class="bg-gray-50">

<!-- Header fijo -->
<header class="header-fixed bg-white shadow-sm border-b border-gray-200">
    <div class="h-full px-6 flex items-center justify-between">
        
        <!-- Título de sección -->
        <div>
            <h1 class="text-xl font-semibold text-gray-800"><?php echo $page_title ?? 'Dashboard'; ?></h1>
        </div>

        <!-- Usuario -->
        <div class="flex items-center space-x-3">
            <div class="hidden md:block text-right">
                <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($usuario_nombre); ?></p>
                <p class="text-xs text-gray-500"><?php echo ucfirst(str_replace('_', ' ', $usuario_rol)); ?></p>
            </div>
            <div class="relative">
                <button id="user-menu-button" 
                        class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm cursor-pointer hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <?php echo $usuario_iniciales; ?>
                </button>
                
                <!-- Dropdown menu -->
                <div id="user-menu-dropdown" 
                     class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                    <!-- Info del usuario -->
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($usuario_nombre); ?></p>
                        <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($usuario_email); ?></p>
                    </div>
                    
                    <!-- Opciones -->
                    <a href="<?php echo BASE_URL; ?>/public/perfil" 
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                        <i class="fas fa-user mr-2 text-gray-400"></i>Mi Perfil
                    </a>
                    <a href="<?php echo BASE_URL; ?>/public/configuracion" 
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                        <i class="fas fa-cog mr-2 text-gray-400"></i>Configuración
                    </a>
                    
                    <!-- Separador -->
                    <div class="border-t border-gray-100 my-1"></div>
                    
                    <!-- Cerrar sesión -->
                    <a href="<?php echo BASE_URL; ?>/public/logout" 
                       class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
        
    </div>
</header>

<script>
// Toggle del menú de usuario
const userButton = document.getElementById('user-menu-button');
const userDropdown = document.getElementById('user-menu-dropdown');

userButton.addEventListener('click', function(e) {
    e.stopPropagation();
    userDropdown.classList.toggle('hidden');
});

// Cerrar dropdown al hacer click fuera
document.addEventListener('click', function(e) {
    if (!userButton.contains(e.target) && !userDropdown.contains(e.target)) {
        userDropdown.classList.add('hidden');
    }
});

// Prevenir que el dropdown se cierre al hacer click dentro de él
userDropdown.addEventListener('click', function(e) {
    e.stopPropagation();
});
</script>