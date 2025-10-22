<?php
// Cargar seguridad y configuración
require_once __DIR__ . '/../../app/config/security.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar Security helper
require_once __DIR__ . '/../../app/helpers/Security.php';

// Cargar middleware
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

use App\Middleware\AuthMiddleware;
use App\Helpers\Security;

// Si ya está autenticado, redirigir
AuthMiddleware::redirectIfAuthenticated();

// Generar token CSRF
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Iniciar Sesión - <?php echo APP_NAME; ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-50 min-h-screen flex items-center justify-center p-4">

    <!-- Contenedor principal -->
    <div class="w-full max-w-md animate-fade-in">
        
        <!-- Logo y título -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-600 rounded-2xl shadow-lg mb-4">
                <i class="fas fa-shield-alt text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">ISO 27001</h1>
            <p class="text-gray-600">Compliance Platform</p>
        </div>

        <!-- Card de login -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Iniciar Sesión</h2>
            
            <!-- Mensajes -->
            <?php if (isset($_SESSION['mensaje'])): 
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
            <div class="<?php echo $colores[$tipo]; ?> border-l-4 rounded-lg p-4 mb-6 flex items-start space-x-3">
                <i class="fas <?php echo $iconos[$tipo]; ?> mt-0.5"></i>
                <p class="text-sm"><?php echo $_SESSION['mensaje']; ?></p>
            </div>
            <?php
                unset($_SESSION['mensaje']);
                unset($_SESSION['mensaje_tipo']);
            endif;
            ?>

            <!-- Formulario -->
            <form method="POST" action="<?php echo BASE_URL; ?>/public/auth/login" id="login-form">
                
                <!-- CSRF Token -->
                <?php echo Security::csrfField(); ?>
                
                <!-- Email -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-blue-600"></i>
                        Correo Electrónico
                    </label>
                    <input type="email" 
                           name="email" 
                           required 
                           autocomplete="email"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           placeholder="usuario@ejemplo.com">
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-blue-600"></i>
                        Contraseña
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="password" 
                               id="password"
                               required 
                               autocomplete="current-password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="••••••••">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye" id="toggle-icon"></i>
                        </button>
                    </div>
                </div>

                <!-- Recordar sesión -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="remember" 
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                    </label>
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800">¿Olvidaste tu contraseña?</a>
                </div>

                <!-- Botón submit -->
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition transform hover:scale-105 focus:ring-4 focus:ring-blue-200">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Iniciar Sesión
                </button>

            </form>

        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-gray-600">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?></p>
            <p class="mt-1">v<?php echo APP_VERSION; ?></p>
        </div>

    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Validación básica del formulario
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const email = document.querySelector('input[name="email"]').value;
            const password = document.querySelector('input[name="password"]').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Por favor complete todos los campos');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
        });
    </script>

</body>
</html>