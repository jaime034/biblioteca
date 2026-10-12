CREATE DATABASE IF NOT EXISTS biblioteca
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE biblioteca;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    senha VARCHAR(255),
    status TINYINT UNSIGNED NOT NULL DEFAULT 1,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_usuarios_nome (nome),
    INDEX idx_usuarios_status (status)
) ENGINE=InnoDB;


CREATE TABLE categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE autores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    biografia TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_autores_nome (nome)
) ENGINE=InnoDB;


CREATE TABLE livros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    descricao VARCHAR(255),
    capa VARCHAR(255),
    editora VARCHAR(150),
    ano_publicacao YEAR,
    categoria_id INT UNSIGNED NOT NULL,
    quantidade_total INT UNSIGNED NOT NULL DEFAULT 1,
    quantidade_disponivel INT UNSIGNED NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_livros_categoria
        FOREIGN KEY (categoria_id)
        REFERENCES categorias(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_livros_titulo (titulo),
    INDEX idx_livros_isbn (isbn),
    INDEX idx_livros_categoria (categoria_id)
) ENGINE=InnoDB;


CREATE TABLE livros_autores (
    livro_id INT UNSIGNED NOT NULL,
    autor_id INT UNSIGNED NOT NULL,

    PRIMARY KEY (livro_id, autor_id),

    CONSTRAINT fk_livros_autores_livro
        FOREIGN KEY (livro_id)
        REFERENCES livros(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_livros_autores_autor
        FOREIGN KEY (autor_id)
        REFERENCES autores(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE emprestimos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    livro_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    funcionario_id INT UNSIGNED NULL,
    data_emprestimo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_devolucao_prevista DATE NOT NULL,
    status TINYINT UNSIGNED NOT NULL DEFAULT 0,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_emprestimos_livro
        FOREIGN KEY (livro_id)
        REFERENCES livros(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_emprestimos_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_emprestimos_funcionario
        FOREIGN KEY (funcionario_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_emprestimos_status (status),
    INDEX idx_emprestimos_devolucao (data_devolucao_prevista),
    INDEX idx_emprestimos_usuario (usuario_id)
) ENGINE=InnoDB;


CREATE TABLE multas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emprestimo_id INT UNSIGNED NOT NULL UNIQUE,
    valor DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    valor_pago DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('pendente', 'paga', 'cancelada') NOT NULL DEFAULT 'pendente',
    motivo VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_multas_emprestimo
        FOREIGN KEY (emprestimo_id)
        REFERENCES emprestimos(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_multas_status (status)
) ENGINE=InnoDB;