-- =====================================================================
-- Biblioteca BookNet / N.L.R. -- BANCO DE DADOS COMPLETO (MySQL / MariaDB)
-- Como usar (XAMPP): phpMyAdmin -> Importar -> escolha este arquivo.
-- * APAGA o banco biblioteca_nlr existente e recria do zero.
-- * Cria o banco biblioteca_nlr e TODAS as 24 tabelas do sistema.
-- * Pode ser importado mais de uma vez: usa IF NOT EXISTS / INSERT IGNORE,
--   então não apaga nem duplica dados já existentes.
-- * Login de demonstração: admin@bibliotecanlr.com / 12345678
--   (alunos de exemplo usam a mesma senha)
-- =====================================================================
SET NAMES utf8mb4;
DROP DATABASE IF EXISTS biblioteca_nlr;
CREATE DATABASE IF NOT EXISTS biblioteca_nlr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_nlr;
CREATE TABLE IF NOT EXISTS usuarios(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,nome VARCHAR(150) NOT NULL,email VARCHAR(150) NOT NULL UNIQUE,senha VARCHAR(255) NOT NULL,tipo ENUM('aluno','administrador','gestor','bibliotecario') NOT NULL DEFAULT 'aluno',telefone VARCHAR(30),ra VARCHAR(50) UNIQUE,turma VARCHAR(80),curso VARCHAR(120),foto VARCHAR(255) NULL,status ENUM('ativo','bloqueado') NOT NULL DEFAULT 'ativo',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX(tipo),INDEX(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS categorias(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,nome VARCHAR(100) NOT NULL UNIQUE,descricao TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS livros(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,codigo_barras VARCHAR(80) UNIQUE,isbn VARCHAR(30),titulo VARCHAR(255) NOT NULL,autor VARCHAR(180) NOT NULL,categoria VARCHAR(100),editora VARCHAR(150),ano_publicacao SMALLINT UNSIGNED,quantidade INT UNSIGNED NOT NULL DEFAULT 0,quantidade_disponivel INT UNSIGNED NOT NULL DEFAULT 0,descricao TEXT,biografia_autor TEXT,capa VARCHAR(255),arquivo_pdf VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX(titulo),INDEX(autor),INDEX(categoria)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS emprestimos(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,usuario_id INT UNSIGNED NOT NULL,livro_id INT UNSIGNED NOT NULL,data_emprestimo DATE NOT NULL,data_prevista_devolucao DATE NOT NULL,data_devolucao DATE NULL,status ENUM('ativo','devolvido','atrasado','cancelado') NOT NULL DEFAULT 'ativo',observacao TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,CONSTRAINT fk_emp_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id),CONSTRAINT fk_emp_livro FOREIGN KEY(livro_id) REFERENCES livros(id),INDEX(status),INDEX(data_prevista_devolucao)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO usuarios(nome,email,senha,tipo,telefone,ra,turma,curso) VALUES
('Administrador N.L.R.','admin@bibliotecanlr.com','$2y$12$eOOTQGll7oW0d00psnsqFOSfIRy5mswm5ZpI.eXOuH/0kBw.rimhW','administrador','(11) 99999-0000','ADM001',NULL,NULL),
('Ana Souza','ana.souza@exemplo.com','$2y$12$eOOTQGll7oW0d00psnsqFOSfIRy5mswm5ZpI.eXOuH/0kBw.rimhW','aluno','(11) 90000-0001','2024001','1º A','Ensino Médio'),
('Bruno Lima','bruno.lima@exemplo.com','$2y$12$eOOTQGll7oW0d00psnsqFOSfIRy5mswm5ZpI.eXOuH/0kBw.rimhW','aluno','(11) 90000-0002','2024002','2º B','Ensino Médio');
INSERT IGNORE INTO livros(codigo_barras,isbn,titulo,autor,categoria,editora,ano_publicacao,quantidade,quantidade_disponivel,descricao) VALUES
('NLR001','9788535902777','O Pequeno Príncipe','Antoine de Saint-Exupéry','Literatura','Agir',2015,4,4,'Uma fábula clássica sobre amizade, afeto e o essencial.'),('NLR002','9788535914848','Dom Casmurro','Machado de Assis','Literatura Brasileira','Companhia das Letras',2016,3,3,'O célebre romance de Bento Santiago e Capitu.'),('NLR003','9788532530783','A Hora da Estrela','Clarice Lispector','Literatura Brasileira','Rocco',2020,2,2,'A trajetória de Macabéa narrada com delicadeza e profundidade.'),('NLR004','9788595081512','Torto Arado','Itamar Vieira Junior','Literatura Brasileira','Todavia',2019,3,3,'Saga de duas irmãs no sertão baiano.'),('NLR005','9788535920504','1984','George Orwell','Ficção','Companhia das Letras',2009,4,4,'Uma distopia sobre vigilância e liberdade.'),('NLR006','9788578277108','Extraordinário','R. J. Palacio','Juvenil','Intrínseca',2013,5,5,'Uma história de gentileza e aceitação.'),('NLR007','9788525056584','Harry Potter e a Pedra Filosofal','J. K. Rowling','Fantasia','Rocco',2017,5,5,'O começo da jornada de Harry Potter.'),('NLR008','9788574068788','Sapiens','Yuval Noah Harari','História','L&PM',2020,2,2,'Uma breve história da humanidade.'),('NLR009','9788539004118','Capitães da Areia','Jorge Amado','Literatura Brasileira','Companhia das Letras',2009,3,3,'A vida de meninos em situação de rua em Salvador.'),('NLR010','9788520935662','O Cortiço','Aluísio Azevedo','Literatura Brasileira','Ática',2011,3,3,'Um marco do naturalismo brasileiro.');

-- BookNet: provas e ranking (incluído também no banco principal para instalações novas)
CREATE TABLE IF NOT EXISTS provas (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(255) NOT NULL,
 disciplina VARCHAR(120) NOT NULL,
 descricao TEXT,
 duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
 publicada TINYINT(1) NOT NULL DEFAULT 1,
 criado_por INT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS prova_perguntas (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 prova_id INT UNSIGNED NOT NULL,
 pergunta TEXT NOT NULL,
 alternativa_a VARCHAR(500) NOT NULL,
 alternativa_b VARCHAR(500) NOT NULL,
 alternativa_c VARCHAR(500) NOT NULL,
 alternativa_d VARCHAR(500) NOT NULL,
 correta ENUM('a','b','c','d') NOT NULL,
 FOREIGN KEY (prova_id) REFERENCES provas(id) ON DELETE CASCADE,
 INDEX idx_prova_perguntas_prova (prova_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS prova_tentativas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 prova_id INT UNSIGNED NOT NULL,
 usuario_id INT UNSIGNED NOT NULL,
 iniciado_em DATETIME NOT NULL,
 finalizado_em DATETIME NULL,
 acertos INT UNSIGNED NOT NULL DEFAULT 0,
 total INT UNSIGNED NOT NULL DEFAULT 0,
 nota DECIMAL(5,2) NOT NULL DEFAULT 0,
 status ENUM('em_andamento','finalizada','expirada') NOT NULL DEFAULT 'em_andamento',
 UNIQUE KEY uma_tentativa (prova_id, usuario_id),
 FOREIGN KEY (prova_id) REFERENCES provas(id) ON DELETE CASCADE,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 INDEX idx_prova_tentativas_usuario (usuario_id),
 INDEX idx_prova_tentativas_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS prova_respostas (
 tentativa_id BIGINT UNSIGNED NOT NULL,
 pergunta_id INT UNSIGNED NOT NULL,
 resposta ENUM('a','b','c','d') NOT NULL,
 PRIMARY KEY (tentativa_id, pergunta_id),
 FOREIGN KEY (tentativa_id) REFERENCES prova_tentativas(id) ON DELETE CASCADE,
 FOREIGN KEY (pergunta_id) REFERENCES prova_perguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabelas que antes dependiam de arquivos de expansão separados.
CREATE TABLE IF NOT EXISTS chat_mensagens (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT UNSIGNED NOT NULL, sala_id INT UNSIGNED NULL,
 mensagem VARCHAR(500) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_chat_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE, INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS chat_privado (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, remetente_id INT UNSIGNED NOT NULL, destinatario_id INT UNSIGNED NOT NULL,
 mensagem VARCHAR(500) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(remetente_id), INDEX(destinatario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS chat_salas (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,nome VARCHAR(120) NOT NULL,descricao VARCHAR(255),criado_por INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(criado_por) REFERENCES usuarios(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS notificacoes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,usuario_id INT UNSIGNED NOT NULL,titulo VARCHAR(150) NOT NULL,mensagem VARCHAR(500) NOT NULL,link VARCHAR(255),lida TINYINT(1) NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,INDEX(usuario_id,lida)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS reservas (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,usuario_id INT UNSIGNED NOT NULL,livro_id INT UNSIGNED NOT NULL,status ENUM('ativa','atendida','cancelada') NOT NULL DEFAULT 'ativa',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,FOREIGN KEY(livro_id) REFERENCES livros(id) ON DELETE CASCADE,UNIQUE KEY reserva_ativa(usuario_id,livro_id,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS favoritos (usuario_id INT UNSIGNED NOT NULL,livro_id INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(usuario_id,livro_id),FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,FOREIGN KEY(livro_id) REFERENCES livros(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS avaliacoes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,usuario_id INT UNSIGNED NOT NULL,livro_id INT UNSIGNED NOT NULL,nota TINYINT UNSIGNED NOT NULL,comentario VARCHAR(500),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,FOREIGN KEY(livro_id) REFERENCES livros(id) ON DELETE CASCADE,UNIQUE KEY avaliacao_unica(usuario_id,livro_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS leitura_progresso (usuario_id INT UNSIGNED NOT NULL,livro_id INT UNSIGNED NOT NULL,aberturas INT UNSIGNED NOT NULL DEFAULT 1,ultima_leitura TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(usuario_id,livro_id),FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,FOREIGN KEY(livro_id) REFERENCES livros(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quizzes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,titulo VARCHAR(255) NOT NULL,disciplina VARCHAR(120) NOT NULL,descricao TEXT,criado_por INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(criado_por) REFERENCES usuarios(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quiz_perguntas (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,quiz_id INT UNSIGNED NOT NULL,pergunta TEXT NOT NULL,alternativa_a VARCHAR(500) NOT NULL,alternativa_b VARCHAR(500) NOT NULL,alternativa_c VARCHAR(500) NOT NULL,alternativa_d VARCHAR(500) NOT NULL,correta ENUM('a','b','c','d') NOT NULL,FOREIGN KEY(quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quiz_respostas (usuario_id INT UNSIGNED NOT NULL,pergunta_id INT UNSIGNED NOT NULL,resposta ENUM('a','b','c','d') NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(usuario_id,pergunta_id),FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,FOREIGN KEY(pergunta_id) REFERENCES quiz_perguntas(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quiz_tentativas (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,quiz_id INT UNSIGNED NOT NULL,usuario_id INT UNSIGNED NOT NULL,iniciado_em DATETIME NOT NULL,finalizado_em DATETIME NOT NULL,acertos INT UNSIGNED NOT NULL DEFAULT 0,total INT UNSIGNED NOT NULL DEFAULT 0,nota DECIMAL(5,2) NOT NULL DEFAULT 0,FOREIGN KEY(quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,INDEX(usuario_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS materiais_estudo (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,titulo VARCHAR(255) NOT NULL,disciplina VARCHAR(120) NOT NULL,descricao TEXT,link_externo VARCHAR(500),arquivo_pdf VARCHAR(255),criado_por INT UNSIGNED NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,FOREIGN KEY(criado_por) REFERENCES usuarios(id),INDEX(disciplina)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS clubes_livro (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,nome VARCHAR(120) NOT NULL,descricao TEXT,criador_id INT UNSIGNED NOT NULL,criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(criador_id) REFERENCES usuarios(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS prova_ordens_alternativas (tentativa_id BIGINT UNSIGNED NOT NULL,pergunta_id INT UNSIGNED NOT NULL,ordem CHAR(4) NOT NULL,PRIMARY KEY(tentativa_id,pergunta_id),FOREIGN KEY(tentativa_id) REFERENCES prova_tentativas(id) ON DELETE CASCADE,FOREIGN KEY(pergunta_id) REFERENCES prova_perguntas(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- BookNet: embaralhamento das alternativas dos quizzes por aluno
CREATE TABLE IF NOT EXISTS quiz_ordens_alternativas (
 usuario_id INT UNSIGNED NOT NULL,
 quiz_id INT UNSIGNED NOT NULL,
 pergunta_id INT UNSIGNED NOT NULL,
 ordem CHAR(4) NOT NULL,
 PRIMARY KEY (usuario_id, pergunta_id),
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
 FOREIGN KEY (pergunta_id) REFERENCES quiz_perguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
