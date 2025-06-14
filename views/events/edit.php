<?php
// ==========================================
// EDITAR EVENTO - VERSÃO CORRIGIDA COM UPLOAD DE IMAGEM
// Local: views/events/edit.php
// ==========================================

session_start();

// Verificar se está logado e é organizador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'organizador') {
    header("Location: ../dashboard/participant.php");
    exit;
}

$title = "Editar Evento - Conecta Eventos";
$userName = $_SESSION['user_name'] ?? 'Organizador';

// URLs
$dashboardUrl = '../dashboard/organizer.php';
$homeUrl = '../../index.php';

// Verificar se ID foi fornecido
$eventId = $_GET['id'] ?? null;
if (!$eventId) {
    header('Location: list.php');
    exit();
}

$success_message = '';
$error_message = '';
$evento = null;
$categorias = [];

// Carregar dados do evento
try {
    require_once '../../controllers/EventController.php';
    $eventController = new EventController();
    
    $evento = $eventController->getById($eventId);
    if (!$evento || !$eventController->canEdit($eventId)) {
        $error_message = "Evento não encontrado ou você não tem permissão para editá-lo.";
    } else {
        $categorias = $eventController->getCategories();
    }
} catch (Exception $e) {
    error_log("Erro ao carregar evento: " . $e->getMessage());
    $error_message = "Erro ao carregar dados do evento.";
}

