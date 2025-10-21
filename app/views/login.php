<?php
// Redirigir si ya está autenticado
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: ' . BASE_URL . '/public/');
    exit;
}

// Generar token CSRF
require_once __DIR__ . '/../helpers/Security.php';
$csrf_token = \App\Helpers\Security::generateCSRFToken();

// Obtener mensaje de sesión si existe
$mensaje = $_SESSION['mensaje'] ?? null;
$mensaje_tipo = $_SESSION['mensaje_tipo'] ?? null;
unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISO 27001 Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/auth.css">
</head>
<body>
    
    <div class="auth-container">
        <div class="auth-card">
            
            <!-- Logo y título -->
            <div class="text-center mb-8">
                <div class="auth-logo mx-auto">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1 class="auth-title">ISO 27001 Platform</h1>
                <p class="auth-subtitle">Inicia sesión para continuar</p>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
            <div class="auth-alert <?php echo $mensaje_tipo === 'error' ? 'auth-alert-error' : ($mensaje_tipo === 'warning' ? 'auth-alert-warning' : 'auth-alert-success'); ?>">
                <i class="fas <?php echo $mensaje_tipo === 'error' ? 'fa-exclamation-circle' : ($mensaje_tipo === 'warning' ? 'fa-exclamation-triangle' : 'fa-check-circle'); ?>"></i>
                <div class="auth-alert-content">
                    <?php echo $mensaje; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Formulario de login -->
            <form method="POST" action="<?php echo BASE_URL; ?>/public/auth/login" id="login-form">
                
                <?php echo \App\Helpers\Security::csrfField(); ?>
                
                <!-- Usuario o Email -->
                <div class="auth-form-group">
                    <label class="auth-form-label">
                        <i class="fas fa-user"></i>
                        Usuario o Email
                        <span class="required">*</span>
                    </label>
                    <div class="auth-input-group">
                        <input type="text" 
                               name="login" 
                               id="login"
                               class="auth-input" 
                               placeholder="usuario o email@empresa.com"
                               required
                               autofocus>
                        <i class="auth-input-icon fas fa-user"></i>
                    </div>
                    <div class="auth-input-validation" id="login-validation"></div>
                </div>

                <!-- Password -->
                <div class="auth-form-group">
                    <label class="auth-form-label">
                        <i class="fas fa-lock"></i>
                        Contraseña
                        <span class="required">*</span>
                    </label>
                    <div class="auth-input-group">
                        <input type="password" 
                               name="password" 
                               id="password"
                               class="auth-input" 
                               placeholder="••••••••"
                               required>
                        <i class="auth-input-icon fas fa-lock"></i>
                    </div>
                    <div class="auth-input-validation" id="password-validation"></div>
                </div>

                <!-- Recordarme -->
                <div class="auth-checkbox-group">
                    <input type="checkbox" name="remember" id="remember" class="auth-checkbox">
                    <label for="remember" class="auth-checkbox-label">Mantener sesión iniciada</label>
                </div>

                <!-- Botón submit -->
                <button type="submit" class="auth-btn auth-btn-primary" id="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Iniciar Sesión</span>
                </button>

            </form>

            <!-- Footer -->
            <div class="auth-footer">
                <p class="auth-footer-text">ISO 27001:2022 Compliance Platform</p>
                <p class="auth-footer-text">Version <?php echo APP_VERSION; ?></p>
            </div>

        </div>
    </div>

    <!-- JavaScript para validación en tiempo real -->
    <script>
        const loginInput = document.getElementById('login');
        const passwordInput = document.getElementById('password');
        const loginBtn = document.getElementById('login-btn');
        const loginForm = document.getElementById('login-form');

        // Validar login en tiempo real
        loginInput.addEventListener('input', function() {
            const validation = document.getElementById('login-validation');
            
            if (this.value.length === 0) {
                this.classList.remove('error', 'success');
                validation.classList.remove('show');
                return;
            }
            
            if (this.value.length >= 3) {
                this.classList.remove('error');
                this.classList.add('success');
                validation.innerHTML = '<i class="fas fa-check-circle"></i> Usuario válido';
                validation.className = 'auth-input-validation success show';
            } else {
                this.classList.remove('success');
                this.classList.add('error');
                validation.innerHTML = '<i class="fas fa-times-circle"></i> Mínimo 3 caracteres';
                validation.className = 'auth-input-validation error show';
            }
        });

        // Validar password en tiempo real
        passwordInput.addEventListener('input', function() {
            const validation = document.getElementById('password-validation');
            
            if (this.value.length === 0) {
                this.classList.remove('error', 'success');
                validation.classList.remove('show');
                return;
            }
            
            if (this.value.length >= 6) {
                this.classList.remove('error');
                this.classList.add('success');
                validation.innerHTML = '<i class="fas fa-check-circle"></i> Contraseña válida';
                validation.className = 'auth-input-validation success show';
            } else {
                this.classList.remove('success');
                this.classList.add('error');
                validation.innerHTML = '<i class="fas fa-times-circle"></i> Mínimo 6 caracteres';
                validation.className = 'auth-input-validation error show';
            }
        });

        // Loading state en submit
        loginForm.addEventListener('submit', function() {
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        });

        // Enter key para submit
        [loginInput, passwordInput].forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    loginForm.submit();
                }
            });
        });
    </script>

</body>
</html>