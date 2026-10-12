<?php
declare(strict_types=1);

// As operações de estoque acontecem em transações e bloqueiam o livro.
// Assim, dois empréstimos simultâneos não usam o mesmo último exemplar.
function transaction(callable $operation)
{
    global $pdo;
    $pdo->beginTransaction();
    try {
        $result = $operation();
        $pdo->commit();
        return $result;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function save_book(array $data, int $id = 0): int
{
    require_staff();
    $title = require_text($data, 'titulo', 'Título', 255);
    $isbn = optional_text($data, 'isbn', 'ISBN', 20);
    $description = optional_text($data, 'descricao', 'Descrição', 255);
    $publisher = optional_text($data, 'editora', 'Editora', 150);
    $total = positive_id(input($data, 'quantidade_total'));
    if ($total < 1 || $total > 1000000) {
        throw new DomainException('Informe de 1 a 1.000.000 de exemplares.');
    }
    $yearValue = input($data, 'ano_publicacao');
    $year = $yearValue === '' ? null : positive_id($yearValue);
    if ($year !== null && ($year < 1901 || $year > 2155)) {
        throw new DomainException('O ano deve estar entre 1901 e 2155.');
    }
    $categoryId = positive_id(input($data, 'categoria_id'));
    $newCategory = optional_text($data, 'nova_categoria', 'Nova categoria', 100);
    $authorText = require_text($data, 'autores', 'Autor(es)', 1000);
    $authors = array_values(array_unique(array_filter(array_map('trim', explode(',', $authorText)))));
    if (!$authors || count($authors) > 10) {
        throw new DomainException('Informe entre 1 e 10 autores, separados por vírgula.');
    }
    foreach ($authors as $author) {
        if (text_length($author) > 150) {
            throw new DomainException('Cada nome de autor pode ter até 150 caracteres.');
        }
    }

    return transaction(function () use ($id, $title, $isbn, $description, $publisher, $total, $year, $categoryId, $newCategory, $authors): int {
        global $pdo;
        $existing = $id ? one('SELECT * FROM livros WHERE id = :id FOR UPDATE', ['id' => $id]) : null;
        if ($id && !$existing) {
            throw new DomainException('Livro não encontrado.');
        }
        $borrowed = $existing ? (int) $existing['quantidade_total'] - (int) $existing['quantidade_disponivel'] : 0;
        if ($total < $borrowed) {
            throw new DomainException('Há ' . $borrowed . ' exemplares emprestados. O total não pode ser menor que esse número.');
        }
        if ($newCategory !== null) {
            $category = one('SELECT id FROM categorias WHERE nome = :nome', ['nome' => $newCategory]);
            if (!$category) {
                query('INSERT INTO categorias (nome) VALUES (:nome)', ['nome' => $newCategory]);
                $categoryId = (int) $pdo->lastInsertId();
            } else {
                $categoryId = (int) $category['id'];
            }
        }
        if (!one('SELECT id FROM categorias WHERE id = :id', ['id' => $categoryId])) {
            throw new DomainException('Selecione uma categoria ou preencha uma nova.');
        }
        $params = ['titulo' => $title, 'isbn' => $isbn, 'descricao' => $description, 'editora' => $publisher, 'ano' => $year, 'categoria' => $categoryId, 'total' => $total, 'disponivel' => $total - $borrowed];
        if ($id) {
            $params['id'] = $id;
            query('UPDATE livros SET titulo=:titulo, isbn=:isbn, descricao=:descricao, editora=:editora, ano_publicacao=:ano, categoria_id=:categoria, quantidade_total=:total, quantidade_disponivel=:disponivel WHERE id=:id', $params);
            query('DELETE FROM livros_autores WHERE livro_id = :id', ['id' => $id]);
        } else {
            query('INSERT INTO livros (titulo,isbn,descricao,editora,ano_publicacao,categoria_id,quantidade_total,quantidade_disponivel) VALUES (:titulo,:isbn,:descricao,:editora,:ano,:categoria,:total,:disponivel)', $params);
            $id = (int) $pdo->lastInsertId();
        }
        foreach ($authors as $authorName) {
            $author = one('SELECT id FROM autores WHERE nome = :nome ORDER BY id LIMIT 1', ['nome' => $authorName]);
            if (!$author) {
                query('INSERT INTO autores (nome) VALUES (:nome)', ['nome' => $authorName]);
                $authorId = (int) $pdo->lastInsertId();
            } else {
                $authorId = (int) $author['id'];
            }
            query('INSERT IGNORE INTO livros_autores (livro_id, autor_id) VALUES (:livro,:autor)', ['livro' => $id, 'autor' => $authorId]);
        }
        return $id;
    });
}

function delete_book(int $id): void
{
    require_staff();
    transaction(function () use ($id): void {
        if (!one('SELECT id FROM livros WHERE id = :id FOR UPDATE', ['id' => $id])) {
            throw new DomainException('Livro não encontrado.');
        }
        if (count_value('SELECT COUNT(*) FROM emprestimos WHERE livro_id = :id', ['id' => $id])) {
            throw new DomainException('Este livro tem histórico de empréstimos e deve ser mantido no acervo.');
        }
        query('DELETE FROM livros WHERE id = :id', ['id' => $id]);
    });
}

function save_user(array $data, int $id = 0): int
{
    $acting = require_staff();
    $name = require_text($data, 'nome', 'Nome', 150);
    $email = require_text($data, 'email', 'E-mail', 150);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new DomainException('Informe um e-mail válido.');
    }
    $cpf = preg_replace('/[.\-\s]/', '', input($data, 'cpf'));
    if (!valid_cpf($cpf)) {
        throw new DomainException('Informe um CPF válido, com 11 dígitos.');
    }
    $phone = optional_text($data, 'telefone', 'Telefone', 20);
    $role = input($data, 'perfil', 'leitor');
    $statusValue = input($data, 'status', '1');
    if (!in_array($role, ['admin', 'bibliotecario', 'leitor'], true) || !in_array($statusValue, ['0', '1'], true)) {
        throw new DomainException('Perfil ou status inválido.');
    }
    if (!is_admin() && $role !== 'leitor') {
        throw new DomainException('Somente o administrador pode cadastrar contas da equipe.');
    }
    $password = isset($data['senha']) && is_string($data['senha']) ? $data['senha'] : '';
    if ($password !== '') {
        validate_password($password);
    }
    if (!$id && $role !== 'leitor' && $password === '') {
        throw new DomainException('Contas da equipe precisam de uma senha.');
    }

    return transaction(function () use ($data, $id, $acting, $name, $email, $cpf, $phone, $role, $statusValue, $password): int {
        global $pdo;
        // Bloqueio compartilhado por todas as alterações de usuários.
        $admins = rows("SELECT id FROM usuarios WHERE perfil='admin' AND status=1 ORDER BY id FOR UPDATE");
        $existing = $id ? one('SELECT * FROM usuarios WHERE id=:id FOR UPDATE', ['id' => $id]) : null;
        if ($id && !$existing) {
            throw new DomainException('Usuário não encontrado.');
        }
        if ($existing && !is_admin() && $existing['perfil'] !== 'leitor') {
            throw new DomainException('Somente o administrador pode alterar contas da equipe.');
        }
        $status = (int) $statusValue;
        if ($id === (int) $acting['id'] && ($status !== 1 || $role !== $acting['perfil'])) {
            throw new DomainException('Você não pode desativar ou mudar o perfil da sua própria conta.');
        }
        if ($existing && $existing['perfil'] === 'admin' && (int) $existing['status'] === 1 && ($role !== 'admin' || $status !== 1) && count($admins) <= 1) {
            throw new DomainException('Mantenha pelo menos um administrador ativo.');
        }
        if ($existing && $role !== 'leitor' && !$existing['senha'] && $password === '') {
            throw new DomainException('Defina uma senha para a conta da equipe.');
        }
        $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : ($existing['senha'] ?? null);
        $params = ['nome' => $name, 'cpf' => $cpf, 'email' => $email, 'telefone' => $phone, 'senha' => $hash, 'perfil' => $role, 'status' => $status];
        if ($id) {
            $params['id'] = $id;
            query('UPDATE usuarios SET nome=:nome,cpf=:cpf,email=:email,telefone=:telefone,senha=:senha,perfil=:perfil,status=:status WHERE id=:id', $params);
        } else {
            query('INSERT INTO usuarios (nome,cpf,email,telefone,senha,perfil,status) VALUES (:nome,:cpf,:email,:telefone,:senha,:perfil,:status)', $params);
            $id = (int) $pdo->lastInsertId();
        }
        return $id;
    });
}

function create_loan(array $data): int
{
    global $config;
    $acting = require_staff();
    $bookId = positive_id(input($data, 'livro_id'));
    $userId = positive_id(input($data, 'usuario_id'));
    $due = input($data, 'data_devolucao_prevista');
    if (!valid_date($due) || $due < date('Y-m-d')) {
        throw new DomainException('A devolução prevista deve ser hoje ou uma data futura válida.');
    }
    $notes = optional_text($data, 'observacoes', 'Observações', 2000);
    return transaction(function () use ($bookId, $userId, $due, $notes, $acting): int {
        global $pdo;
        $book = one('SELECT * FROM livros WHERE id=:id FOR UPDATE', ['id' => $bookId]);
        if (!$book || (int) $book['quantidade_disponivel'] < 1) {
            throw new DomainException('Este livro não tem exemplares disponíveis.');
        }
        $borrower = one('SELECT id, status, perfil FROM usuarios WHERE id=:id FOR UPDATE', ['id' => $userId]);
        if (!$borrower || (int) $borrower['status'] !== 1 || $borrower['perfil'] !== 'leitor') {
            throw new DomainException('Selecione um leitor ativo.');
        }
        if (count_value('SELECT COUNT(*) FROM emprestimos WHERE livro_id=:livro AND usuario_id=:usuario AND status=0', ['livro' => $bookId, 'usuario' => $userId])) {
            throw new DomainException('Este leitor já tem um empréstimo ativo deste livro.');
        }
        query('INSERT INTO emprestimos (livro_id,usuario_id,funcionario_id,data_emprestimo,data_devolucao_prevista,observacoes) VALUES (:livro,:usuario,:funcionario,:agora,:prevista,:observacoes)', [
            'livro' => $bookId, 'usuario' => $userId, 'funcionario' => (int) $acting['id'], 'agora' => date('Y-m-d H:i:s'), 'prevista' => $due, 'observacoes' => $notes,
        ]);
        $id = (int) $pdo->lastInsertId();
        query('UPDATE livros SET quantidade_disponivel=quantidade_disponivel-1 WHERE id=:id', ['id' => $bookId]);
        return $id;
    });
}

function return_loan(int $id): int
{
    require_staff();
    return transaction(function () use ($id): int {
        global $config;
        $lookup = one('SELECT livro_id FROM emprestimos WHERE id=:id', ['id' => $id]);
        if (!$lookup) {
            throw new DomainException('Empréstimo não encontrado.');
        }
        // Mantém a mesma ordem de bloqueios do cadastro: livro, empréstimo.
        one('SELECT id FROM livros WHERE id=:id FOR UPDATE', ['id' => (int) $lookup['livro_id']]);
        $loan = one('SELECT * FROM emprestimos WHERE id=:id FOR UPDATE', ['id' => $id]);
        if ((int) $loan['status'] !== 0) {
            throw new DomainException('A devolução já foi registrada.');
        }
        $today = new DateTimeImmutable('today');
        $due = new DateTimeImmutable($loan['data_devolucao_prevista']);
        $days = $today > $due ? (int) $due->diff($today)->days : 0;
        $cents = $days * max(0, (int) $config['multa_por_dia_centavos']);
        query('UPDATE emprestimos SET status=1,data_devolucao_real=:agora WHERE id=:id', ['agora' => date('Y-m-d H:i:s'), 'id' => $id]);
        query('UPDATE livros SET quantidade_disponivel=quantidade_disponivel+1 WHERE id=:id AND quantidade_disponivel < quantidade_total', ['id' => (int) $loan['livro_id']]);
        if ($cents > 0 && !one('SELECT id FROM multas WHERE emprestimo_id=:id FOR UPDATE', ['id' => $id])) {
            query('INSERT INTO multas (emprestimo_id,valor,motivo) VALUES (:id,:valor,:motivo)', ['id' => $id, 'valor' => number_format($cents / 100, 2, '.', ''), 'motivo' => $days . ' dia(s) de atraso na devolução.']);
        }
        return $cents;
    });
}

function settle_fine(int $id, string $action): void
{
    require_staff();
    if (!in_array($action, ['pagar', 'cancelar'], true) || ($action === 'cancelar' && !is_admin())) {
        throw new DomainException('Ação não permitida.');
    }
    transaction(function () use ($id, $action): void {
        $fine = one('SELECT * FROM multas WHERE id=:id FOR UPDATE', ['id' => $id]);
        if (!$fine || $fine['status'] !== 'pendente') {
            throw new DomainException('Esta multa não está pendente.');
        }
        if ($action === 'pagar') {
            query("UPDATE multas SET valor_pago=valor,status='paga' WHERE id=:id", ['id' => $id]);
        } else {
            query("UPDATE multas SET status='cancelada' WHERE id=:id", ['id' => $id]);
        }
    });
}
