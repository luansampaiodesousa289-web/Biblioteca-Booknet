-- Redefine o administrador do BookNet para uma senha conhecida.
-- Execute dentro do phpMyAdmin, no banco biblioteca_nlr.
USE biblioteca_nlr;

UPDATE usuarios
SET senha = '$2y$12$eOOTQGll7oW0d00psnsqFOSfIRy5mswm5ZpI.eXOuH/0kBw.rimhW',
    tipo = 'administrador',
    status = 'ativo'
WHERE email = 'admin@bibliotecanlr.com';

-- Se o usuário não existir, crie-o.
INSERT INTO usuarios (nome, email, senha, tipo, telefone, ra, status)
SELECT 'Administrador N.L.R.', 'admin@bibliotecanlr.com', '$2y$12$eOOTQGll7oW0d00psnsqFOSfIRy5mswm5ZpI.eXOuH/0kBw.rimhW', 'administrador', '(11) 99999-0000', 'ADM001', 'ativo'
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'admin@bibliotecanlr.com');

-- Login: admin@bibliotecanlr.com
-- Senha: 12345678
