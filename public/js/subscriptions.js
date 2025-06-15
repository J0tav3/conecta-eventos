/**
 * Sistema de Inscrições em Eventos - JavaScript Atualizado
 * Local: public/js/subscriptions.js
 */

class EventSubscriptionSystem {
    constructor() {
        this.apiUrl = '/api/subscriptions.php';
        this.isProcessing = false;
        this.init();
    }

    init() {
        this.bindEvents();
        
        // Verificar se há estado local primeiro
        const eventId = this.getEventId();
        if (eventId && this.isUserLoggedIn()) {
            // Sempre verificar o servidor para ter dados atualizados
            this.checkSubscriptionStatus();
        }
        
        // Iniciar timer para garantir persistência visual
        this.startPersistenceTimer();
        
        console.log('Sistema de Inscrições inicializado');
    }

    startPersistenceTimer() {
        // Verificar estado visual a cada 2 segundos para garantir persistência
        setInterval(() => {
            this.enforceVisualState();
        }, 2000);
        
        console.log('Timer de persistência iniciado');
    }

    bindEvents() {
        // Botão de inscrição
        const subscribeBtn = document.getElementById('subscribe-btn');
        if (subscribeBtn) {
            subscribeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleSubscription();
            });
        }

        // Botão de cancelar inscrição
        const unsubscribeBtn = document.getElementById('unsubscribe-btn');
        if (unsubscribeBtn) {
            unsubscribeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleUnsubscription();
            });
        }

        // Form de inscrição (se existir)
        const subscriptionForm = document.getElementById('subscription-form');
        if (subscriptionForm) {
            subscriptionForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubscriptionForm(e.target);
            });
        }
    }

    async checkSubscriptionStatus() {
        const eventId = this.getEventId();
        if (!eventId) {
            console.log('Event ID não encontrado');
            return;
        }

        // Verificar se usuário está logado antes de verificar status
        if (!this.isUserLoggedIn()) {
            console.log('Usuário não está logado, não verificando status');
            return;
        }

        try {
            console.log('Verificando status de inscrição para evento:', eventId);
            
            const response = await fetch(`${this.apiUrl}?event_id=${eventId}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });

            if (!response.ok) {
                if (response.status === 401) {
                    console.log('Usuário não autenticado');
                    return;
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log('Status de inscrição recebido:', result);
            
            if (result.success) {
                // Sempre atualizar com dados do servidor
                this.updateSubscriptionUI(result.subscribed, result.data);
                
                // Salvar/atualizar status no sessionStorage
                sessionStorage.setItem(`subscription_status_${eventId}`, JSON.stringify({
                    subscribed: result.subscribed,
                    data: result.data,
                    timestamp: Date.now()
                }));
                
                // Se o usuário está inscrito, garantir que a UI reflita isso
                if (result.subscribed && result.data) {
                    console.log('Usuário confirmado como inscrito, atualizando UI');
                    this.ensureSubscribedState(result.data);
                }
            } else {
                console.warn('Erro ao verificar status:', result.message);
            }
        } catch (error) {
            console.error('Erro ao verificar status de inscrição:', error);
            
            // Tentar usar dados do sessionStorage como fallback
            this.loadStatusFromCache(eventId);
        }
    }

    loadStatusFromCache(eventId) {
        try {
            const cached = sessionStorage.getItem(`subscription_status_${eventId}`);
            if (cached) {
                const statusData = JSON.parse(cached);
                
                // Verificar se cache não é muito antigo (5 minutos)
                if (Date.now() - statusData.timestamp < 5 * 60 * 1000) {
                    console.log('Carregando status do cache:', statusData);
                    this.updateSubscriptionUI(statusData.subscribed, statusData.data);
                    return true;
                }
            }
        } catch (error) {
            console.error('Erro ao carregar cache:', error);
        }
        return false;
    }

    // Garantir que o estado de inscrito seja mantido
    ensureSubscribedState(data) {
        const subscribeBtn = document.getElementById('subscribe-btn');
        const unsubscribeBtn = document.getElementById('unsubscribe-btn');
        const subscriptionStatus = document.getElementById('subscription-status');
        const subscriptionCard = document.querySelector('.subscription-card');

        // Forçar estado visual de inscrito
        if (subscribeBtn) {
            subscribeBtn.style.display = 'none';
        }
        
        if (unsubscribeBtn) {
            unsubscribeBtn.style.display = 'inline-block';
        }

        if (subscriptionStatus && !subscriptionStatus.innerHTML.includes('inscrito')) {
            const dataInscricao = data && data.data_inscricao ? 
                new Date(data.data_inscricao).toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : '';
            
            subscriptionStatus.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Você está inscrito neste evento!</strong>
                    ${dataInscricao ? `<br><small>Inscrito em: ${dataInscricao}</small>` : ''}
                    ${data && data.status ? `<br><small>Status: ${data.status}</small>` : ''}
                </div>
            `;
        }

        if (subscriptionCard) {
            subscriptionCard.classList.add('subscribed');
            
            const cardTitle = subscriptionCard.querySelector('h5');
            if (cardTitle && !cardTitle.textContent.includes('Inscrito')) {
                cardTitle.innerHTML = '<i class="fas fa-check-circle me-2"></i>Você está Inscrito!';
            }
        }
    }

    async handleSubscription() {
        if (this.isProcessing) {
            console.log('Já processando uma inscrição');
            return;
        }

        const eventId = this.getEventId();
        if (!eventId) {
            this.showToast('ID do evento não encontrado', 'error');
            return;
        }

        // Verificar se usuário está logado
        if (!this.isUserLoggedIn()) {
            this.showToast('Você precisa fazer login para se inscrever', 'warning');
            setTimeout(() => {
                window.location.href = '../auth/login.php';
            }, 2000);
            return;
        }

        this.isProcessing = true;
        this.setLoadingState(true);

        try {
            console.log('Iniciando inscrição para evento:', eventId);
            
            const formData = new FormData();
            formData.append('event_id', eventId);
            
            // Observações (se houver campo)
            const observacoesInput = document.getElementById('observacoes');
            if (observacoesInput) {
                formData.append('observacoes', observacoesInput.value);
            }

            const response = await fetch(this.apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log('Resultado da inscrição:', result);
            
            if (result.success) {
                this.showToast(result.message, 'success');
                this.updateSubscriptionUI(true, result.data);
                
                // Atualizar contador de participantes
                this.updateParticipantCount(1);
                
                // Salvar status no sessionStorage
                const eventId = this.getEventId();
                sessionStorage.setItem(`subscription_status_${eventId}`, JSON.stringify({
                    subscribed: true,
                    data: result.data,
                    timestamp: Date.now()
                }));
                
                // Disparar evento customizado
                this.dispatchEvent('subscriptionSuccess', result.data);
            } else {
                this.showToast(result.message, 'error');
            }

        } catch (error) {
            console.error('Erro na inscrição:', error);
            this.showToast('Erro de conexão. Tente novamente.', 'error');
        } finally {
            this.isProcessing = false;
            this.setLoadingState(false);
        }
    }

    async handleUnsubscription() {
        if (this.isProcessing) return;

        const eventId = this.getEventId();
        if (!eventId) return;

        // Confirmação
        if (!confirm('Tem certeza que deseja cancelar sua inscrição neste evento?')) {
            return;
        }

        this.isProcessing = true;
        this.setLoadingState(true, 'Cancelando...');

        try {
            console.log('Cancelando inscrição para evento:', eventId);
            
            const response = await fetch(this.apiUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ event_id: eventId })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            console.log('Resultado do cancelamento:', result);
            
            if (result.success) {
                this.showToast(result.message, 'success');
                this.updateSubscriptionUI(false, null);
                
                // Atualizar contador de participantes
                this.updateParticipantCount(-1);
                
                // Remover status do sessionStorage
                const eventId = this.getEventId();
                sessionStorage.removeItem(`subscription_status_${eventId}`);
                
                // Disparar evento customizado
                this.dispatchEvent('unsubscriptionSuccess', result.data);
            } else {
                this.showToast(result.message, 'error');
            }

        } catch (error) {
            console.error('Erro ao cancelar inscrição:', error);
            this.showToast('Erro de conexão. Tente novamente.', 'error');
        } finally {
            this.isProcessing = false;
            this.setLoadingState(false);
        }
    }

    handleSubscriptionForm(form) {
        const formData = new FormData(form);
        const eventId = this.getEventId();
        
        if (eventId) {
            formData.append('event_id', eventId);
        }

        this.isProcessing = true;
        this.setLoadingState(true);

        fetch(this.apiUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                this.showToast(result.message, 'success');
                this.updateSubscriptionUI(true, result.data);
                form.reset();
            } else {
                this.showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            this.showToast('Erro de conexão. Tente novamente.', 'error');
        })
        .finally(() => {
            this.isProcessing = false;
            this.setLoadingState(false);
        });
    }

    updateSubscriptionUI(isSubscribed, data) {
        const container = document.getElementById('subscription-container');
        const subscribeBtn = document.getElementById('subscribe-btn');
        const unsubscribeBtn = document.getElementById('unsubscribe-btn');
        const subscriptionStatus = document.getElementById('subscription-status');

        console.log('Atualizando UI:', { isSubscribed, data });

        // Salvar estado no DOM para persistência
        if (container) {
            container.setAttribute('data-subscription-state', isSubscribed ? 'subscribed' : 'not-subscribed');
            if (isSubscribed && data) {
                container.setAttribute('data-subscription-data', JSON.stringify(data));
            }
        }

        if (isSubscribed) {
            // Usuário está inscrito
            if (subscribeBtn) {
                subscribeBtn.style.display = 'none';
                subscribeBtn.setAttribute('data-hidden', 'true');
            }
            
            if (unsubscribeBtn) {
                unsubscribeBtn.style.display = 'inline-block';
                unsubscribeBtn.removeAttribute('data-hidden');
            }

            if (subscriptionStatus) {
                const dataInscricao = data && data.data_inscricao ? 
                    new Date(data.data_inscricao).toLocaleDateString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit', 
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    }) : '';
                
                const statusHtml = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Você está inscrito neste evento!</strong>
                        ${dataInscricao ? `<br><small>Inscrito em: ${dataInscricao}</small>` : ''}
                        ${data && data.status ? `<br><small>Status: ${data.status}</small>` : ''}
                    </div>
                `;
                
                subscriptionStatus.innerHTML = statusHtml;
                subscriptionStatus.setAttribute('data-status', 'subscribed');
            }

            // Adicionar classe para indicar sucesso
            if (container) {
                container.classList.add('subscription-success');
                setTimeout(() => {
                    container.classList.remove('subscription-success');
                }, 1000);
            }

            // Atualizar card de inscrição se existir
            const subscriptionCard = document.querySelector('.subscription-card');
            if (subscriptionCard) {
                subscriptionCard.classList.add('subscribed');
                subscriptionCard.setAttribute('data-subscribed', 'true');
                
                // Atualizar título do card
                const cardTitle = subscriptionCard.querySelector('h5');
                if (cardTitle) {
                    cardTitle.innerHTML = '<i class="fas fa-check-circle me-2"></i>Você está Inscrito!';
                    cardTitle.setAttribute('data-original-title', cardTitle.getAttribute('data-original-title') || cardTitle.textContent);
                }
            }

        } else {
            // Usuário não está inscrito
            if (subscribeBtn) {
                subscribeBtn.style.display = 'inline-block';
                subscribeBtn.removeAttribute('data-hidden');
            }
            
            if (unsubscribeBtn) {
                unsubscribeBtn.style.display = 'none';
                unsubscribeBtn.setAttribute('data-hidden', 'true');
            }

            if (subscriptionStatus) {
                subscriptionStatus.innerHTML = '';
                subscriptionStatus.setAttribute('data-status', 'not-subscribed');
            }

            // Remover classe de inscrito
            const subscriptionCard = document.querySelector('.subscription-card');
            if (subscriptionCard) {
                subscriptionCard.classList.remove('subscribed');
                subscriptionCard.setAttribute('data-subscribed', 'false');
                
                // Restaurar título original do card
                const cardTitle = subscriptionCard.querySelector('h5');
                if (cardTitle) {
                    const originalTitle = cardTitle.getAttribute('data-original-title');
                    if (originalTitle) {
                        cardTitle.innerHTML = originalTitle;
                    } else {
                        const eventoGratuito = document.body.getAttribute('data-evento-gratuito') === '1';
                        const preco = document.body.getAttribute('data-evento-preco') || '0';
                        
                        if (eventoGratuito) {
                            cardTitle.innerHTML = '<i class="fas fa-ticket-alt me-2"></i>Evento Gratuito';
                        } else {
                            cardTitle.innerHTML = `<i class="fas fa-ticket-alt me-2"></i>R$ ${parseFloat(preco).toFixed(2).replace('.', ',')}`;
                        }
                    }
                }
            }
        }

        // Garantir que o estado persista visualmente
        this.enforceVisualState();
    }

    // Força o estado visual correto (executado periodicamente)
    enforceVisualState() {
        const container = document.getElementById('subscription-container');
        if (!container) return;

        const savedState = container.getAttribute('data-subscription-state');
        if (savedState === 'subscribed') {
            const subscribeBtn = document.getElementById('subscribe-btn');
            const unsubscribeBtn = document.getElementById('unsubscribe-btn');
            
            if (subscribeBtn && subscribeBtn.style.display !== 'none') {
                subscribeBtn.style.display = 'none';
                console.log('Forçando botão inscrever-se escondido');
            }
            
            if (unsubscribeBtn && unsubscribeBtn.style.display !== 'inline-block') {
                unsubscribeBtn.style.display = 'inline-block';
                console.log('Forçando botão cancelar visível');
            }

            const subscriptionCard = document.querySelector('.subscription-card');
            if (subscriptionCard && !subscriptionCard.classList.contains('subscribed')) {
                subscriptionCard.classList.add('subscribed');
                console.log('Forçando classe subscribed no card');
            }

            const subscriptionStatus = document.getElementById('subscription-status');
            if (subscriptionStatus && !subscriptionStatus.innerHTML.includes('inscrito')) {
                const savedData = container.getAttribute('data-subscription-data');
                if (savedData) {
                    try {
                        const data = JSON.parse(savedData);
                        this.updateSubscriptionUI(true, data);
                    } catch (e) {
                        console.error('Erro ao restaurar dados salvos:', e);
                    }
                }
            }
        }
    }

    updateParticipantCount(change) {
        const countElements = document.querySelectorAll('.participant-count, .total-inscritos');
        
        countElements.forEach(element => {
            const currentCount = parseInt(element.textContent) || 0;
            const newCount = Math.max(0, currentCount + change);
            element.textContent = newCount;
        });

        // Atualizar progress bar se existir
        const progressBar = document.querySelector('.progress-bar');
        const capacityElement = document.querySelector('.capacity-max');
        
        if (progressBar && capacityElement) {
            const totalInscritos = parseInt(document.querySelector('.participant-count')?.textContent) || 0;
            const capacidadeMax = parseInt(capacityElement.textContent) || 100;
            const percentage = Math.min(100, (totalInscritos / capacidadeMax) * 100);
            
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
        }
    }

    setLoadingState(isLoading, message = 'Processando...') {
        const subscribeBtn = document.getElementById('subscribe-btn');
        const unsubscribeBtn = document.getElementById('unsubscribe-btn');

        if (isLoading) {
            if (subscribeBtn && !subscribeBtn.hasAttribute('data-original-html')) {
                subscribeBtn.setAttribute('data-original-html', subscribeBtn.innerHTML);
                subscribeBtn.disabled = true;
                subscribeBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${message}`;
            }

            if (unsubscribeBtn && !unsubscribeBtn.hasAttribute('data-original-html')) {
                unsubscribeBtn.setAttribute('data-original-html', unsubscribeBtn.innerHTML);
                unsubscribeBtn.disabled = true;
                unsubscribeBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${message}`;
            }
        } else {
            if (subscribeBtn) {
                subscribeBtn.disabled = false;
                const originalHtml = subscribeBtn.getAttribute('data-original-html');
                if (originalHtml) {
                    subscribeBtn.innerHTML = originalHtml;
                    subscribeBtn.removeAttribute('data-original-html');
                }
            }

            if (unsubscribeBtn) {
                unsubscribeBtn.disabled = false;
                const originalHtml = unsubscribeBtn.getAttribute('data-original-html');
                if (originalHtml) {
                    unsubscribeBtn.innerHTML = originalHtml;
                    unsubscribeBtn.removeAttribute('data-original-html');
                }
            }
        }
    }

    getEventId() {
        // Tentar várias formas de obter o ID do evento
        
        // 1. URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        let eventId = urlParams.get('id');
        
        // 2. Data attribute no body ou container
        if (!eventId) {
            eventId = document.body.getAttribute('data-event-id');
        }
        
        // 3. Data attribute em elementos específicos
        if (!eventId) {
            const eventElement = document.querySelector('[data-event-id]');
            if (eventElement) {
                eventId = eventElement.getAttribute('data-event-id');
            }
        }
        
        // 4. Hidden input
        if (!eventId) {
            const hiddenInput = document.getElementById('event-id');
            if (hiddenInput) {
                eventId = hiddenInput.value;
            }
        }

        // 5. Meta tag
        if (!eventId) {
            const metaEventId = document.querySelector('meta[name="event-id"]');
            if (metaEventId) {
                eventId = metaEventId.getAttribute('content');
            }
        }

        console.log('Event ID encontrado:', eventId);
        return eventId;
    }

    isUserLoggedIn() {
        // Verificar se usuário está logado
        return document.body.classList.contains('user-logged-in') || 
               document.querySelector('.user-dropdown') !== null ||
               window.ConectaEventos?.isLoggedIn === true;
    }

    showToast(message, type = 'info') {
        // Criar toast simples
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
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        toast.innerHTML = `
            <i class="${icons[type] || icons.info} me-2"></i>
            ${message}
            <button type="button" class="btn-close" onclick="this.parentNode.remove()"></button>
        `;

        document.body.appendChild(toast);

        // Auto remove após 5 segundos
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }

    dispatchEvent(eventName, data = {}) {
        const event = new CustomEvent(eventName, {
            detail: data,
            bubbles: true
        });
        document.dispatchEvent(event);
    }

    // Método público para verificar status
    async checkStatus(eventId = null) {
        if (!eventId) {
            eventId = this.getEventId();
        }
        
        if (!eventId) return null;

        try {
            const response = await fetch(`${this.apiUrl}?event_id=${eventId}`, {
                method: 'GET',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Erro ao verificar status:', error);
            return null;
        }
    }

    // Método público para inscrever
    async subscribe(eventId = null, observacoes = '') {
        if (!eventId) {
            eventId = this.getEventId();
        }

        const formData = new FormData();
        formData.append('event_id', eventId);
        formData.append('observacoes', observacoes);

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Erro na inscrição:', error);
            return { success: false, message: 'Erro de conexão' };
        }
    }

    // Método público para cancelar inscrição
    async unsubscribe(eventId = null) {
        if (!eventId) {
            eventId = this.getEventId();
        }

        try {
            const response = await fetch(this.apiUrl, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ event_id: eventId })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Erro ao cancelar inscrição:', error);
            return { success: false, message: 'Erro de conexão' };
        }
    }
}

// Inicializar automaticamente quando DOM estiver pronto
let subscriptionSystem = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado, inicializando sistema de inscrições...');
    
    // Só inicializar se estivermos em uma página de evento
    if (document.getElementById('subscribe-btn') || 
        document.getElementById('unsubscribe-btn') || 
        document.getElementById('subscription-container')) {
        
        subscriptionSystem = new EventSubscriptionSystem();
        
        // Expor globalmente para uso em outros scripts
        window.EventSubscriptions = subscriptionSystem;
        console.log('Sistema de inscrições inicializado e exposto globalmente');
    } else {
        console.log('Página não contém elementos de inscrição, sistema não inicializado');
    }
});

// Funções globais para compatibilidade
window.subscribeToEvent = function(eventId, observacoes = '') {
    if (subscriptionSystem) {
        return subscriptionSystem.subscribe(eventId, observacoes);
    }
    console.error('Sistema de inscrições não inicializado');
};

window.unsubscribeFromEvent = function(eventId) {
    if (subscriptionSystem) {
        return subscriptionSystem.unsubscribe(eventId);
    }
    console.error('Sistema de inscrições não inicializado');
};

window.checkSubscriptionStatus = function(eventId) {
    if (subscriptionSystem) {
        return subscriptionSystem.checkStatus(eventId);
    }
    console.error('Sistema de inscrições não inicializado');
};

// CSS adicional para animações
const subscriptionCSS = `
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
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .subscription-success {
        animation: pulse 0.6s ease-in-out;
    }
    
    .subscription-card.subscribed {
        background: linear-gradient(135deg, #17a2b8, #138496) !important;
        border: 2px solid #28a745;
        animation: fadeInUp 0.5s ease-out;
    }
    
    .subscription-card.subscribed h5 {
        color: white !important;
    }
    
    #subscribe-btn, #unsubscribe-btn {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    #subscribe-btn:disabled, #unsubscribe-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    #subscribe-btn:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
    
    #unsubscribe-btn:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    
    .subscription-container {
        transition: all 0.3s ease;
    }
    
    .subscription-status {
        margin-bottom: 1rem;
        border-radius: 0.5rem;
        animation: fadeInUp 0.5s ease-out;
    }
    
    .subscription-status .alert {
        border: none;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
    }
    
    .progress-bar {
        transition: width 0.5s ease;
    }
    
    /* Loading states */
    .btn-loading {
        position: relative;
        color: transparent !important;
    }
    
    .btn-loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 16px;
        height: 16px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
    
    /* Efeito de persistência visual */
    .subscription-persisted {
        position: relative;
    }
    
    .subscription-persisted::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(45deg, #28a745, #20c997, #28a745);
        border-radius: inherit;
        z-index: -1;
        animation: persistedGlow 2s ease-in-out infinite alternate;
    }
    
    @keyframes persistedGlow {
        0% { opacity: 0.5; }
        100% { opacity: 0.8; }
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .alert.position-fixed {
            right: 10px;
            left: 10px;
            min-width: auto;
            max-width: none;
        }
        
        #subscribe-btn, #unsubscribe-btn {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }
    }
    
    /* Status indicators */
    .subscription-status-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
        animation: statusPulse 2s ease-in-out infinite;
    }
    
    .subscription-status-indicator.subscribed {
        background: #28a745;
    }
    
    .subscription-status-indicator.not-subscribed {
        background: #6c757d;
    }
    
    @keyframes statusPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
`;

// Adicionar CSS se não existir
if (!document.getElementById('subscription-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'subscription-styles';
    styleSheet.textContent = subscriptionCSS;
    document.head.appendChild(styleSheet);
}