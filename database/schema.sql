-- Script de criação das tabelas para o Railway
CREATE DATABASE IF NOT EXISTS conecta_eventos;
USE conecta_eventos;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('organizador', 'participante') NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL
);

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT,
    cor VARCHAR(7) DEFAULT '#007bff',
    icone VARCHAR(50) DEFAULT 'fa-calendar',
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de eventos
CREATE TABLE IF NOT EXISTS eventos (
    id_evento INT PRIMARY KEY AUTO_INCREMENT,
    id_organizador INT NOT NULL,
    id_categoria INT,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    horario_inicio TIME NOT NULL,
    horario_fim TIME NOT NULL,
    local_nome VARCHAR(100) NOT NULL,
    local_endereco VARCHAR(200) NOT NULL,
    local_cidade VARCHAR(50) NOT NULL,
    local_estado VARCHAR(2) NOT NULL,
    local_cep VARCHAR(10),
    capacidade_maxima INT,
    preco DECIMAL(10,2) DEFAULT 0.00,
    evento_gratuito BOOLEAN DEFAULT TRUE,
    imagem_capa VARCHAR(255),
    link_externo VARCHAR(255),
    requisitos TEXT,
    informacoes_adicionais TEXT,
    status ENUM('rascunho', 'publicado', 'cancelado', 'finalizado') DEFAULT 'rascunho',
    destaque BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_organizador) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE SET NULL
);

-- Tabela de inscrições
CREATE TABLE IF NOT EXISTS inscricoes (
    id_inscricao INT PRIMARY KEY AUTO_INCREMENT,
    id_evento INT NOT NULL,
    id_participante INT NOT NULL,
    data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pendente', 'confirmada', 'cancelada') DEFAULT 'confirmada',
    observacoes TEXT,
    presente BOOLEAN NULL,
    avaliacao_evento INT CHECK (avaliacao_evento BETWEEN 1 AND 5),
    comentario_avaliacao TEXT,
    data_avaliacao TIMESTAMP NULL,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
    FOREIGN KEY (id_participante) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY unique_inscricao (id_evento, id_participante)
);

-- Tabela de favoritos
CREATE TABLE IF NOT EXISTS favoritos (
    id_favorito INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_evento INT NOT NULL,
    data_favoritado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) ON DELETE CASCADE,
    UNIQUE KEY unique_favorito (id_usuario, id_evento)
);

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id_notificacao INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('sistema', 'evento', 'inscricao', 'avaliacao') DEFAULT 'sistema',
    lida BOOLEAN DEFAULT FALSE,
    id_referencia INT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_leitura TIMESTAMP NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Inserir categorias padrão
INSERT IGNORE INTO categorias (nome, descricao, cor, icone) VALUES
('Tecnologia', 'Eventos relacionados à tecnologia', '#007bff', 'fa-laptop'),
('Negócios', 'Eventos corporativos e de negócios', '#28a745', 'fa-briefcase'),
('Educação', 'Eventos educacionais e de aprendizado', '#ffc107', 'fa-graduation-cap'),
('Arte e Cultura', 'Eventos artísticos e culturais', '#e83e8c', 'fa-palette'),
('Esportes', 'Eventos esportivos e atividades físicas', '#fd7e14', 'fa-running'),
('Música', 'Shows, concertos e eventos musicais', '#6f42c1', 'fa-music');

-- Criar usuário administrador padrão (senha: admin123)
INSERT IGNORE INTO usuarios (nome, email, senha, tipo) VALUES
('Administrador', 'admin@conectaeventos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.u