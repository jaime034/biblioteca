<?php
require __DIR__ . '/../app/bootstrap.php';
if (has_admin()) {
    redirect('login.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $lock = false;
    try {
        $name = require_text($_POST, 'nome', 'Nome', 150);
        $email = require_text($_POST, 'email', 'E-mail', 150);
        $cpf = preg_replace('/[.\-\s]/', '', input($_POST, 'cpf'));
        $password = isset($_POST['senha']) && is_string($_POST['senha']) ? $_POST['senha'] : '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !valid_cpf($cpf)) {
            throw new DomainException('Informe um e-mail e um CPF válidos.');
        }
        validate_password($password);
        $confirm = isset($_POST['confirmar_senha']) && is_string($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';
        if ($password !== $confirm) {
            throw new DomainException('As senhas não coincidem.');
        }
        $lock = (int) query("SELECT GET_LOCK(CONCAT(DATABASE(),'_instalacao'), 5)")->fetchColumn() === 1;
        if (!$lock || has_admin()) {
            throw new DomainException('A instalação já foi concluída ou está em andamento.');
        }
        query("INSERT INTO usuarios (nome,cpf,email,senha,perfil,status) VALUES (:nome,:cpf,:email,:senha,'admin',1)", ['nome' => $name, 'cpf' => $cpf, 'email' => $email, 'senha' => password_hash($password, PASSWORD_DEFAULT)]);
        flash('Administrador criado. Entre com o e-mail e a senha que você definiu.');
    } catch (Throwable $exception) {
        $error = friendly_error($exception);
    } finally {
        if ($lock) {
            query("SELECT RELEASE_LOCK(CONCAT(DATABASE(),'_instalacao'))");
        }
    }
    if ($error === '') {
        redirect('login.php');
    }
}
?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Primeiro acesso · Biblioteca</title><link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>"></head>
<body class="setup-page"><main class="setup-card"><span class="brand-icon"><?= icon('book') ?></span><span class="eyebrow">PRIMEIRO ACESSO</span><h1>Sua biblioteca começa aqui.</h1><p>Crie a primeira conta de administrador. Depois disso, esta etapa será fechada automaticamente.</p>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form-grid" action="<?= e(url('instalar.php')) ?>"><?= csrf_field() ?><div class="field field-full"><label for="nome">Nome completo</label><input id="nome" name="nome" maxlength="150" autocomplete="name" value="<?= e(input($_POST, 'nome')) ?>" required></div><div class="field"><label for="cpf">CPF</label><input id="cpf" name="cpf" maxlength="14" inputmode="numeric" placeholder="000.000.000-00" value="<?= e(input($_POST, 'cpf')) ?>" required></div><div class="field"><label for="email">E-mail</label><input type="email" id="email" name="email" maxlength="150" autocomplete="username" value="<?= e(input($_POST, 'email')) ?>" required></div><div class="field"><label for="senha">Senha</label><input type="password" id="senha" name="senha" minlength="8" autocomplete="new-password" required><small>Use pelo menos 8 caracteres.</small></div><div class="field"><label for="confirmar_senha">Confirme a senha</label><input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" autocomplete="new-password" required></div><div class="form-actions field-full"><button class="button" type="submit">Criar administrador <?= icon('arrow') ?></button></div></form></main></body></html>
