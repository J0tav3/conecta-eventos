<?php
// ==========================================
// FUNÇÕES DE SESSÃO ATUALIZADAS COM FOTO
// Local: includes/session.php
// ==========================================

/**
 * Verificar se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Obter ID do usuário logado
 */
function getUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Obter nome do usuário logado
 */
function getUserName() {
    return isLoggedIn() ? $_SESSION['user_name'] : null;
}

/**
 * Obter email do usuário logado
 */
function getUserEmail() {
    return isLoggedIn() ? $_SESSION['user_email'] : null;
}

/**
 * Obter tipo do usuário logado
 */
function getUserType() {
    return isLoggedIn() ? $_SESSION['user_type'] : null;
}

/**
 * Obter foto de perfil do usuário logado
 */
function getUserPhoto() {
    return isLoggedIn() ? ($_SESSION['user_photo'] ?? null) : null;
}

/**
 * Obter URL da foto de perfil do usuário logado
 */
function getUserPhotoUrl() {
    $photo = getUserPhoto();
    if (!$photo) return null;
    
    $baseUrl = 'https://conecta-eventos-production.up.railway.app';
    return $baseUrl . '/uploads/profiles/' . $photo;
}

/**
 * Verificar se o usuário é organizador
 */
function isOrganizer() {
    return isLoggedIn() && getUserType() === 'organizador';
}

/**
 * Verificar se o usuário é participante
 */
function isParticipant() {
    return isLoggedIn() && getUserType() === 'participante';
}

/**
 * Obter avatar do usuário (foto ou inicial)
 */
function getUserAvatar($size = 40, $class = '') {
    $photoUrl = getUserPhotoUrl();
    $userName = getUserName() ?? 'U';
    $initial = strtoupper(substr($userName, 0, 1));
    
    if ($photoUrl) {
        return sprintf(
            '<img src="%s" alt="Foto de %s" class="rounded-circle %s" style="width: %dpx; height: %dpx; object-fit: cover;">',
            htmlspecialchars($photoUrl),
            htmlspecialchars($userName),
            htmlspecialchars($class),
            $size,
            $size
        );
    } else {
        return sprintf(
            '<div class="rounded-circle d-flex align-items-center justify-content-center %s" style="width: %dpx; height: %dpx; background: linear-gradient(135deg, #667eea, #764ba2); color: white; font-weight: bold; font-size: %dpx;">%s</div>',
            htmlspecialchars($class),
            $size,
            $size,
            max(12, $size / 3),
            $initial
        );
    }
}

/**
 * Atualizar foto na sessão
 */
function updateUserPhotoInSession($photoFileName) {
    if (isLoggedIn()) {
        $_SESSION['user_photo'] = $photoFileName;
        return true;
    }
    return false;
}

/**
 * Obter dados completos do usuário logado
 */
function getCurrentUserData() {
    if (!isLoggedIn()) return null;
    
    return [
        'id' => getUserId(),
        'name' => getUserName(),
        'email' => getUserEmail(),
        'type' => getUserType(),
        'photo' => getUserPhoto(),
        'photo_url' => getUserPhotoUrl(),
        'login_time' => $_SESSION['login_time'] ?? null
    ];
}

/**
 * Verificar se a sessão ainda é válida
 */
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Verificar se a sessão não expirou (24 horas)
    $maxLifetime = 24 * 60 * 60;
    
    if (isset($_SESSION['login_time']) && 
        (time() - $_SESSION['login_time']) > $maxLifetime) {
        
        // Sessão expirada
        destroySession();
        return false;
    }
    
    return true;
}

/**
 * Destruir sessão
 */
function destroySession() {
    // Destruir todas as variáveis de sessão
    $_SESSION = array();
    
    // Se existe um cookie de sessão, deletá-lo
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir a sessão
    session_destroy();
}

/**
 * Redirecionar se não estiver logado
 */
