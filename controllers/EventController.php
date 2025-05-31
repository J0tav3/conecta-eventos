<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../includes/session.php';

class EventController {
    private $eventModel;
    
    public function __construct() {
        $this->eventModel = new Event();
    }
    
    /**
     * Criar novo evento
     */
    public function create($data) {
        // Verificar se usuário está logado e é organizador
        if (!isLoggedIn() || !isOrganizer()) {
            return [
                'success' => false,
                'message' => 'Acesso negado. Apenas organizadores podem criar eventos.'
            ];
        }
        
        // Adicionar ID do organizador
        $data['id_organizador'] = getUserId();
        
        // Processar upload de imagem se existir
        if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->uploadImage($_FILES['imagem_capa']);
            if ($uploadResult['success']) {
                $data['imagem_capa'] = $uploadResult['filename'];
            } else {
                return $uploadResult;
            }
        }
        
        // Processar checkbox de evento gratuito
        $data['evento_gratuito'] = isset($data['evento_gratuito']) ? true : false;
        if ($data['evento_gratuito']) {
            $data['preco'] = 0.00;
        }
        
        // Processar checkbox de destaque
        $data['destaque'] = isset($data['destaque']) ? true : false;
        
        return $this->eventModel->create($data);
    }
    
    /**
     * Atualizar evento existente
     */
    public function update($id, $data) {
        // Verificar se usuário está logado e é organizador
        if (!isLoggedIn() || !isOrganizer()) {
            return [
                'success' => false,
                'message' => 'Acesso negado. Apenas organizadores podem editar eventos.'
            ];
        }
        
        // Verificar se o evento pertence ao organizador
        $evento = $this->eventModel->findById($id);
        if (!$evento || $evento['id_organizador'] != getUserId()) {
            return [
                'success' => false,
                'message' => 'Evento não encontrado ou você não tem permissão para editá-lo.'
            ];
        }
        
        // Processar upload de imagem se existir
        if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->uploadImage($_FILES['imagem_capa']);
            if ($uploadResult['success']) {
                // Remover imagem antiga se existir
                if (!empty($evento['imagem_capa'])) {
                    $this->deleteImage($evento['imagem_capa']);
                }
                $data['imagem_capa'] = $uploadResult['filename'];
            } else {
                return $uploadResult;
            }
        } else {
            // Manter imagem atual se não foi enviada nova
            $data['imagem_capa'] = $evento['imagem_capa'];
        }
        
        // Processar checkbox de evento gratuito
        $data['evento_gratuito'] = isset($data['evento_gratuito']) ? true : false;
        if ($data['evento_gratuito']) {
            $data['preco'] = 0.00;
        }
        
        // Processar checkbox de destaque
        $data['destaque'] = isset($data['destaque']) ? true : false;
        
        return $this->eventModel->update($id, $data);
    }
    
    /**
     * Excluir evento
     */
    public function delete($id) {
        // Verificar se usuário está logado e é organizador
        if (!isLoggedIn() || !isOrganizer()) {
            return [
                'success' => false,
                'message' => 'Acesso negado.'
            ];
        }
        
        // Buscar evento para pegar nome da imagem
        $evento = $this->eventModel->findById($id);
        if ($evento && !empty($evento['imagem_capa'])) {
            $this->deleteImage($evento['imagem_capa']);
        }
        
        return $this->eventModel->delete($id, getUserId());
    }
    
    /**
     * Buscar evento por ID
     */
    public function getById($id) {
        return $this->eventModel->findById($id);
    }
    
    /**
     * Listar eventos do organizador logado
     */
    public function getMyEvents($filters = []) {
        if (!isLoggedIn() || !isOrganizer()) {
            return [];
        }
        
        return $this->eventModel->getEventsByOrganizer(getUserId(), $filters);
    }
    
    /**
     * Listar eventos públicos
     */
    public function getPublicEvents($filters = []) {
        return $this->eventModel->getPublicEvents($filters);
    }
    
    /**
     * Alterar status do evento
     */
    public function changeStatus($id, $status) {
        if (!isLoggedIn() || !isOrganizer()) {
            return [
                'success' => false,
                'message' => 'Acesso negado.'
            ];
        }
        
        return $this->eventModel->changeStatus($id, $status, getUserId());
    }
    
    /**
     * Obter estatísticas do evento
     */
    public function getEventStats($id) {
        // Verificar se o evento pertence ao organizador logado
        $evento = $this->eventModel->findById($id);
        if (!$evento || $evento['id_organizador'] != getUserId()) {
            return false;
        }
        
        return $this->eventModel->getEventStats($id);
    }
    
    /**
     * Obter categorias para formulário
     */
    public function getCategories() {
        // Buscar categorias ativas
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Upload de imagem
     */
    private function uploadImage($file) {
        $uploadDir = __DIR__ . '/../public/uploads/eventos/';
        
        // Criar diretório se não existir
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validar arquivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.'
            ];
        }
        
        // Validar tamanho (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'Arquivo muito grande. Tamanho máximo: 5MB.'
            ];
        }
        
        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('evento_') . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Fazer upload
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'filename' => $filename
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Erro ao fazer upload da imagem.'
            ];
        }
    }
    
    /**
     * Excluir imagem
     */
    private function deleteImage($filename) {
        $filepath = __DIR__ . '/../public/uploads/eventos/' . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
    
    /**
     * Paginação
     */
    public function paginate($filters = [], $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $filters['limite'] = $perPage;
        $filters['offset'] = $offset;
        
        $items = $this->eventModel->list($filters);
        $total = $this->eventModel->count($filters);
        $totalPages = ceil($total / $perPage);
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }
    
    /**
     * Verificar se usuário pode editar evento
     */
    public function canEdit($eventId) {
        if (!isLoggedIn() || !isOrganizer()) {
            return false;
        }
        
        $evento = $this->eventModel->findById($eventId);
        return $evento && $evento['id_organizador'] == getUserId();
    }
    
    /**
     * Formatar dados para exibição
     */
    public function formatEventForDisplay($event) {
        if (!$event) return null;
        
        $event['data_inicio_formatada'] = date('d/m/Y', strtotime($event['data_inicio']));
        $event['data_fim_formatada'] = date('d/m/Y', strtotime($event['data_fim']));
        $event['horario_inicio_formatado'] = date('H:i', strtotime($event['horario_inicio']));
        $event['horario_fim_formatado'] = date('H:i', strtotime($event['horario_fim']));
        $event['preco_formatado'] = $event['evento_gratuito'] ? 'Gratuito' : 'R$ ' . number_format($event['preco'], 2, ',', '.');
        
        // URL da imagem
        $event['imagem_url'] = !empty($event['imagem_capa']) 
            ? SITE_URL . '/public/uploads/eventos/' . $event['imagem_capa']
            : SITE_URL . '/public/images/evento-default.jpg';
            
        // Status traduzido
        $statusMap = [
            'rascunho' => 'Rascunho',
            'publicado' => 'Publicado',
            'cancelado' => 'Cancelado',
            'finalizado' => 'Finalizado'
        ];
        $event['status_nome'] = $statusMap[$event['status']] ?? $event['status'];
        
        // Vagas disponíveis
        if ($event['capacidade_maxima']) {
            $event['vagas_disponiveis'] = $event['capacidade_maxima'] - ($event['total_inscritos'] ?? 0);
            $event['vagas_esgotadas'] = $event['vagas_disponiveis'] <= 0;
        } else {
            $event['vagas_disponiveis'] = null;
            $event['vagas_esgotadas'] = false;
        }
        
        return $event;
    }
    
    /**
     * Duplicar evento
     */
    public function duplicate($id) {
        if (!isLoggedIn() || !isOrganizer()) {
            return [
                'success' => false,
                'message' => 'Acesso negado.'
            ];
        }
        
        $evento = $this->eventModel->findById($id);
        if (!$evento || $evento['id_organizador'] != getUserId()) {
            return [
                'success' => false,
                'message' => 'Evento não encontrado ou sem permissão.'
            ];
        }
        
        // Preparar dados para duplicação
        $novoEvento = [
            'id_organizador' => getUserId(),
            'id_categoria' => $evento['id_categoria'],
            'titulo' => $evento['titulo'] . ' (Cópia)',
            'descricao' => $evento['descricao'],
            'data_inicio' => date('Y-m-d', strtotime('+1 week')),
            'data_fim' => date('Y-m-d', strtotime('+1 week')),
            'horario_inicio' => $evento['horario_inicio'],
            'horario_fim' => $evento['horario_fim'],
            'local_nome' => $evento['local_nome'],
            'local_endereco' => $evento['local_endereco'],
            'local_cidade' => $evento['local_cidade'],
            'local_estado' => $evento['local_estado'],
            'local_cep' => $evento['local_cep'],
            'capacidade_maxima' => $evento['capacidade_maxima'],
            'preco' => $evento['preco'],
            'evento_gratuito' => $evento['evento_gratuito'],
            'requisitos' => $evento['requisitos'],
            'informacoes_adicionais' => $evento['informacoes_adicionais'],
            'status' => 'rascunho',
            'destaque' => false
        ];
        
        return $this->eventModel->create($novoEvento);
    }
}
?>