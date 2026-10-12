<?php
$layoutUser = require_login();
$layoutNotice = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$layoutMenu = [];
if (is_staff()) {
    $layoutMenu['dashboard'] = ['Dashboard', 'index.php', 'dashboard'];
}
$layoutMenu['livros'] = ['Livros', 'livros.php', 'book'];
if (is_staff()) {
    $layoutMenu['usuarios'] = ['Usuários', 'usuarios.php', 'users'];
}
$layoutMenu['emprestimos'] = [is_staff() ? 'Empréstimos' : 'Meus empréstimos', 'emprestimos.php', 'loan'];
$layoutMenu['multas'] = [is_staff() ? 'Multas' : 'Minhas multas', 'multas.php', 'coin'];
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Gestão do acervo, leitores e empréstimos da biblioteca.">
    <title><?= e($title ?? 'Biblioteca') ?> · Biblioteca</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body>
<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
<div class="app-layout">
    <aside class="sidebar" aria-label="Menu principal">
        <a class="brand" href="<?= e(url(is_staff() ? 'index.php' : 'livros.php')) ?>"><span class="brand-icon"><?= icon('book') ?></span><span><strong>Biblioteca</strong><small>Sistema de Gestão</small></span></a>
        <span class="nav-label">PRINCIPAL</span>
        <nav class="main-nav">
            <?php foreach ($layoutMenu as $navKey => [$navLabel, $navPath, $navSymbol]): ?>
                <a class="nav-link <?= ($active ?? '') === $navKey ? 'active' : '' ?>" href="<?= e(url($navPath)) ?>" <?= ($active ?? '') === $navKey ? 'aria-current="page"' : '' ?>><?= icon($navSymbol) ?><span><?= e($navLabel) ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom"><div class="sidebar-note"><?= icon('book') ?><p>Uma biblioteca.<br><strong>Muitas possibilidades.</strong></p></div><form action="<?= e(url('logout.php')) ?>" method="post"><?= csrf_field() ?><button class="logout-button" type="submit"><?= icon('logout') ?>Sair da conta</button></form></div>
    </aside>
    <div class="app-main">
        <header class="topbar"><span class="topbar-label">Painel da biblioteca</span><div class="profile"><span class="avatar"><?= e(initials($layoutUser['nome'])) ?></span><span><strong><?= e($layoutUser['nome']) ?></strong><small><?= e(role_label($layoutUser['perfil'])) ?></small></span></div></header>
        <main class="content" id="conteudo">
            <?php if ($layoutNotice): ?><div class="alert alert-<?= e($layoutNotice['type']) ?>" role="status"><?= e($layoutNotice['message']) ?></div><?php endif; ?>