function requireLogin($redirectTo = '/views/auth/login.php') {
    if (!isLoggedIn()) {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
        $loginUrl = $redirectTo . '?redirect=' . urlencode($currentUrl);
        header("Location: $loginUrl");
        exit;
    }
}

/**
 * Redirecionar se não for organizador
 */
function requireOrganizer($redirectTo = '/views/dashboard/participant.php') {
    requireLogin();
    if (!isOrganizer()) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Redirecionar se não for participante
 */
function requireParticipant($redirectTo = '/views/dashboard/organizer.php') {
    requireLogin();
    if (!isParticipant()) {
        header("Location: $redirectTo");
        exit;
    }
}

/**
 * Obter URL de redirecionamento baseada no tipo de usuário
 */
function getRedirectUrl($userType = null) {
    $baseUrl = 'https://conecta-eventos-production.up.railway.app';
    
    if (!$userType) {
        $userType = getUserType();
    }
    
    switch ($userType) {
        case 'organizador':
            return $baseUrl . '/views/dashboard/organizer.php';
        case 'participante':
            return $baseUrl . '/views/dashboard/participant.php';
        default:
            return $baseUrl . '/index.php';
    }
}

/**
 * Gerar token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Gerar campo hidden com token CSRF
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Log de atividades do usuário
 */
function logUserActivity($action, $details = null) {
    if (!isLoggedIn()) return false;
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => getUserId(),
        'user_name' => getUserName(),
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Log no arquivo de sistema
    error_log("[USER_ACTIVITY] " . json_encode($logEntry));
    
    return true;
}

/**
 * Verificar se o usuário tem permissão para acessar um recurso
 */
function hasPermission($resource, $action = 'view') {
    if (!isLoggedIn()) return false;
    
    $userType = getUserType();
    
    // Definir permissões básicas
    $permissions = [
        'organizador' => [
            'events' => ['view', 'create', 'edit', 'delete'],
            'participants' => ['view'],
            'reports' => ['view'],
            'settings' => ['view', 'edit']
        ],
        'participante' => [
            'events' => ['view'],
            'subscriptions' => ['view', 'create', 'delete'],
            'favorites' => ['view', 'create', 'delete'],
            'settings' => ['view', 'edit']
        ]
    ];
    
    return isset($permissions[$userType][$resource]) && 
           in_array($action, $permissions[$userType][$resource]);
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gerar senha segura
 */
function generateSecurePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Formatar data para exibição
 */
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Formatar data e hora para exibição
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Calcular tempo decorrido (tempo relativo)
 */
function timeAgo($datetime) {
    if (!$datetime) return '-';
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'agora mesmo';
    if ($time < 3600) return floor($time / 60) . ' min atrás';
    if ($time < 86400) return floor($time / 3600) . ' h atrás';
    if ($time < 2592000) return floor($time / 86400) . ' dias atrás';
    if ($time < 31536000) return floor($time / 2592000) . ' meses atrás';
    return floor($time / 31536000) . ' anos atrás';
}

/**
 * Truncar texto mantendo palavras completas
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) return $text;
    
    $truncated = substr($text, 0, $length);
    $lastSpace = strrpos($truncated, ' ');
    
    if ($lastSpace !== false) {
        $truncated = substr($truncated, 0, $lastSpace);
    }
    
    return $truncated . $suffix;
}

/**
 * Gerar slug amigável para URLs
 */
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[àáâãäåąā]/u', 'a', $text);
    $text = preg_replace('/[èéêëęē]/u', 'e', $text);
    $text = preg_replace('/[ìíîïīįı]/u', 'i', $text);
    $text = preg_replace('/[òóôõöøōő]/u', 'o', $text);
    $text = preg_replace('/[ùúûüūůų]/u', 'u', $text);
    $text = preg_replace('/[ñń]/u', 'n', $text);
    $text = preg_replace('/[ç]/u', 'c', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}
?>