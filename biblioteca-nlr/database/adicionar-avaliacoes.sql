-- BookNet: módulo completo de provas e histórico de avaliações
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
);
CREATE TABLE IF NOT EXISTS prova_perguntas (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 prova_id INT UNSIGNED NOT NULL,
 pergunta TEXT NOT NULL,
 alternativa_a VARCHAR(500) NOT NULL,
 alternativa_b VARCHAR(500) NOT NULL,
 alternativa_c VARCHAR(500) NOT NULL,
 alternativa_d VARCHAR(500) NOT NULL,
 correta ENUM('a','b','c','d') NOT NULL,
 FOREIGN KEY (prova_id) REFERENCES provas(id) ON DELETE CASCADE
);
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
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS prova_respostas (
 tentativa_id BIGINT UNSIGNED NOT NULL,
 pergunta_id INT UNSIGNED NOT NULL,
 resposta ENUM('a','b','c','d') NOT NULL,
 PRIMARY KEY (tentativa_id, pergunta_id),
 FOREIGN KEY (tentativa_id) REFERENCES prova_tentativas(id) ON DELETE CASCADE,
 FOREIGN KEY (pergunta_id) REFERENCES prova_perguntas(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS prova_ordens_alternativas (
 tentativa_id BIGINT UNSIGNED NOT NULL,
 pergunta_id INT UNSIGNED NOT NULL,
 ordem CHAR(4) NOT NULL,
 PRIMARY KEY (tentativa_id, pergunta_id),
 FOREIGN KEY (tentativa_id) REFERENCES prova_tentativas(id) ON DELETE CASCADE,
 FOREIGN KEY (pergunta_id) REFERENCES prova_perguntas(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS quiz_tentativas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 quiz_id INT UNSIGNED NOT NULL,
 usuario_id INT UNSIGNED NOT NULL,
 iniciado_em DATETIME NOT NULL,
 finalizado_em DATETIME NOT NULL,
 acertos INT UNSIGNED NOT NULL DEFAULT 0,
 total INT UNSIGNED NOT NULL DEFAULT 0,
 nota DECIMAL(5,2) NOT NULL DEFAULT 0,
 FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
CREATE INDEX idx_prova_tentativas_usuario ON prova_tentativas(usuario_id);
CREATE INDEX idx_quiz_tentativas_usuario ON quiz_tentativas(usuario_id);
