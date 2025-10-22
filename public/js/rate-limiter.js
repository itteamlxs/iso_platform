/**
 * Rate Limiter - Sistema de limitación de intentos para formularios
 * ISO 27001 Compliance Platform
 * VERSIÓN 1.0
 * 
 * Uso:
 * const limiter = new RateLimiter('login-form', { maxAttempts: 5, lockoutTime: 15 });
 * if (!limiter.canAttempt()) { return; }
 * limiter.recordAttempt();
 */

class RateLimiter {
    /**
     * Constructor
     * @param {string} identifier - Identificador único del formulario/acción
     * @param {object} options - Configuración
     * @param {number} options.maxAttempts - Máximo de intentos permitidos (default: 10)
     * @param {number} options.lockoutTime - Tiempo de bloqueo en minutos (default: 5)
     * @param {boolean} options.showAlert - Mostrar alerta visual (default: true)
     */
    constructor(identifier, options = {}) {
        this.identifier = identifier;
        this.maxAttempts = options.maxAttempts || 20;
        this.lockoutTime = options.lockoutTime || 5; // minutos
        this.showAlert = options.showAlert !== false;
        this.storageKey = `rate_limit_${this.identifier}`;
        
        // Limpiar intentos expirados al inicializar
        this.cleanExpiredAttempts();
    }
    
    /**
     * Obtener datos del rate limiter desde localStorage
     */
    getData() {
        const data = localStorage.getItem(this.storageKey);
        if (!data) {
            return {
                attempts: 0,
                lockedUntil: 0,
                firstAttempt: 0
            };
        }
        return JSON.parse(data);
    }
    
    /**
     * Guardar datos del rate limiter en localStorage
     */
    saveData(data) {
        localStorage.setItem(this.storageKey, JSON.stringify(data));
    }
    
    /**
     * Limpiar intentos expirados
     */
    cleanExpiredAttempts() {
        const data = this.getData();
        const now = Date.now();
        
        // Si el bloqueo expiró, resetear
        if (data.lockedUntil > 0 && data.lockedUntil <= now) {
            this.reset();
            return;
        }
        
        // Si pasaron más de 5 minutos desde el primer intento, resetear contador
        if (data.firstAttempt > 0 && (now - data.firstAttempt) > (this.lockoutTime * 60 * 1000)) {
            this.reset();
        }
    }
    
    /**
     * Verificar si se puede realizar un intento
     * @returns {boolean}
     */
    canAttempt() {
        const data = this.getData();
        const now = Date.now();
        
        // Verificar si está bloqueado
        if (data.lockedUntil > now) {
            const remainingTime = Math.ceil((data.lockedUntil - now) / 1000 / 60);
            
            if (this.showAlert) {
                this.showBlockedAlert(remainingTime);
            }
            
            return false;
        }
        
        // Verificar si excedió el límite
        if (data.attempts >= this.maxAttempts) {
            // Bloquear
            data.lockedUntil = now + (this.lockoutTime * 60 * 1000);
            this.saveData(data);
            
            if (this.showAlert) {
                this.showBlockedAlert(this.lockoutTime);
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Registrar un intento
     */
    recordAttempt() {
        const data = this.getData();
        const now = Date.now();
        
        // Si es el primer intento, registrar timestamp
        if (data.attempts === 0) {
            data.firstAttempt = now;
        }
        
        data.attempts++;
        this.saveData(data);
        
        // Mostrar advertencia si quedan pocos intentos
        const remaining = this.maxAttempts - data.attempts;
        if (remaining > 0 && remaining <= 3 && this.showAlert) {
            this.showWarningAlert(remaining);
        }
    }
    
    /**
     * Resetear contador
     */
    reset() {
        localStorage.removeItem(this.storageKey);
    }
    
    /**
     * Obtener intentos restantes
     * @returns {number}
     */
    getRemainingAttempts() {
        const data = this.getData();
        return Math.max(0, this.maxAttempts - data.attempts);
    }
    
    /**
     * Verificar si está bloqueado
     * @returns {boolean}
     */
    isLocked() {
        const data = this.getData();
        return data.lockedUntil > Date.now();
    }
    
    /**
     * Obtener tiempo restante de bloqueo en minutos
     * @returns {number}
     */
    getRemainingLockTime() {
        const data = this.getData();
        const now = Date.now();
        
        if (data.lockedUntil <= now) {
            return 0;
        }
        
        return Math.ceil((data.lockedUntil - now) / 1000 / 60);
    }
    
    /**
     * Mostrar alerta de bloqueo
     */
    showBlockedAlert(minutes) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'rate-limit-alert rate-limit-blocked';
        alertDiv.innerHTML = `
            <div class="rate-limit-alert-content">
                <i class="fas fa-ban"></i>
                <div>
                    <strong>Demasiados intentos</strong>
                    <p>Por favor, espere ${minutes} minuto(s) antes de intentar nuevamente.</p>
                </div>
            </div>
        `;
        
        this.insertAlert(alertDiv);
    }
    
    /**
     * Mostrar alerta de advertencia
     */
    showWarningAlert(remaining) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'rate-limit-alert rate-limit-warning';
        alertDiv.innerHTML = `
            <div class="rate-limit-alert-content">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Advertencia</strong>
                    <p>Le quedan ${remaining} intento(s) antes del bloqueo temporal.</p>
                </div>
            </div>
        `;
        
        this.insertAlert(alertDiv);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => alertDiv.remove(), 5000);
    }
    
    /**
     * Insertar alerta en el DOM
     */
    insertAlert(alertDiv) {
        // Remover alertas previas
        document.querySelectorAll('.rate-limit-alert').forEach(el => el.remove());
        
        // Insertar nueva alerta
        document.body.insertAdjacentElement('afterbegin', alertDiv);
        
        // Animación de entrada
        setTimeout(() => alertDiv.classList.add('show'), 10);
    }
}

// Estilos CSS para las alertas (se inyectan automáticamente)
if (!document.getElementById('rate-limiter-styles')) {
    const styles = document.createElement('style');
    styles.id = 'rate-limiter-styles';
    styles.textContent = `
        .rate-limit-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 16px 20px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease-out;
        }
        
        .rate-limit-alert.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        .rate-limit-alert-content {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .rate-limit-alert-content i {
            font-size: 24px;
            margin-top: 2px;
        }
        
        .rate-limit-alert-content strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .rate-limit-alert-content p {
            font-size: 13px;
            margin: 0;
            line-height: 1.4;
        }
        
        .rate-limit-blocked {
            border-left: 4px solid #ef4444;
        }
        
        .rate-limit-blocked i {
            color: #ef4444;
        }
        
        .rate-limit-blocked strong {
            color: #991b1b;
        }
        
        .rate-limit-blocked p {
            color: #6b7280;
        }
        
        .rate-limit-warning {
            border-left: 4px solid #f59e0b;
        }
        
        .rate-limit-warning i {
            color: #f59e0b;
        }
        
        .rate-limit-warning strong {
            color: #92400e;
        }
        
        .rate-limit-warning p {
            color: #6b7280;
        }
        
        @media (max-width: 640px) {
            .rate-limit-alert {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    `;
    document.head.appendChild(styles);
}

// Exportar para uso global
window.RateLimiter = RateLimiter;