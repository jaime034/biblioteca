-- Para quem JÁ importou codigo(1).sql. Não remove tabelas nem dados.
-- Pode ser executado novamente: só acrescenta os campos que ainda não existem.
USE biblioteca;

SET @perfil_existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'perfil');
SET @sql_perfil = IF(@perfil_existe = 0,
    'ALTER TABLE usuarios ADD COLUMN perfil ENUM(''admin'',''bibliotecario'',''leitor'') NOT NULL DEFAULT ''leitor'' AFTER senha',
    'SELECT ''O campo perfil já existe'' AS informacao');
PREPARE migrar_perfil FROM @sql_perfil;
EXECUTE migrar_perfil;
DEALLOCATE PREPARE migrar_perfil;

SET @devolucao_existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emprestimos' AND COLUMN_NAME = 'data_devolucao_real');
SET @sql_devolucao = IF(@devolucao_existe = 0,
    'ALTER TABLE emprestimos ADD COLUMN data_devolucao_real DATETIME NULL AFTER data_devolucao_prevista',
    'SELECT ''O campo data_devolucao_real já existe'' AS informacao');
PREPARE migrar_devolucao FROM @sql_devolucao;
EXECUTE migrar_devolucao;
DEALLOCATE PREPARE migrar_devolucao;
