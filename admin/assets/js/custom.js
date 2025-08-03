// Archivo: /creator/admin/assets/js/custom.js
// JavaScript personalizado para el sistema

document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar tooltips (Bootstrap 5)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Cerrar alertas automáticamente después de 5 segundos
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Confirmar acciones peligrosas
    document.querySelectorAll('[data-confirm]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            var message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Actualizar tiempo relativo cada minuto
    setInterval(updateRelativeTimes, 60000);
    
});

// Función para actualizar tiempos relativos
function updateRelativeTimes() {
    document.querySelectorAll('[data-time]').forEach(function(element) {
        var time = element.getAttribute('data-time');
        element.textContent = timeAgo(time);
    });
}

// Función para calcular tiempo relativo
function timeAgo(dateString) {
    var date = new Date(dateString);
    var now = new Date();
    var seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'hace ' + seconds + ' segundos';
    if (seconds < 3600) return 'hace ' + Math.floor(seconds / 60) + ' minutos';
    if (seconds < 86400) return 'hace ' + Math.floor(seconds / 3600) + ' horas';
    if (seconds < 604800) return 'hace ' + Math.floor(seconds / 86400) + ' días';
    
    return date.toLocaleDateString('es-ES');
}

// Función para mostrar notificación toast
function showToast(message, type = 'info') {
    // Implementar si se necesita
}

// Función para formatear números
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}