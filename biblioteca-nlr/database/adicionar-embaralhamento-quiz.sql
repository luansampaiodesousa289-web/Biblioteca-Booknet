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
);
