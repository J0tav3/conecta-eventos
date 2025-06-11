// ========================================
// JAVASCRIPT PARA ATALHO DO USUÁRIO
// ========================================
// Local: public/js/user-shortcut.js
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    // ========================================
    // FUNCIONALIDADES DO ATALHO DO USUÁRIO
    // ========================================
    
    const userShortcut = document.querySelector('.user-shortcut');
    const navbar = document.querySelector('.navbar-custom');
    
    // Efeito de hover melhorado
    if (userShortcut) {
        userShortcut.addEventListener('mouseenter', function() {
            // Adicionar efeito de pulsação ao avatar
            const avatar = this.querySelector('.user-avatar');
            if (avatar) {
                avatar.style.animation = 'pulse 0.6s ease-in-out';
            }
            
            // Mostrar tooltip informativo
            showUserTooltip(this);
        });
        
        userShortcut.addEventListener('mouseleave', function() {
            // Remover animação
            const avatar = this.querySelector('.user-avatar');
            if (avatar) {
                avatar.style.animation = '';
            }
            
            // Esconder tooltip
            hideUserTooltip();
        });
        
        // Click tracking para analytics
        userShortcut.addEventListener('click', function() {
            // Analytics: registrar clique no atalho do usuário
            trackUserShortcutClick();
            
            // Feedback visual
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    }
    
    // ========================================
    // TOOLTIP DINÂMICO
    // ========================================
    
    function showUserTooltip(element) {
        // Remover tooltip existente
        hideUserTooltip();
        
        const userName = element.querySelector('.user-name')?.textContent || '';
        const userType = element.querySelector('.user-type')?.textContent || '';
        const dashboardText = userType.toLowerCase().includes('organizador') ? 'Dashboard' : 'Meu Painel';
        
        const tooltip = document.createElement('div');
        tooltip.id = 'user-tooltip';
        tooltip.className = 'user-tooltip';
        tooltip.innerHTML = `
            <div class="tooltip-content">
                <strong>Olá, ${userName}!</strong>
                <br>
                <small>Clique para ir ao ${dashboardText}</small>
                <div class="tooltip-arrow"></div>
            </div>
        `;
        
        document.body.appendChild(tooltip);
        
        // Posicionar tooltip
        const rect = element.getBoundingClientRect();
        tooltip.style.cssText = `
            position: fixed;
            top: ${rect.bottom + 10}px;
            left: ${rect.left + (rect.width / 2) - 75}px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            z-index: 9999;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            text-align: center;
            min-width: 150px;
        `;
        
        // Animar entrada
        setTimeout(() => {
            tooltip.style.opacity = '1';
            tooltip.style.transform = 'translateY(0)';
        }, 10);
    }
    
    function hideUserTooltip() {
        const tooltip = document.getElementById('user-tooltip');
        if (tooltip) {
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                if (tooltip.parentNode) {
                    tooltip.remove();
                }
            }, 300);
        }
    }
    
    // ========================================
    // INDICADOR DE STATUS ONLINE
    // ========================================
    
    function updateOnlineStatus() {
        const indicator = document.querySelector('.online-indicator');
        if (indicator) {
            if (navigator.onLine) {
                indicator.style.background = '#28a745';
                indicator.title = 'Online';
            } else {
                indicator.style.background = '#dc3545';
                indicator.title = 'Offline';
            }
        }
    }
    
    // Monitorar status de conexão
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();
    
    // ========================================
    // ANIMAÇÕES CSS DINÂMICAS
    // ========================================
    
    // Adicionar estilos de animação
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .user-shortcut:hover::before {
            animation: shimmer 0.8s ease-in-out;
        }
        
        .user-tooltip {
            pointer-events: none;
        }
        
        .tooltip-arrow {
            position: absolute;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-bottom: 5px solid rgba(0, 0, 0, 0.9);
        }
        
        /* Responsividade melhorada */
        @media (max-width: 768px) {
            .user-shortcut {
                min-width: 200px;
                justify-content: center;
            }
            
            .user-info {
                display: flex !important;
            }
        }
        
        /* Efeito de loading no avatar */
        .user-avatar.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top: 2px solid #667eea;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Melhoria no dropdown */
        .dropdown-menu {
            animation: fadeInUp 0.3s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
    
    // ========================================
    // NAVEGAÇÃO POR TECLADO
    // ========================================
    
    // Atalho de teclado para dashboard (Alt + D)
    document.addEventListener('keydown', function(e) {
        if (e.altKey && e.key === 'd') {
            e.preventDefault();
            if (userShortcut) {
                userShortcut.click();
            }
        }
    });
    
    // ========================================
    // ANALYTICS E TRACKING
    // ========================================
    
    function trackUserShortcutClick() {
        // Implementar tracking de analytics aqui
        if (typeof gtag !== 'undefined') {
            gtag('event', 'user_shortcut_click', {
                'event_category': 'navigation',
                'event_label': 'header_user_shortcut'
            });
        }
        
        // Console log para desenvolvimento
        console.log('User shortcut clicked - redirecting to dashboard');
    }
    
    // ========================================
    // NOTIFICAÇÕES VISUAIS
    // ========================================
    
    function showQuickNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} quick-notification`;
        notification.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            ${message}
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto remover
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 3000);
    }
    
    // ========================================
    // MELHORIAS DE PERFORMANCE
    // ========================================
    
    // Lazy loading do avatar se necessário
    const avatar = document.querySelector('.user-avatar');
    if (avatar && !avatar.style.backgroundImage) {
        // Implementar carregamento de imagem do usuário se disponível
        loadUserAvatar(avatar);
    }
    
    function loadUserAvatar(avatarElement) {
        // Simular carregamento de avatar personalizado
        const userId = avatarElement.dataset.userId;
        
        if (userId) {
            // Adicionar efeito de loading
            avatarElement.classList.add('loading');
            
            // Simular requisição para buscar avatar personalizado
            setTimeout(() => {
                // Remover loading
                avatarElement.classList.remove('loading');
                
                // Se não encontrar avatar personalizado, manter as iniciais
                console.log('Avatar carregado para usuário:', userId);
            }, 1000);
        }
    }
    
    // ========================================
    // CACHE E OTIMIZAÇÕES
    // ========================================
    
    // Cache do último dashboard visitado
    function cacheLastDashboardVisit() {
        const userType = document.querySelector('.user-type')?.textContent?.toLowerCase();
        if (userType) {
            localStorage.setItem('lastDashboardType', userType);
            localStorage.setItem('lastDashboardVisit', new Date().toISOString());
        }
    }
    
    // Pré-carregar dashboard (preload)
    function preloadDashboard() {
        if (userShortcut) {
            const dashboardUrl = userShortcut.getAttribute('href');
            if (dashboardUrl) {
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = dashboardUrl;
                document.head.appendChild(link);
            }
        }
    }
    
    // ========================================
    // ACCESSIBILITY (A11Y)
    // ========================================
    
    // Melhorar acessibilidade do atalho
    function enhanceAccessibility() {
        if (userShortcut) {
            // Adicionar atributos ARIA
            userShortcut.setAttribute('role', 'button');
            userShortcut.setAttribute('aria-label', 'Ir para dashboard principal');
            
            // Suporte a navegação por teclado
            userShortcut.setAttribute('tabindex', '0');
            
            // Enter e Space para ativar
            userShortcut.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
            
            // Focus visível
            userShortcut.addEventListener('focus', function() {
                this.style.outline = '2px solid rgba(255,255,255,0.5)';
                this.style.outlineOffset = '2px';
            });
            
            userShortcut.addEventListener('blur', function() {
                this.style.outline = '';
                this.style.outlineOffset = '';
            });
        }
    }
    
    // ========================================
    // INICIALIZAÇÃO
    // ========================================
    
    // Executar melhorias
    enhanceAccessibility();
    preloadDashboard();
    
    // Adicionar evento para cache na saída
    window.addEventListener('beforeunload', cacheLastDashboardVisit);
    
    // ========================================
    // PWA E SERVICE WORKER INTEGRATION
    // ========================================
    
    // Verificar se é PWA e adicionar funcionalidades extras
    if (window.matchMedia('(display-mode: standalone)').matches) {
        // Está rodando como PWA
        userShortcut?.classList.add('pwa-mode');
        
        // Adicionar gesture de swipe para mobile PWA
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipeGesture();
        });
        
        function handleSwipeGesture() {
            const swipeThreshold = 100;
            const swipeDistance = touchEndX - touchStartX;
            
            // Swipe right para abrir dashboard
            if (swipeDistance > swipeThreshold && userShortcut) {
                userShortcut.click();
            }
        }
    }
    
    // ========================================
    // DARK MODE SUPPORT
    // ========================================
    
    function updateTheme() {
        const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (isDarkMode) {
            userShortcut?.classList.add('dark-mode');
        } else {
            userShortcut?.classList.remove('dark-mode');
        }
    }
    
    // Monitorar mudanças de tema
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateTheme);
    updateTheme();
    
    // ========================================
    // FEEDBACK HAPTICO (MOBILE)
    // ========================================
    
    function triggerHapticFeedback() {
        if ('vibrate' in navigator) {
            navigator.vibrate(50); // Vibração leve
        }
    }
    
    // Adicionar feedback haptico ao toque
    userShortcut?.addEventListener('touchstart', triggerHapticFeedback);
    
    // ========================================
    // MONITORAMENTO DE PERFORMANCE
    // ========================================
    
    // Medir tempo de carregamento do dashboard
    function measureDashboardLoadTime() {
        const startTime = performance.now();
        
        userShortcut?.addEventListener('click', function() {
            const clickTime = performance.now();
            const timeTaken = clickTime - startTime;
            
            // Log para analytics
            console.log(`Dashboard navigation time: ${timeTaken}ms`);
            
            // Enviar para analytics se configurado
            if (typeof gtag !== 'undefined') {
                gtag('event', 'timing_complete', {
                    name: 'dashboard_navigation',
                    value: Math.round(timeTaken)
                });
            }
        });
    }
    
    measureDashboardLoadTime();
    
    // ========================================
    // EASTER EGGS E FEATURES ESPECIAIS
    // ========================================
    
    // Konami Code para modo desenvolvedor
    const konamiCode = [
        'ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown',
        'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight',
        'KeyB', 'KeyA'
    ];
    let konamiIndex = 0;
    
    document.addEventListener('keydown', function(e) {
        if (e.code === konamiCode[konamiIndex]) {
            konamiIndex++;
            if (konamiIndex === konamiCode.length) {
                activateDeveloperMode();
                konamiIndex = 0;
            }
        } else {
            konamiIndex = 0;
        }
    });
    
    function activateDeveloperMode() {
        showQuickNotification('🎉 Modo Desenvolvedor Ativado!', 'success');
        
        // Adicionar informações extras no atalho do usuário
        if (userShortcut) {
            userShortcut.style.background = 'linear-gradient(45deg, #ff6b6b, #4ecdc4)';
            userShortcut.title = 'Developer Mode Activated! 🚀';
            
            // Adicionar badge especial
            const devBadge = document.createElement('span');
            devBadge.innerHTML = '🚀';
            devBadge.style.cssText = `
                position: absolute;
                top: -5px;
                right: -5px;
                font-size: 0.8rem;
                animation: bounce 2s infinite;
            `;
            userShortcut.style.position = 'relative';
            userShortcut.appendChild(devBadge);
        }
    }
    
    // ========================================
    // CLEANUP E MEMORY MANAGEMENT
    // ========================================
    
    // Limpar recursos quando sair da página
    window.addEventListener('beforeunload', function() {
        // Remover event listeners
        hideUserTooltip();
        
        // Limpar timeouts
        clearTimeout(window.userShortcutTimeout);
        
        // Log final
        console.log('User shortcut cleanup completed');
    });
    
    // ========================================
    // FEATURES EXPERIMENTAIS
    // ========================================
    
    // Voice commands (experimental)
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'pt-BR';
        
        recognition.onresult = function(event) {
            const command = event.results[0][0].transcript.toLowerCase();
            
            if (command.includes('dashboard') || command.includes('painel')) {
                userShortcut?.click();
                showQuickNotification('🎤 Comando de voz executado!', 'info');
            }
        };
        
        // Ativar com Alt + V
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.key === 'v') {
                e.preventDefault();
                recognition.start();
                showQuickNotification('🎤 Diga "dashboard" ou "painel"', 'info');
            }
        });
    }
    
    console.log('🚀 User shortcut initialized with enhanced features');
});