/**
 * main.js - JavaScript Principal
 * ISO 27001 Compliance Platform
 */

// Configuración global
const API_BASE = window.location.origin + '/iso_platform/public/api';

// Toast notifications
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg mb-4 flex items-center space-x-3 fade-in`;
    toast.innerHTML = `
        <i class="fas ${icons[type]}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Auto-remove después de 5 segundos
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Función para cargar cards dinámicamente
async function loadCard(cardId, endpoint) {
    const card = document.getElementById(cardId);
    if (!card) return;
    
    const cardBody = card.querySelector('.card-body');
    const cardError = card.querySelector('.card-error');
    
    // Mostrar loading
    cardBody.innerHTML = '<div class="flex justify-center items-center py-8"><div class="spinner"></div></div>';
    
    try {
        const response = await fetch(`${API_BASE}${endpoint}`);
        const data = await response.json();
        
        if (data.success) {
            cardBody.innerHTML = data.html;
        } else {
            throw new Error(data.error || 'Error al cargar datos');
        }
        
    } catch (error) {
        console.error('Error loading card:', error);
        cardError.innerHTML = `
            <div class="text-red-600 text-sm p-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                ${error.message}
            </div>
        `;
        cardError.style.display = 'block';
        cardBody.innerHTML = '';
    }
}

// Confirmación antes de eliminar
function confirmDelete(message = '¿Está seguro de eliminar este elemento?') {
    return confirm(message);
}

// Formatear fecha
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit' 
    });
}

// Validar formulario
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            isValid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });
    
    return isValid;
}

// Event listeners globales
document.addEventListener('DOMContentLoaded', function() {
    console.log('ISO Platform loaded');
});