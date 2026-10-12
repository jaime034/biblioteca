-- Opcional: acervo inicial para apresentar o projeto. Sem contas ou senhas prontas.
USE biblioteca;
START TRANSACTION;
INSERT IGNORE INTO categorias (nome, descricao) VALUES
('Literatura Brasileira', 'Obras e autores da literatura nacional.'),
('Romance', 'Romances clássicos e contemporâneos.'),
('Tecnologia', 'Programação, informática e tecnologia.'),
('Fantasia', 'Mundos imaginários e histórias fantásticas.');

INSERT INTO autores (nome) SELECT 'Machado de Assis' WHERE NOT EXISTS (SELECT 1 FROM autores WHERE nome='Machado de Assis');
INSERT INTO autores (nome) SELECT 'Robert C. Martin' WHERE NOT EXISTS (SELECT 1 FROM autores WHERE nome='Robert C. Martin');
INSERT INTO autores (nome) SELECT 'George Orwell' WHERE NOT EXISTS (SELECT 1 FROM autores WHERE nome='George Orwell');
INSERT INTO autores (nome) SELECT 'Antoine de Saint-Exupéry' WHERE NOT EXISTS (SELECT 1 FROM autores WHERE nome='Antoine de Saint-Exupéry');
INSERT INTO autores (nome) SELECT 'J. R. R. Tolkien' WHERE NOT EXISTS (SELECT 1 FROM autores WHERE nome='J. R. R. Tolkien');
INSERT INTO autores (nome) SELECT 'Jane Austen' WHERE NOT EXISTS (SELECT 1 FROM autores WHERE nome='Jane Austen');

-- ISBN, editora e ano da edição não são preenchidos: variam conforme o exemplar.
INSERT INTO livros (titulo,descricao,categoria_id,quantidade_total,quantidade_disponivel)
SELECT 'Dom Casmurro','Um clássico sobre memória, afeto e dúvida.',id,6,6 FROM categorias WHERE nome='Literatura Brasileira'
AND NOT EXISTS (SELECT 1 FROM livros WHERE titulo='Dom Casmurro');
INSERT INTO livros (titulo,descricao,categoria_id,quantidade_total,quantidade_disponivel)
SELECT 'Memórias Póstumas de Brás Cubas','Uma narrativa irreverente sobre a sociedade brasileira.',id,4,4 FROM categorias WHERE nome='Literatura Brasileira'
AND NOT EXISTS (SELECT 1 FROM livros WHERE titulo='Memórias Póstumas de Brás Cubas');
INSERT INTO livros (titulo,descricao,categoria_id,quantidade_total,quantidade_disponivel)
SELECT 'Código Limpo','Boas práticas para escrever e manter código.',id,5,5 FROM categorias WHERE nome='Tecnologia'
AND NOT EXISTS (SELECT 1 FROM livros WHERE titulo='Código Limpo');
INSERT INTO livros (titulo,descricao,categoria_id,quantidade_total,quantidade_disponivel)
SELECT '1984','Uma distopia sobre poder, vigilância e liberdade.',id,7,7 FROM categorias WHERE nome='Romance'
AND NOT EXISTS (SELECT 1 FROM livros WHERE titulo='1984');
INSERT INTO livros (titulo,descricao,categoria_id,quantidade_total,quantidade_disponivel)
SELECT 'O Pequeno Príncipe','Uma história sobre amizade e o olhar para o mundo.',id,8,8 FROM categorias WHERE nome='Romance'
AND NOT EXISTS (SELECT 1 FROM livros WHERE titulo='O Pequeno Príncipe');
INSERT INTO livros (titulo,descricao,categoria_id,quantidade_total,quantidade_disponivel)
SELECT 'O Hobbit','A inesperada aventura de Bilbo Bolseiro.',id,3,3 FROM categorias WHERE nome='Fantasia'
AND NOT EXISTS (SELECT 1 FROM livros WHERE titulo='O Hobbit');
INSERT INTO livros (titulo,descricao,categoria_id,quantidade_total,quantidade_disponivel)
SELECT 'Orgulho e Preconceito','Encontros, escolhas e convenções sociais.',id,4,4 FROM categorias WHERE nome='Romance'
AND NOT EXISTS (SELECT 1 FROM livros WHERE titulo='Orgulho e Preconceito');

INSERT IGNORE INTO livros_autores (livro_id,autor_id)
SELECT l.id, MIN(a.id) FROM livros l JOIN autores a ON
((l.titulo='Dom Casmurro' AND a.nome='Machado de Assis')
OR (l.titulo='Memórias Póstumas de Brás Cubas' AND a.nome='Machado de Assis')
OR (l.titulo='Código Limpo' AND a.nome='Robert C. Martin')
OR (l.titulo='1984' AND a.nome='George Orwell')
OR (l.titulo='O Pequeno Príncipe' AND a.nome='Antoine de Saint-Exupéry')
OR (l.titulo='O Hobbit' AND a.nome='J. R. R. Tolkien')
OR (l.titulo='Orgulho e Preconceito' AND a.nome='Jane Austen'))
GROUP BY l.id;
COMMIT;
