// ==========================================
// JAVASCRIPT PARA EDIÇÃO DE EVENTOS
// Local: public/js/event-edit.js
// ==========================================

class EventEditor {
    constructor() {
        this.form = document.getElementById('editEventForm');
        this.originalData = {};
        this.hasUnsavedChanges = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.captureOriginalData();
        this.setupAutoSave();
        this.setupFormValidation();
        this.setupStatusChangeWarnings();
    }

    bindEvents() {
        // Toggle preço gratuito
        const eventoGratuito = document.getElementById('evento_gratuito');
        const precoSection = document.getElementById('preco_section');
        
        if (eventoGratuito && precoSection) {
            eventoGratuito.addEventListener('change', (e) => {
                precoSection.style.display = e.target.checked ? 'none' : 'block';
                if (e.target.checked) {
                    document.getElementById('preco').value = '';
                }
                this.markAsChanged();
            });
        }

        // Máscara de CEP
        const cepInput = document.getElementById('local_cep');
        if (cepInput) {
            cepInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            });
        }

        // Validação de datas
        const dataInicio = document.getElementById('data_inicio');
        const dataFim = document.getElementById('data_fim');
        
        if (dataInicio && dataFim) {
            dataInicio.addEventListener('change', (e) => {
                dataFim.min = e.target.value;
                if (dataFim.value && dataFim.value < e.target.value) {
                    dataFim.value = e.target.value;
                }
                this.markAsChanged();
            });
        }

        // Detectar mudanças em todos os campos
        const formInputs = this.form.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('change', () => this.markAsChanged());
            input.addEventListener('input', () => this.debounce(() => this.markAsChanged(), 300));
        });

        // Submit do formulário
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Aviso antes de sair da página
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'Você tem alterações não salvas. Tem certeza que deseja sair?';
                return e.returnValue;
            }
        });
    }

    captureOriginalData() {
        const formData = new FormData(this.form);
        this.originalData = Object.fromEntries(formData.entries());
    }

    markAsChanged() {
        this.hasUnsavedChanges = true;
        this.updateSaveButton();
    }

    updateSaveButton() {
        const saveBtn = this.form.querySelector('button[type="submit"]');
        if (saveBtn && this.hasUnsavedChanges) {
            saveBtn.classList.add('btn-warning');
            saveBtn.classList.remove('btn-primary');
            saveBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Salvar Alterações*';
        }
    }

    setupAutoSave() {
        // Auto-save a cada 2 minutos se houver mudanças
        setInterval(() => {
            if (this.hasUnsavedChanges) {
                this.autoSave();
            }
        }, 120000); // 2 minutos
    }

    async autoSave() {
        try {
            const formData = new FormData(this.form);
            formData.append('auto_save', '1');
            
            const response = await fetch('../../api/event-edit.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast('Rascunho salvo automaticamente', 'info', 2000);
                this.hasUnsavedChanges = false;
            }
        } catch (error) {
            console.log('Auto-save failed:', error);
        }
    }

    setupFormValidation() {
        const requiredFields = this.form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldGroup = field.closest('.mb-3');
        
        if (!value && field.hasAttribute('required')) {
            this.showFieldError(field, 'Este campo é obrigatório');
            return false;
        }
        
        // Validações específicas
        switch (field.type) {
            case 'email':
                if (value && !this.isValidEmail(value)) {
                    this.showFieldError(field, 'Email inválido');
                    return false;
                }
                break;
                
            case 'date':
                if (field.id === 'data_inicio' && value) {
                    const hoje = new Date().toISOString().split('T')[0];
                    if (value < hoje) {
                        this.showFieldError(field, 'A data deve ser futura');
                        return false;
                    }
                }
                break;
                
            case 'number':
                if (field.id === 'preco' && value < 0) {
                    this.showFieldError(field, 'Preço deve ser positivo');
                    return false;
                }
                break;
        }
        
        this.clearFieldError(field);
        return true;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    setupStatusChangeWarnings() {
        const statusSelect = document.getElementById('status');
        if (statusSelect) {
            statusSelect.addEventListener('change', (e) => {
                this.handleStatusChange(e.target.value);
            });
        }
    }

    handleStatusChange(newStatus) {
        const currentStatus = this.originalData.status || 'rascunho';
        
        if (newStatus === currentStatus) return;
        
        let warning = '';
        
        switch (newStatus) {
            case 'publicado':
                if (currentStatus === 'rascunho') {
                    warning = 'Ao publicar o evento, ele ficará visível para todos os usuários e eles poderão se inscrever.';
                }
                break;
                
            case 'cancelado':
                warning = 'Ao cancelar o evento, todos os participantes inscritos serão notificados. Esta ação pode afetar a credibilidade do organizador.';
                break;
                
            case 'finalizado':
                warning = 'Ao finalizar o evento, não será mais possível fazer alterações significativas.';
                break;
                
            case 'rascunho':
                if (currentStatus === 'publicado') {
                    warning = 'Ao voltar para rascunho, o evento deixará de ser visível para os participantes.';
                }
                break;
        }
        
        if (warning) {
            this.showToast(warning, 'warning', 5000);
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        // Validar todos os campos
        const isValid = this.validateForm();
        if (!isValid) {
            this.showToast('Por favor, corrija os erros no formulário', 'error');
            return;
        }
        
        const submitBtn = this.form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        try {
            // Mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
            
            const formData = new FormData(this.form);
            
            const response = await fetch(this.form.action || window.location.href, {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.hasUnsavedChanges = false;
                
                // Se a resposta for JSON (API), processar
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showToast('Evento atualizado com sucesso!', 'success');
                        this.captureOriginalData(); // Atualizar dados originais
                        
                        // Atualizar badge de status se mudou
                        this.updateStatusBadge(formData.get('status'));
                        
                        // Reset do botão
                        submitBtn.classList.remove('btn-warning');
                        submitBtn.classList.add('btn-primary');
                        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Alterações';
                    } else {
                        throw new Error(result.message || 'Erro ao salvar');
                    }
                } else {
                    // Recarregar página se for resposta HTML padrão
                    window.location.reload();
                }
            } else {
                throw new Error('Erro na comunicação com o servidor');
            }
            
        } catch (error) {
            this.showToast(error.message, 'error');
            console.error('Erro ao salvar evento:', error);
        } finally {
            submitBtn.disabled = false;
            if (submitBtn.innerHTML.includes('Salvando')) {
                submitBtn.innerHTML = originalText;
            }
        }
    }

    validateForm() {
        const requiredFields = this.form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        // Validações específicas adicionais
        const eventoGratuito = document.getElementById('evento_gratuito');
        const preco = document.getElementById('preco');
        
        if (!eventoGratuito.checked && (!preco.value || preco.value <= 0)) {
            this.showFieldError(preco, 'Preço deve ser informado para eventos pagos');
            isValid = false;
        }
        
        return isValid;
    }

    updateStatusBadge(newStatus) {
        const badge = document.querySelector('.preview-badge .badge');
        if (badge) {
            const statusColors = {
                'rascunho': 'warning',
                'publicado': 'success',
                'cancelado': 'danger',
                'finalizado': 'secondary'
            };
            
            badge.className = `badge bg-${statusColors[newStatus] || 'secondary'} fs-6`;
            badge.innerHTML = `<i class="fas fa-circle me-1"></i>${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}`;
        }
    }

    // Utilitários
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    showToast(message, type = 'info', duration = 4000) {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s ease-out;
        `;
        
        const icons = {
            success: 'fas fa-check-circle',
            info: 'fas fa-info-circle',
            warning: 'fas fa-exclamation-triangle',
            danger: 'fas fa-exclamation-circle',
            error: 'fas fa-exclamation-circle'
        };
        
        toast.innerHTML = `
            <i class="${icons[type] || icons.info} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                const bsAlert = new bootstrap.Alert(toast);
                bsAlert.close();
            }
        }, duration);
    }

    // Métodos públicos para interação externa
    previewEvent() {
        const eventId = new URLSearchParams(window.location.search).get('id');
        if (eventId) {
            window.open(`view.php?id=${eventId}`, '_blank');
        }
    }

    async loadEventData(eventId) {
        try {
            const response = await fetch(`../../api/event-edit.php?id=${eventId}`);
            const result = await response.json();
            
            if (result.success) {
                this.populateForm(result.evento);
                this.captureOriginalData();
                return result;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            this.showToast('Erro ao carregar dados do evento', 'error');
            throw error;
        }
    }

    populateForm(eventData) {
        Object.keys(eventData).forEach(key => {
            const field = this.form.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = eventData[key] == 1;
                } else {
                    field.value = eventData[key] || '';
                }
            }
        });
        
        // Trigger events para campos especiais
        const eventoGratuito = document.getElementById('evento_gratuito');
        if (eventoGratuito) {
            eventoGratuito.dispatchEvent(new Event('change'));
        }
    }

    resetForm() {
        if (confirm('Tem certeza que deseja descartar todas as alterações?')) {
            this.form.reset();
            this.populateForm(this.originalData);
            this.hasUnsavedChanges = false;
            this.updateSaveButton();
        }
    }
}

// Funções globais para compatibilidade
function previewEvent() {
    if (window.eventEditor) {
        window.eventEditor.previewEvent();
    }
}

function resetForm() {
    if (window.eventEditor) {
        window.eventEditor.resetForm();
    }
}

// CSS adicional para animações
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
    
    .form-control.is-invalid:focus,
    .form-select.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .btn-warning.pulse {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
    }
    
    .preview-badge {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(style);

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('editEventForm')) {
        window.eventEditor = new EventEditor();
        console.log('Event Editor initialized');
    }
});

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl+S para salvar
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (window.eventEditor && window.eventEditor.hasUnsavedChanges) {
            document.getElementById('editEventForm').dispatchEvent(new Event('submit'));
        }
    }
    
    // Ctrl+P para preview
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        previewEvent();
    }
    
    // Esc para resetar form (com confirmação)
    if (e.key === 'Escape' && window.eventEditor && window.eventEditor.hasUnsavedChanges) {
        resetForm();
    }
});

export default EventEditor;