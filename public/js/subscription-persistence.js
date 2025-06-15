/**
 * Sistema de Persistência de Inscrições
 * Melhora a experiência do usuário mantendo estado entre reloads
 */

class SubscriptionPersistence {
    constructor() {
        this.storageKey = 'conecta_eventos_subscriptions';
        this.init();
    }

    init() {
        // Escutar eventos de inscrição/cancelamento
        document.addEventListener('subscriptionSuccess', (event) => {
            this.saveSubscriptionState(event.detail);
        });

        document.addEventListener('unsubscriptionSuccess', (event) => {
            this.removeSubscriptionState(event.detail);
        });

        // Verificar se há estados salvos ao carregar a página
        this.loadSavedStates();

        // Limpar estados antigos
        this.cleanOldStates();
    }

    saveSubscriptionState(data) {
        const eventId = this.getCurrentEventId();
        if (!eventId) return;

        const subscriptions = this.getStoredSubscriptions();
        subscriptions[eventId] = {
            subscribed: true,
            data: data,
            timestamp: Date.now(),
            url: window.location.pathname
        };

        localStorage.setItem(this.storageKey, JSON.stringify(subscriptions));
        console.log('Estado de inscrição salvo:', subscriptions[eventId]);
    }

    removeSubscriptionState(data) {
        const eventId = this.getCurrentEventId();
        if (!eventId) return;

        const subscriptions = this.getStoredSubscriptions();
        delete subscriptions[eventId];

        localStorage.setItem(this.storageKey, JSON.stringify(subscriptions));
        console.log('Estado de inscrição removido para evento:', eventId);
    }

    loadSavedStates() {
        const eventId = this.getCurrentEventId();
        if (!eventId) return;

        const subscriptions = this.getStoredSubscriptions();
        const savedState = subscriptions[eventId];

        if (savedState && savedState.subscribed) {
            // Verificar se o estado não é muito antigo (1 hora)
            const oneHour = 60 * 60 * 1000;
            if (Date.now() - savedState.timestamp < oneHour) {
                console.log('Carregando estado salvo de inscrição:', savedState);
                
                // Aguardar o sistema de inscrições carregar
                setTimeout(() => {
                    if (window.EventSubscriptions) {
                        window.EventSubscriptions.updateSubscriptionUI(true, savedState.data);
                        this.showPersistenceIndicator();
                    }
                }, 500);
            } else {
                // Estado muito antigo, remover
                delete subscriptions[eventId];
                localStorage.setItem(this.storageKey, JSON.stringify(subscriptions));
            }
        }
    }

    getStoredSubscriptions() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : {};
        } catch (error) {
            console.error('Erro ao ler estados salvos:', error);
            return {};
        }
    }

    getCurrentEventId() {
        // Múltiplas formas de obter o ID do evento
        const urlParams = new URLSearchParams(window.location.search);
        let eventId = urlParams.get('id');
        
        if (!eventId) {
            eventId = document.body.getAttribute('data-event-id');
        }
        
        if (!eventId) {
            const metaTag = document.querySelector('meta[name="event-id"]');
            if (metaTag) {
                eventId = metaTag.getAttribute('content');
            }
        }

        return eventId;
    }

    cleanOldStates() {
        const subscriptions = this.getStoredSubscriptions();
        const now = Date.now();
        const maxAge = 24 * 60 * 60 * 1000; // 24 horas
        let hasChanges = false;

        for (const eventId in subscriptions) {
            const state = subscriptions[eventId];
            if (now - state.timestamp > maxAge) {
                delete subscriptions[eventId];
                hasChanges = true;
            }
        }

        if (hasChanges) {
            localStorage.setItem(this.storageKey, JSON.stringify(subscriptions));
            console.log('Estados antigos removidos');
        }
    }

    showPersistenceIndicator() {
        // Mostrar um indicador visual discreto de que o estado foi restaurado
        const container = document.getElementById('subscription-container');
        if (container) {
            container.classList.add('subscription-persisted');
            
            // Remover o indicador após alguns segundos
            setTimeout(() => {
                container.classList.remove('subscription-persisted');
            }, 3000);
        }
    }

    // Método público para forçar sincronização
    syncWithServer() {
        if (window.EventSubscriptions) {
            window.EventSubscriptions.checkSubscriptionStatus();
        }
    }

    // Método público para limpar todos os estados
    clearAllStates() {
        localStorage.removeItem(this.storageKey);
        console.log('Todos os estados de inscrição foram limpos');
    }

    // Método público para debug
    getDebugInfo() {
        return {
            currentEventId: this.getCurrentEventId(),
            storedSubscriptions: this.getStoredSubscriptions(),
            storageKey: this.storageKey
        };
    }
}

// Inicializar automaticamente
document.addEventListener('DOMContentLoaded', () => {
    // Só inicializar se estivermos em uma página de evento
    if (document.getElementById('subscription-container') || 
        document.querySelector('[data-event-id]')) {
        
        window.SubscriptionPersistence = new SubscriptionPersistence();
        console.log('Sistema de persistência de inscrições inicializado');
    }
});

// Expor funções úteis globalmente
window.clearSubscriptionStates = function() {
    if (window.SubscriptionPersistence) {
        window.SubscriptionPersistence.clearAllStates();
    }
};

window.syncSubscriptionStates = function() {
    if (window.SubscriptionPersistence) {
        window.SubscriptionPersistence.syncWithServer();
    }
};

window.debugSubscriptionStates = function() {
    if (window.SubscriptionPersistence) {
        console.log('Debug de Estados de Inscrição:', 
                   window.SubscriptionPersistence.getDebugInfo());
    }
};