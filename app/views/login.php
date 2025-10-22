<?php
// La sesión ya está iniciada por security.php en index.php
// Las configuraciones ya están cargadas por index.php

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
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .shake {
            animation: shake 0.5s;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); max-height: 0; }
            to { opacity: 1; transform: translateY(0); max-height: 100px; }
        }
        .slide-down {
            animation: slideDown 0.3s ease-out forwards;
        }
        
        /* Indicador de fortaleza de contraseña */
        .password-strength-bar {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
            background: #e5e7eb;
        }
        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #10b981; width: 100%; }
        
        /* Input estados */
        .input-default {
            border-color: #d1d5db;
        }
        .input-focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .input-success {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        .input-error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }
        
        /* Tooltips */
        .tooltip {
            position: relative;
        }
        .tooltip-text {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            z-index: 100;
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            transition: all 0.3s ease;
        }
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        .tooltip-text::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #1f2937 transparent transparent transparent;
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
            <div class="<?php echo $colores[$tipo]; ?> border-l-4 rounded-lg p-4 mb-6 flex items-start space-x-3 <?php echo $tipo === 'error' ? 'shake' : ''; ?>">
                <i class="fas <?php echo $iconos[$tipo]; ?> mt-0.5"></i>
                <p class="text-sm flex-1"><?php echo $_SESSION['mensaje']; ?></p>
                <button onclick="this.parentElement.remove()" class="text-current opacity-50 hover:opacity-100 transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <?php
                unset($_SESSION['mensaje']);
                unset($_SESSION['mensaje_tipo']);
            endif;
            ?>

            <!-- Formulario -->
            <form method="POST" action="<?php echo BASE_URL; ?>/public/auth/login" id="login-form" novalidate>
                
                <!-- CSRF Token -->
                <?php echo Security::csrfField(); ?>
                
                <!-- Usuario o Email -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-blue-600"></i>
                        Usuario o Correo Electrónico
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" 
                               name="identifier" 
                               id="identifier"
                               required 
                               autocomplete="username"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none transition input-default"
                               placeholder="usuario o correo@ejemplo.com">
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <i id="identifier-icon" class="fas fa-circle text-gray-300 text-xs hidden"></i>
                        </div>
                    </div>
                    <!-- Feedback inline -->
                    <div id="identifier-feedback" class="hidden mt-2 text-xs">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-circle text-xs"></i>
                            <span id="identifier-message"></span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Puede ingresar con su nombre de usuario o correo electrónico
                    </p>
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-blue-600"></i>
                        Contraseña
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="password" 
                               id="password"
                               required 
                               autocomplete="current-password"
                               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none transition input-default"
                               placeholder="••••••••">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 transition tooltip">
                            <i class="fas fa-eye" id="toggle-icon"></i>
                            <span class="tooltip-text">Mostrar contraseña</span>
                        </button>
                    </div>
                    
                    <!-- Barra de fortaleza -->
                    <div class="mt-2">
                        <div class="w-full h-1 bg-gray-200 rounded-full overflow-hidden">
                            <div id="strength-bar" class="password-strength-bar"></div>
                        </div>
                    </div>
                    
                    <!-- Feedback inline -->
                    <div id="password-feedback" class="hidden mt-2 text-xs">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-circle text-xs"></i>
                            <span id="password-message"></span>
                        </div>
                    </div>
                    
                    <!-- Requisitos de contraseña -->
                    <div id="password-requirements" class="mt-3 space-y-1 text-xs hidden">
                        <div id="req-length" class="flex items-center space-x-2 text-gray-500">
                            <i class="fas fa-circle text-xs"></i>
                            <span>Mínimo 6 caracteres</span>
                        </div>
                    </div>
                </div>

                <!-- Recordar sesión -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" 
                               name="remember" 
                               id="remember-checkbox"
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition">
                        <span class="ml-2 text-sm text-gray-600 group-hover:text-gray-900 transition">
                            Recordarme por 30 días
                        </span>
                    </label>
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800 transition hover:underline">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <!-- Botón submit -->
                <button type="submit" 
                        id="submit-btn"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition transform hover:scale-[1.02] active:scale-[0.98] focus:ring-4 focus:ring-blue-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                    <span id="btn-text" class="flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Iniciar Sesión
                    </span>
                    <span id="btn-loading" class="hidden flex items-center justify-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Verificando...
                    </span>
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
        // ========== ELEMENTOS DEL DOM ==========
        const form = document.getElementById('login-form');
        const identifierInput = document.getElementById('identifier');
        const passwordInput = document.getElementById('password');
        const submitBtn = document.getElementById('submit-btn');
        const btnText = document.getElementById('btn-text');
        const btnLoading = document.getElementById('btn-loading');
        
        // Feedback elements
        const identifierFeedback = document.getElementById('identifier-feedback');
        const identifierMessage = document.getElementById('identifier-message');
        const identifierIcon = document.getElementById('identifier-icon');
        
        const passwordFeedback = document.getElementById('password-feedback');
        const passwordMessage = document.getElementById('password-message');
        const strengthBar = document.getElementById('strength-bar');
        const passwordRequirements = document.getElementById('password-requirements');
        const reqLength = document.getElementById('req-length');

        // ========== FUNCIONES DE VALIDACIÓN ==========
        
        // Toggle password visibility
        function togglePassword() {
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

        // Validar identifier (email o username)
        function validateIdentifier() {
            const value = identifierInput.value.trim();
            
            identifierInput.classList.remove('input-error', 'input-success', 'shake');
            identifierFeedback.classList.add('hidden');
            identifierIcon.classList.add('hidden');
            
            if (value.length === 0) {
                identifierInput.classList.add('input-default');
                return false;
            }
            
            const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            const isValidUsername = /^[a-zA-Z0-9_]{3,}$/.test(value);
            
            if (isEmail || isValidUsername) {
                identifierInput.classList.add('input-success');
                identifierInput.classList.remove('input-default');
                identifierIcon.classList.remove('hidden', 'fa-times-circle', 'text-red-500');
                identifierIcon.classList.add('fa-check-circle', 'text-green-500');
                
                identifierFeedback.classList.remove('hidden', 'text-red-600');
                identifierFeedback.classList.add('text-green-600', 'slide-down');
                identifierMessage.textContent = isEmail ? 'Email válido' : 'Usuario válido';
                
                return true;
            } else {
                identifierInput.classList.add('input-error');
                identifierInput.classList.remove('input-default');
                identifierIcon.classList.remove('hidden', 'fa-check-circle', 'text-green-500');
                identifierIcon.classList.add('fa-times-circle', 'text-red-500');
                
                identifierFeedback.classList.remove('hidden', 'text-green-600');
                identifierFeedback.classList.add('text-red-600', 'slide-down');
                
                if (value.includes('@')) {
                    identifierMessage.textContent = 'Formato de email inválido';
                } else {
                    identifierMessage.textContent = 'Usuario debe tener al menos 3 caracteres (a-z, 0-9, _)';
                }
                
                return false;
            }
        }

        // Calcular fortaleza de contraseña
        function calculatePasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            return Math.min(strength, 3); // 0: nada, 1: débil, 2: media, 3: fuerte
        }

        // Validar contraseña
        function validatePassword() {
            const value = passwordInput.value;
            
            passwordInput.classList.remove('input-error', 'input-success', 'shake');
            passwordFeedback.classList.add('hidden');
            strengthBar.className = 'password-strength-bar';
            
            if (value.length === 0) {
                passwordInput.classList.add('input-default');
                passwordRequirements.classList.add('hidden');
                return false;
            }
            
            passwordRequirements.classList.remove('hidden');
            
            // Validar longitud mínima
            if (value.length >= 6) {
                reqLength.classList.remove('text-gray-500');
                reqLength.classList.add('text-green-600');
                reqLength.querySelector('i').classList.remove('fa-circle');
                reqLength.querySelector('i').classList.add('fa-check-circle');
            } else {
                reqLength.classList.remove('text-green-600');
                reqLength.classList.add('text-gray-500');
                reqLength.querySelector('i').classList.remove('fa-check-circle');
                reqLength.querySelector('i').classList.add('fa-circle');
            }
            
            // Calcular fortaleza
            const strength = calculatePasswordStrength(value);
            
            if (value.length < 6) {
                passwordInput.classList.add('input-error');
                passwordInput.classList.remove('input-default');
                
                passwordFeedback.classList.remove('hidden', 'text-green-600');
                passwordFeedback.classList.add('text-red-600', 'slide-down');
                passwordMessage.textContent = 'La contraseña debe tener al menos 6 caracteres';
                
                return false;
            } else {
                passwordInput.classList.add('input-success');
                passwordInput.classList.remove('input-default');
                
                // Mostrar nivel de fortaleza
                if (strength === 1) {
                    strengthBar.classList.add('strength-weak');
                    passwordFeedback.classList.remove('hidden', 'text-green-600');
                    passwordFeedback.classList.add('text-yellow-600', 'slide-down');
                    passwordMessage.textContent = 'Contraseña débil';
                } else if (strength === 2) {
                    strengthBar.classList.add('strength-medium');
                    passwordFeedback.classList.remove('hidden', 'text-yellow-600');
                    passwordFeedback.classList.add('text-blue-600', 'slide-down');
                    passwordMessage.textContent = 'Contraseña media';
                } else if (strength >= 3) {
                    strengthBar.classList.add('strength-strong');
                    passwordFeedback.classList.remove('hidden', 'text-blue-600', 'text-yellow-600');
                    passwordFeedback.classList.add('text-green-600', 'slide-down');
                    passwordMessage.textContent = 'Contraseña fuerte';
                }
                
                return true;
            }
        }

        // ========== EVENT LISTENERS ==========
        
        // Validación en tiempo real para identifier
        identifierInput.addEventListener('input', validateIdentifier);
        identifierInput.addEventListener('blur', validateIdentifier);

        // Validación en tiempo real para password
        passwordInput.addEventListener('input', validatePassword);
        passwordInput.addEventListener('blur', validatePassword);

        // Validación del formulario al enviar
        form.addEventListener('submit', function(e) {
            const isIdentifierValid = validateIdentifier();
            const isPasswordValid = validatePassword();
            
            if (!isIdentifierValid) {
                e.preventDefault();
                identifierInput.classList.add('shake');
                identifierInput.focus();
                setTimeout(() => identifierInput.classList.remove('shake'), 500);
                return false;
            }
            
            if (!isPasswordValid) {
                e.preventDefault();
                passwordInput.classList.add('shake');
                passwordInput.focus();
                setTimeout(() => passwordInput.classList.remove('shake'), 500);
                return false;
            }
            
            // Mostrar loading
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
        });

        // Prevenir submit múltiple
        let isSubmitting = false;
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
        });
    </script>

</body>
</html>