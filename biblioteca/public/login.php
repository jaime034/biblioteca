<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/auth.php';
if (current_user()) {
    redirect(is_staff() ? 'index.php' : 'livros.php');
}
if (!has_admin()) {
    redirect('instalar.php');
}
$error = '';
$notice = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$email = input($_POST, 'email');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $password = isset($_POST['senha']) && is_string($_POST['senha']) ? $_POST['senha'] : '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) > 72 || strpos($password, "\0") !== false || !attempt_login($email, $password)) {
            throw new DomainException('E-mail ou senha incorretos, ou conta inativa.');
        }
        redirect('index.php');
    } catch (Throwable $exception) {
        $error = friendly_error($exception);
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Entrar · Biblioteca</title><link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>"><link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml"></head>
<body class="auth-page">
    <main class="auth-layout">
        <section class="auth-story"><a class="brand" href="<?= e(url('login.php')) ?>"><span class="brand-icon"><?= icon('book') ?></span><span><strong>Biblioteca</strong><small>Sistema de Gestão</small></span></a><div class="auth-message"><span class="eyebrow">CONHECIMENTO QUE CIRCULA</span><h1>Grandes histórias.<br>Novas possibilidades.</h1><p>Um lugar para organizar o acervo, conectar leitores e cuidar de cada empréstimo.</p><div class="shelf-art" aria-hidden="true"><span class="shelf-book shelf-one">LITERATURA</span><span class="shelf-book shelf-two">IDEIAS</span><span class="shelf-book shelf-three">HISTÓRIAS</span><span class="shelf-book shelf-four">CONHECIMENTO</span></div></div><small class="auth-story-footer">Organizar para compartilhar.</small></section>
        <section class="auth-form-side"><div class="auth-card"><span class="auth-symbol"><?= icon('lock') ?></span><span class="eyebrow">BEM-VINDO DE VOLTA</span><h2>Acesse sua biblioteca</h2><p>Entre com seu e-mail e senha para continuar.</p>
            <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <?php if ($notice): ?><div class="alert" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
            <form method="post" action="<?= e(url('login.php')) ?>" class="form-stack">
                <?= csrf_field() ?>
                <div class="field"><label for="email">E-mail</label><input type="email" id="email" name="email" maxlength="150" placeholder="seuemail@exemplo.com" autocomplete="username" value="<?= e($email) ?>" required autofocus></div>
                <div class="field"><label for="senha">Senha</label><input type="password" id="senha" name="senha" placeholder="Digite sua senha" autocomplete="current-password" required></div>
                <button class="button button-block" type="submit">Entrar <?= icon('arrow') ?></button>
            </form>
            <p class="auth-help">Precisa de acesso? Fale com a equipe da biblioteca.</p>
        </div><small class="auth-copyright">Biblioteca · Sistema de Gestão · <?= date('Y') ?></small></section>
    </main>
</body></html>
