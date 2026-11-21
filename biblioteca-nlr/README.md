# Biblioteca BookNet — versão revisada

Sistema escolar em PHP 8+ e MySQL/MariaDB. As páginas `.php` geram o HTML dinamicamente; por isso o projeto original não tinha arquivos `.html` separados. Foi incluído `index.html` como entrada alternativa, que encaminha para `index.php`.

## Instalação limpa (banco novo)
1. Extraia a pasta `biblioteca-nlr` dentro de `htdocs` do XAMPP.
2. Inicie Apache e MySQL.
3. No phpMyAdmin, importe `database/biblioteca.sql` (banco completo, 24 tabelas; pode ser reimportado sem duplicar dados). Esse arquivo cria o banco `biblioteca_nlr`, tabelas principais e tabelas dos módulos (chat, estudos, clubes, quizzes e provas).
4. Confira `config/database.php` (padrão XAMPP: host `localhost`, usuário `root`, senha vazia).
5. Acesse `http://localhost/biblioteca-nlr/` (ou a pasta em que você extraiu o projeto).

## Atualizar banco já existente
1. **Faça backup antes**: phpMyAdmin → banco `biblioteca_nlr` → Exportar → Rápido → SQL.
2. Selecione o banco existente e importe `database/corrigir_banco_existente.sql`. O script é aditivo: cria tabelas que estiverem faltando e adiciona colunas ausentes, sem apagar os registros existentes.
3. Não importe `biblioteca.sql` sobre um banco já em uso: ele é o instalador para banco novo e contém dados de demonstração.

## Caminho do site
Os links agora identificam automaticamente a pasta em que o projeto foi instalado (por exemplo `/biblioteca-nlr`, `/booknet` ou outra subpasta), inclusive nas áreas `admin`, `aluno` e `api`.

## Verificações realizadas nesta revisão
- 59 arquivos PHP verificados com `php -l`: sem erros de sintaxe.
- Corrigido o caminho fixo que quebrava links quando a pasta não se chamava `biblioteca-nlr`.
- Corrigida a ordem de criação do banco: tabelas de quizzes são criadas antes das tabelas que possuem chaves estrangeiras para elas.
- Incluídas no instalador as tabelas que estavam somente em scripts de expansão, além de `prova_ordens_alternativas`.
- Criado script de atualização compatível com banco já existente e entrada HTML alternativa.

## Observações
A análise estática não substitui um teste de execução conectado ao seu MySQL real. Usuário administrador de demonstração: admin@bibliotecanlr.com / 12345678. Para um banco já existente, execute database/redefinir_admin.sql no phpMyAdmin.

## Compatibilidade com PC e celular
A interface usa layout responsivo para telas de computador, tablets e celulares.
O menu público e o menu lateral se adaptam a telas menores; tabelas podem ser
deslizadas horizontalmente no celular; formulários, cartões e páginas de detalhes
se reorganizam para evitar rolagem horizontal. O projeto deve ser testado no
navegador após instalação, especialmente nas páginas administrativas.

## Correções da v3
- `database/biblioteca.sql`: o `INSERT` do aluno Bruno Lima tinha 7 valores para 8 colunas (faltava o RA), o que fazia a importação parar com erro no phpMyAdmin. Corrigido (RA 2024002).
- O banco agora usa `CREATE TABLE IF NOT EXISTS` e `INSERT IGNORE`: pode ser importado novamente sem erro de "tabela já existe" e sem duplicar dados.
- `admin/livros.php`: excluir um livro que já teve empréstimos devolvidos causava erro fatal de chave estrangeira; agora mostra uma mensagem clara.