// Se não conseguiu carregar o evento, redirecionar
if (!$evento) {
    header('Location: list.php');
    exit();
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message) {
    try {
        // Verificar se classe ImageUploadHandler existe, se não, criar inline
        if (!class_exists('ImageUploadHandler')) {
            // Incluir o handler se existir, senão usar implementação básica
            $handlerPath = '../../handlers/ImageUploadHandler.php';
            if (file_exists($handlerPath)) {
                require_once $handlerPath;
            } else {
                // Implementação básica inline se arquivo não existir
                class ImageUploadHandler {
                    private $uploadDir;
                    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    private $maxFileSize = 5242880; // 5MB
                    
                    public function __construct() {
                        $this->uploadDir = __DIR__ . '/../../uploads/eventos/';
                        $this->ensureUploadDirectory();
                    }
                    
                    private function ensureUploadDirectory() {
                        if (!file_exists($this->uploadDir)) {
                            mkdir($this->uploadDir, 0755, true);
                        }
                    }
                    
                    public function uploadImage($fileArray, $oldImageName = null) {
                        if (!isset($fileArray) || $fileArray['error'] !== UPLOAD_ERR_OK) {
                            return [
                                'success' => false,
                                'message' => 'Erro no upload do arquivo.',
                                'filename' => null
                            ];
                        }
                        
                        $extension = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
                        if (!in_array($extension, $this->allowedTypes)) {
                            return [
                                'success' => false,
                                'message' => 'Tipo de arquivo não permitido.',
                                'filename' => null
                            ];
                        }
                        
                        if ($fileArray['size'] > $this->maxFileSize) {
                            return [
                                'success' => false,
                                'message' => 'Arquivo muito grande (máximo 5MB).',
                                'filename' => null
                            ];
                        }
                        
                        $newFileName = 'evento_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
                        $targetPath = $this->uploadDir . $newFileName;
                        
                        if (move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
                            if ($oldImageName && file_exists($this->uploadDir . $oldImageName)) {
                                unlink($this->uploadDir . $oldImageName);
                            }
                            
                            return [
                                'success' => true,
                                'message' => 'Imagem enviada com sucesso!',
                                'filename' => $newFileName,
                                'url' => $this->getImageUrl($newFileName)
                            ];
                        }
                        
                        return [
                            'success' => false,
                            'message' => 'Erro ao salvar arquivo.',
                            'filename' => null
                        ];
                    }
                    
                    public function deleteImage($fileName) {
                        if ($fileName && file_exists($this->uploadDir . $fileName)) {
                            return unlink($this->uploadDir . $fileName);
                        }
                        return true;
                    }
                    
                    public function getImageUrl($fileName) {
                        return 'https://conecta-eventos-production.up.railway.app/uploads/eventos/' . $fileName;
                    }
                }
            }
        }
        
        $imageHandler = new ImageUploadHandler();
        
        // Processar upload de nova imagem se enviada
        $imageResult = null;
        $newImageName = null;
        
        if (isset($_FILES['imagem_capa']) && $_FILES['imagem_capa']['error'] !== UPLOAD_ERR_NO_FILE) {
            $oldImageName = $evento['imagem_capa']; // Imagem atual para remoção se upload for bem-sucedido
            $imageResult = $imageHandler->uploadImage($_FILES['imagem_capa'], $oldImageName);
            
            if (!$imageResult['success']) {
                $error_message = "Erro no upload da imagem: " . $imageResult['message'];
            } else {
                $newImageName = $imageResult['filename'];
            }
        }
        
        // Se não houve erro na imagem, atualizar evento
        if (!$error_message) {
            // Preparar dados para atualização
            $updateData = [
                'titulo' => trim($_POST['titulo'] ?? ''),
                'descricao' => trim($_POST['descricao'] ?? ''),
                'id_categoria' => !empty($_POST['categoria']) ? (int)$_POST['categoria'] : null,
                'data_inicio' => $_POST['data_inicio'] ?? '',
                'data_fim' => $_POST['data_fim'] ?? $_POST['data_inicio'],
                'horario_inicio' => $_POST['horario_inicio'] ?? '',
                'horario_fim' => $_POST['horario_fim'] ?? $_POST['horario_inicio'],
                'local_nome' => trim($_POST['local_nome'] ?? ''),
                'local_endereco' => trim($_POST['local_endereco'] ?? ''),
                'local_cidade' => trim($_POST['local_cidade'] ?? ''),
                'local_estado' => $_POST['local_estado'] ?? '',
                'local_cep' => $_POST['local_cep'] ?? null,
                'capacidade_maxima' => !empty($_POST['max_participantes']) ? (int)$_POST['max_participantes'] : null,
                'evento_gratuito' => isset($_POST['evento_gratuito']) ? 1 : 0,
                'preco' => isset($_POST['evento_gratuito']) ? 0 : (float)($_POST['preco'] ?? 0),
                'requisitos' => trim($_POST['requisitos'] ?? ''),
                'informacoes_adicionais' => trim($_POST['o_que_levar'] ?? ''),
                'status' => $_POST['status'] ?? 'rascunho'
            ];
            
            // Adicionar nova imagem se foi enviada
            if ($newImageName) {
                $updateData['imagem_capa'] = $newImageName;
            }
            
            // Verificar se deve remover imagem atual
            if (isset($_POST['remove_current_image']) && $_POST['remove_current_image'] && $evento['imagem_capa']) {
                $imageHandler->deleteImage($evento['imagem_capa']);
                $updateData['imagem_capa'] = null;
            }
            
            $result = $eventController->update($eventId, $updateData);
            
            if ($result['success']) {
                $success_message = $result['message'];
                // Recarregar dados atualizados
                $evento = $eventController->getById($eventId);
            } else {
                $error_message = $result['message'];
                
                // Se evento falhou mas imagem foi enviada, deletar imagem nova
                if ($newImageName) {
                    $imageHandler->deleteImage($newImageName);
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Erro ao atualizar evento: " . $e->getMessage());
        $error_message = "Erro interno do sistema. Tente novamente.";
        
        // Deletar imagem se foi enviada
        if (isset($newImageName) && $newImageName) {
            try {
                $imageHandler->deleteImage($newImageName);
            } catch (Exception $deleteException) {
                error_log("Erro ao deletar imagem após falha: " . $deleteException->getMessage());
            }
        }
    }
}

// Preparar valores padrão para o formulário
$defaults = [
    'titulo' => $evento['titulo'] ?? '',
    'descricao' => $evento['descricao'] ?? '',
    'categoria' => $evento['id_categoria'] ?? '',
    'data_inicio' => date('Y-m-d', strtotime($evento['data_inicio'] ?? 'now')),
    'data_fim' => date('Y-m-d', strtotime($evento['data_fim'] ?? $evento['data_inicio'] ?? 'now')),
    'horario_inicio' => $evento['horario_inicio'] ?? '',
    'horario_fim' => $evento['horario_fim'] ?? '',
    'local_nome' => $evento['local_nome'] ?? '',
    'local_endereco' => $evento['local_endereco'] ?? '',
    'local_cidade' => $evento['local_cidade'] ?? '',
    'local_estado' => $evento['local_estado'] ?? '',
    'local_cep' => $evento['local_cep'] ?? '',
    'max_participantes' => $evento['capacidade_maxima'] ?? '',
    'evento_gratuito' => $evento['evento_gratuito'] ?? false,
    'preco' => $evento['preco'] ?? '',
    'requisitos' => $evento['requisitos'] ?? '',
    'o_que_levar' => $evento['informacoes_adicionais'] ?? '',
    'status' => $evento['status'] ?? 'rascunho',
    'imagem_capa' => $evento['imagem_capa'] ?? ''
];

// Sobrescrever com dados do POST em caso de erro
if ($_POST && $error_message) {
    foreach ($defaults as $key => $value) {
        if (isset($_POST[$key])) {
            $defaults[$key] = $_POST[$key];
        }
    }
}

// URL da imagem atual
$currentImageUrl = '';
if ($defaults['imagem_capa']) {
    $currentImageUrl = 'https://conecta-eventos-production.up.railway.app/uploads/eventos/' . $defaults['imagem_capa'];
}
?>