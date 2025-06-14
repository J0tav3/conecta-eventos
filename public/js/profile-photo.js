/**
 * ==========================================
 * JAVASCRIPT PARA UPLOAD DE FOTO DE PERFIL - CORRIGIDO
 * ==========================================
 * Local: public/js/profile-photo.js
 */

class ProfilePhotoManager {
    constructor() {
        this.apiUrl = '/api/profile-photo.php';
        this.maxFileSize = 2 * 1024 * 1024; // 2MB
        this.allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        this.isUploading = false;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadCurrentPhoto();
    }
    
    setupEventListeners() {
        // Click no avatar para abrir seletor de arquivo
        const avatarOverlay = document.querySelector('.avatar-overlay');
        const avatarInput = document.getElementById('avatar');
        
        if (avatarOverlay && avatarInput) {
            avatarOverlay.addEventListener('click', () => {
                if (!this.isUploading) {
                    avatarInput.click();
                }
            });
            
            avatarInput.addEventListener('change', (e) => {
                this.handleFileSelect(e);
            });
        }
        
        // Botão de remover foto (se existir)
        const removeBtn = document.getElementById('removePhotoBtn');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                this.removePhoto();
            });
        }
        
        // Drag and drop
        const avatarUpload = document.querySelector('.avatar-upload');
        if (avatarUpload) {
            avatarUpload.addEventListener('dragover', (e) => {
                e.preventDefault();
                avatarUpload.classList.add('drag-over');
            });
            
            avatarUpload.addEventListener('dragleave', (e) => {
                e.preventDefault();
                avatarUpload.classList.remove('drag-over');
            });
            
            avatarUpload.addEventListener('drop', (e) => {
                e.preventDefault();
                avatarUpload.classList.remove('drag-over');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    this.handleFile(files[0]);
                }
            });
        }
    }
    
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            this.handleFile(file);
        }
        // CORREÇÃO: Limpar o input para permitir reenvio do mesmo arquivo
        event.target.value = '';
    }
    
    handleFile(file) {
        // Validar arquivo
        const validation = this.validateFile(file);
        if (!validation.valid) {
            this.showToast(validation.message, 'error');
            return;
        }
        
        // Mostrar preview
        this.showPreview(file);
        
        // Fazer upload
        this.uploadPhoto(file);
    }
    
    validateFile(file) {
        // Verificar tipo
        if (!this.allowedTypes.includes(file.type)) {
            return {
                valid: false,
                message: 'Tipo de arquivo não permitido. Use: JPG, PNG, GIF ou WebP'
            };
        }
        
        // Verificar tamanho
        if (file.size > this.maxFileSize) {
            const maxSizeMB = this.maxFileSize / (1024 * 1024);
            return {
                valid: false,
                message: `Arquivo muito grande. Tamanho máximo: ${maxSizeMB}MB`
            };
        }
        
        return { valid: true };
    }
    
    showPreview(file) {
        const reader = new FileReader();
        const avatar = document.querySelector('.avatar');
        
        reader.onload = (e) => {
            if (avatar) {
                this.updateAvatarDisplay(e.target.result, true);
            }
        };
        
        reader.readAsDataURL(file);
    }
    
    async uploadPhoto(file) {
        if (this.isUploading) {
            return;
        }
        
        this.isUploading = true;
        this.showLoadingState(true);
        
        try {
            const formData = new FormData();
            formData.append('profile_photo', file);
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast(result.message, 'success');
                
                // CORREÇÃO: Usar a URL da resposta, com cache-busting
                const imageUrl = result.image_info.url + '?t=' + Date.now();
                this.updateAvatarDisplay(imageUrl, false);
                this.updateNavbarPhoto(imageUrl);
                this.ensureRemoveButton();
                
                // Aguardar um momento e atualizar novamente para garantir
                setTimeout(() => {
                    this.updateAvatarDisplay(imageUrl, false);
                    this.updateNavbarPhoto(imageUrl);
                }, 500);
                
            } else {
                this.showToast(result.message, 'error');
                this.revertToCurrentPhoto();
            }
            
        } catch (error) {
            console.error('Erro no upload:', error);
            this.showToast('Erro de conexão. Tente novamente.', 'error');
            this.revertToCurrentPhoto();
        } finally {
            this.isUploading = false;
            this.showLoadingState(false);
        }
    }
    
    async removePhoto() {
        if (this.isUploading) {
            return;
        }
        
        if (!confirm('Tem certeza que deseja remover sua foto de perfil?')) {
            return;
        }
        
        this.isUploading = true;
        this.showLoadingState(true);
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showToast(result.message, 'success');
                this.removePhotoDisplay();
                this.updateNavbarPhoto(null);
                this.removeRemoveButton();
            } else {
                this.showToast(result.message, 'error');
            }
            
        } catch (error) {
            console.error('Erro ao remover foto:', error);
            this.showToast('Erro de conexão. Tente novamente.', 'error');
        } finally {
            this.isUploading = false;
            this.showLoadingState(false);
        }
    }
    
    /**
     * CORREÇÃO: Método melhorado para atualizar o avatar
     */
    updateAvatarDisplay(imageSrc, isPreview = false) {
        const avatar = document.querySelector('.avatar');
        if (!avatar) return;
        
        // Limpar conteúdo atual
        avatar.innerHTML = '';
        
        if (imageSrc) {
            const img = document.createElement('img');
            img.style.cssText = `
                width: 100%;
                height: 100%;
                border-radius: 50%;
                object-fit: cover;
            `;
            
            // Aguardar carregamento da imagem
            img.onload = () => {
                avatar.classList.add('has-image');
            };
            
            img.onerror = () => {
                console.error('Erro ao carregar imagem:', imageSrc);
                if (!isPreview) {
                    this.revertToCurrentPhoto();
                }
            };
            
            img.src = imageSrc;
            avatar.appendChild(img);
            
        } else {
            // Mostrar inicial do usuário
            const userName = document.body.getAttribute('data-user-name') || 'U';
            const initial = userName.charAt(0).toUpperCase();
            avatar.textContent = initial;
            avatar.classList.remove('has-image');
        }
    }
    
    removePhotoDisplay() {
        this.updateAvatarDisplay(null);
    }
    
    ensureRemoveButton() {
        if (document.getElementById('removePhotoBtn')) {
            return; // Botão já existe
        }
        
        const avatarUpload = document.querySelector('.avatar-upload');
        if (avatarUpload && avatarUpload.parentNode) {
            const removeBtn = document.createElement('button');
            removeBtn.id = 'removePhotoBtn';
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger mt-2';
            removeBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Remover Foto';
            removeBtn.onclick = () => this.removePhoto();
            
            // Inserir após o container da foto
            avatarUpload.parentNode.insertBefore(removeBtn, avatarUpload.nextSibling);
        }
    }
    
    removeRemoveButton() {
        const removeBtn = document.getElementById('removePhotoBtn');
        if (removeBtn) {
            removeBtn.remove();
        }
    }
    
    /**
     * CORREÇÃO: Método melhorado para atualizar fotos na navbar
     */
    updateNavbarPhoto(photoUrl) {
        // Selecionar todos os elementos de foto do usuário
        const navbarPhotos = document.querySelectorAll('.navbar-user-photo, .sidebar-user-photo, .user-avatar, .dropdown-avatar');
        
        navbarPhotos.forEach(photo => {
            if (photoUrl) {
                // Adicionar cache-busting à URL
                const cacheBustedUrl = photoUrl + (photoUrl.includes('?') ? '&' : '?') + 't=' + Date.now();
                
                if (photo.tagName === 'IMG') {
                    photo.src = cacheBustedUrl;
                } else {
                    // Limpar conteúdo e adicionar imagem
                    photo.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = cacheBustedUrl;
                    img.style.cssText = `
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                        border-radius: 50%;
                    `;
                    photo.appendChild(img);
                }
            } else {
                // Voltar para inicial
                const userName = document.body.getAttribute('data-user-name') || 'U';
                const initial = userName.charAt(0).toUpperCase();
                
                if (photo.tagName === 'IMG') {
                    photo.style.display = 'none';
                } else {
                    photo.innerHTML = initial;
                    photo.style.backgroundImage = '';
                }
            }
        });
    }
    
    revertToCurrentPhoto() {
        // Tentar obter a foto atual da sessão/página
        const currentPhotoElements = document.querySelectorAll('.navbar-user-photo img, .sidebar-user-photo img');
        let currentPhotoUrl = null;
        
        for (const element of currentPhotoElements) {
            if (element.src && !element.src.includes('data:')) {
                currentPhotoUrl = element.src.split('?')[0]; // Remove cache-busting
                break;
            }
        }
        
        if (currentPhotoUrl) {
            this.updateAvatarDisplay(currentPhotoUrl);
        } else {
            this.updateAvatarDisplay(null);
        }
    }
    
    async loadCurrentPhoto() {
        // Carregar foto atual se existir
        const currentPhotoElements = document.querySelectorAll('.navbar-user-photo img, .sidebar-user-photo img');
        
        for (const element of currentPhotoElements) {
            if (element.src && !element.src.includes('data:')) {
                const photoUrl = element.src.split('?')[0]; // Remove cache-busting
                this.updateAvatarDisplay(photoUrl);
                this.ensureRemoveButton();
                break;
            }
        }
    }
    
    showLoadingState(isLoading) {
        const overlay = document.querySelector('.avatar-overlay');
        
        if (isLoading) {
            if (overlay) {
                overlay.innerHTML = '<i class="fas fa-spinner fa-spin text-white fa-lg"></i>';
                overlay.style.opacity = '1';
            }
        } else {
            if (overlay) {
                overlay.innerHTML = '<i class="fas fa-camera text-white fa-lg"></i>';
                overlay.style.opacity = '';
            }
        }
    }
    
    showToast(message, type = 'info') {
        // Usar o sistema de toast existente ou criar um novo
        if (typeof showToast === 'function') {
            showToast(message, type);
            return;
        }
        
        // Fallback: criar toast simples
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        `;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle',
            warning: 'fas fa-exclamation-triangle'
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
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Só inicializar se estivermos em uma página com avatar upload
    if (document.querySelector('.avatar-upload')) {
        window.profilePhotoManager = new ProfilePhotoManager();
    }
});

// CSS adicional para melhorar a aparência
const additionalCSS = `
    .avatar-upload {
        transition: all 0.3s ease;
    }
    
    .avatar-upload.drag-over {
        transform: scale(1.05);
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
    }
    
    .avatar.has-image {
        border: 3px solid #28a745;
    }
    
    .avatar-overlay {
        transition: all 0.3s ease;
    }
    
    .avatar-upload:hover .avatar-overlay {
        opacity: 1 !important;
    }
    
    .profile-photo-loading {
        position: relative;
        overflow: hidden;
    }
    
    .profile-photo-loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    @keyframes photoUpload {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .avatar-upload.uploading {
        animation: photoUpload 2s infinite;
    }
    
    /* Garantir que imagens de perfil não tenham cache */
    .navbar-user-photo img,
    .sidebar-user-photo img,
    .user-avatar img,
    .dropdown-avatar img,
    .avatar img {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
`;

// Adicionar CSS ao documento
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalCSS;
document.head.appendChild(styleSheet);