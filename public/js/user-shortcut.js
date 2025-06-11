/**
 * Sistema de Atalho do Usuário - Conecta Eventos
 * Gerencia dropdown, navegação e interações do usuário
 */

class UserShortcut {
    constructor() {
        this.dropdown = null;
        this.trigger = null;
        this.menu = null;
        this.overlay = null;
        this.isOpen = false;
        this.touchStartY = 0;
        
        this.init();
    }

    init() {
        // Aguarda o DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        this.dropdown = document.querySelector('.user-dropdown');
        if (!this.dropdown) return;

        this.trigger = this.dropdown.querySelector('.user-trigger');
        this.menu = this.dropdown.querySelector('.dropdown-menu');
        
        this.createOverlay();
        this.bindEvents();
        this.loadUserInfo();
    }

    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'dropdown-overlay';
        document.body.appendChild(this.overlay);
    }

    bindEvents() {
        // Click no trigger
        this.trigger?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggle();
        });

        // Hover events
        this.dropdown.addEventListener('mouseenter', () => {
            if (!this.isMobile()) {
                this.show();
            }
        });

        this.dropdown.addEventListener('mouseleave', () => {
            if (!this.isMobile()) {
                this.hide();
            }
        });

        // Click no overlay
        this.overlay?.addEventListener('click', () => this.hide());

        // Click fora do dropdown
        document.addEventListener('click', (e) => {
            if (!this.dropdown.contains(e.target)) {
                this.hide();
            }
        });

        // Teclas de navegação
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));

        // Touch events para mobile
        this.bindTouchEvents();

        // Resize handler
        window.addEventListener('resize', () => this.handleResize());

        // Links do dropdown
        this.bindDropdownLinks();
    }

    bindTouchEvents() {
        this.dropdown.addEventListener('touchstart', (e) => {
            this.touchStartY = e.touches[0].clientY;
        }, { passive: true });

        this.dropdown.addEventListener('touchmove', (e) => {
            if (this.isOpen) {
                e.preventDefault();
            }
        }, { passive: false });
    }

    bindDropdownLinks() {
        const links = this.menu?.querySelectorAll('a');
        links?.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                
                // Se é um link para dashboard, adiciona parâmetros úteis
                if (href?.includes('dashboard')) {
                    this.trackNavigation('dashboard');
                }
                
                // Fecha o dropdown após um delay para permitir a animação
                setTimeout(() => this.hide(), 100);
            });
        });
    }

    toggle() {
        if (this.isOpen) {
            this.hide();
        } else {
            this.show();
        }
    }

    show() {
        if (this.isOpen) return;

        this.isOpen = true;
        this.dropdown.classList.add('active');
        this.overlay?.classList.add('active');
        
        // Foca no primeiro item do menu
        const firstLink = this.menu?.querySelector('a');
        if (firstLink && this.isMobile()) {
            setTimeout(() => firstLink.focus(), 150);
        }

        // Ajusta posição se necessário
        this.adjustPosition();
        
        // Evento customizado
        this.dispatchEvent('userDropdownOpen');
    }

    hide() {
        if (!this.isOpen) return;

        this.isOpen = false;
        this.dropdown.classList.remove('active');
        this.overlay?.classList.remove('active');
        
        // Evento customizado
        this.dispatchEvent('userDropdownClose');
    }

    handleKeyboard(e) {
        if (!this.isOpen) {
            // Abre dropdown com Enter ou Space no trigger
            if ((e.key === 'Enter' || e.key === ' ') && 
                e.target === this.trigger) {
                e.preventDefault();
                this.show();
            }
            return;
        }

        switch (e.key) {
            case 'Escape':
                e.preventDefault();
                this.hide();
                this.trigger?.focus();
                break;
                
            case 'ArrowDown':
                e.preventDefault();
                this.focusNextItem();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.focusPrevItem();
                break;
                
            case 'Tab':
                // Permite navegação natural com Tab
                break;
        }
    }

    focusNextItem() {
        const items = Array.from(this.menu?.querySelectorAll('a') || []);
        const currentIndex = items.indexOf(document.activeElement);
        const nextIndex = (currentIndex + 1) % items.length;
        items[nextIndex]?.focus();
    }

    focusPrevItem() {
        const items = Array.from(this.menu?.querySelectorAll('a') || []);
        const currentIndex = items.indexOf(document.activeElement);
        const prevIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
        items[prevIndex]?.focus();
    }

    adjustPosition() {
        if (!this.menu) return;

        const rect = this.menu.getBoundingClientRect();
        const viewport = {
            width: window.innerWidth,
            height: window.innerHeight
        };

        // Ajusta horizontalmente se sair da tela
        if (rect.right > viewport.width) {
            this.menu.style.right = '0';
            this.menu.style.left = 'auto';
        }

        // Ajusta verticalmente se sair da tela
        if (rect.bottom > viewport.height) {
            this.menu.style.top = 'auto';
            this.menu.style.bottom = '100%';
            this.menu.style.marginTop = '0';
            this.menu.style.marginBottom = '8px';
        }
    }

    handleResize() {
        if (this.isOpen) {
            this.adjustPosition();
        }
    }

    isMobile() {
        return window.innerWidth <= 768;
    }

    loadUserInfo() {
        // Carrega informações adicionais do usuário se necessário
        const userAvatar = this.dropdown?.querySelector('.user-avatar');
        const userName = this.dropdown?.querySelector('.user-name');
        
        if (userAvatar && !userAvatar.textContent.trim()) {
            // Se não tem conteúdo, adiciona iniciais baseadas no nome
            const name = userName?.textContent?.trim();
            if (name) {
                const initials = this.getInitials(name);
                userAvatar.textContent = initials;
            }
        }

        // Adiciona status online se necessário
        this.updateOnlineStatus();
    }

    getInitials(name) {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .substring(0, 2)
            .toUpperCase();
    }

    updateOnlineStatus() {
        // Adiciona indicador de status online
        const avatar = this.dropdown?.querySelector('.user-avatar');
        if (avatar && !avatar.querySelector('.user-status')) {
            const status = document.createElement('div');
            status.className = 'user-status';
            status.title = 'Online';
            avatar.appendChild(status);
        }
    }

    trackNavigation(destination) {
        // Tracking de navegação (implementar conforme analytics usado)
        console.log(`User navigated to: ${destination}`);
        
        // Exemplo com Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'navigation', {
                'destination': destination,
                'source': 'user_dropdown'
            });
        }
    }

    dispatchEvent(eventName, detail = {}) {
        const event = new CustomEvent(eventName, {
            detail: { ...detail, dropdown: this.dropdown },
            bubbles: true
        });
        this.dropdown?.dispatchEvent(event);
    }

    // Método público para fechar dropdown
    close() {
        this.hide();
    }

    // Método público para abrir dropdown
    open() {
        this.show();
    }

    // Método público para verificar estado
    isDropdownOpen() {
        return this.isOpen;
    }

    // Cleanup
    destroy() {
        this.hide();
        this.overlay?.remove();
        // Remove todos os event listeners seria ideal aqui
    }
}

