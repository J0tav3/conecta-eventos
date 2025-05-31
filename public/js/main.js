// Conecta Eventos - JavaScript Principal

// Executar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    console.log('Conecta Eventos carregado!');
    
    // Adicionar animações aos cards
    animateCards();
    
    // Configurar formulários
    setupForms();
    
    // Auto-hide alerts
    autoHideAlerts();
});

// Função para animar cards
function animateCards() {
    const cards = document.querySelectorAll('.card');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Função para configurar formulários
function setupForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processando...';
                
                // Reativar após 3 segundos (segurança)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.originalText || 'Enviar';
                }, 3000);
            }
        });
    });
}

// Função para esconder alertas automaticamente
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000); // 5 segundos
    });
}

// Função para confirmar exclusões
function confirmDelete(message = 'Tem certeza que deseja excluir?') {
    return confirm(message);
}

// Função para mostrar toast de sucesso
function showSuccess(message) {
    showToast(message, 'success');
}

// Função para mostrar toast de erro
function showError(message) {
    showToast(message, 'danger');
}

// Função genérica para mostrar toast
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-remover após 3 segundos
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Função para formatar data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

// Função para formatar hora
function formatTime(timeString) {
    return timeString.substr(0, 5); // HH:MM
}