// Função para navegação rápida para dashboard
function navigateToDashboard() {
    const dashboardUrl = '/dashboard';
    
    // Adiciona parâmetros úteis
    const params = new URLSearchParams();
    params.set('from', 'header');
    params.set('timestamp', Date.now());
    
    const finalUrl = `${dashboardUrl}?${params.toString()}`;
    
    // Navegação com feedback visual
    const trigger = document.querySelector('.user-trigger');
    if (trigger) {
        trigger.style.opacity = '0.7';
        setTimeout(() => {
            window.location.href = finalUrl;
        }, 150);
    } else {
        window.location.href = finalUrl;
    }
}

// Função para quick actions
function quickAction(action) {
    switch (action) {
        case 'profile':
            window.location.href = '/perfil';
            break;
        case 'settings':
            window.location.href = '/configuracoes';
            break;
        case 'help':
            window.location.href = '/ajuda';
            break;
        case 'logout':
            if (confirm('Tem certeza que deseja sair?')) {
                window.location.href = '/logout';
            }
            break;
        default:
            console.warn('Ação não reconhecida:', action);
    }
}

// Inicialização automática
let userShortcutInstance = null;

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUserShortcut);
} else {
    initUserShortcut();
}

function initUserShortcut() {
    if (!userShortcutInstance) {
        userShortcutInstance = new UserShortcut();
    }
}

// Exporta para uso global
window.UserShortcut = UserShortcut;
window.navigateToDashboard = navigateToDashboard;
window.quickAction = quickAction;

// Event listeners para atalhos de teclado globais
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + Shift + D para dashboard
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
        e.preventDefault();
        navigateToDashboard();
    }
    
    // Ctrl/Cmd + Shift + U para abrir dropdown do usuário
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'U') {
        e.preventDefault();
        userShortcutInstance?.toggle();
    }
});