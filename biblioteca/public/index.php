<?php
require __DIR__ . '/../app/bootstrap.php';
require_login();
if (!is_staff()) {
    redirect('livros.php');
}
$title = 'Dashboard';
$active = 'dashboard';
$user = current_user();
$stock = one('SELECT COALESCE(SUM(quantidade_total),0) AS total, COALESCE(SUM(quantidade_disponivel),0) AS disponivel, COUNT(*) AS titulos FROM livros');
$stats = [
    ['Total de livros', $stock['total'], $stock['titulos'] . ' títulos no acervo', 'book'],
    ['Livros disponíveis', $stock['disponivel'], 'Exemplares para empréstimo', 'check'],
    ['Livros emprestados', count_value('SELECT COUNT(*) FROM emprestimos WHERE status=0'), 'Empréstimos em andamento', 'loan'],
    ['Livros atrasados', count_value('SELECT COUNT(*) FROM emprestimos WHERE status=0 AND data_devolucao_prevista < :hoje', ['hoje' => date('Y-m-d')]), 'Devoluções que precisam de atenção', 'clock'],
    ['Total de usuários', count_value('SELECT COUNT(*) FROM usuarios'), 'Leitores e equipe cadastrados', 'users'],
];
$recent = rows('SELECT e.*,l.titulo,u.nome FROM emprestimos e JOIN livros l ON l.id=e.livro_id JOIN usuarios u ON u.id=e.usuario_id ORDER BY e.data_emprestimo DESC,e.id DESC LIMIT 6');
$popular = rows('SELECT l.id,l.titulo,COUNT(e.id) AS total FROM livros l JOIN emprestimos e ON e.livro_id=l.id GROUP BY l.id,l.titulo ORDER BY total DESC,l.titulo LIMIT 5');
require __DIR__ . '/../app/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">VISÃO GERAL</span><h1>Bem-vindo, <?= e(explode(' ', $user['nome'])[0]) ?>!</h1><p>Acompanhe o dia a dia da sua biblioteca.</p></div><span class="date-label"><?= icon('clock') ?><?= date('d/m/Y') ?></span></div>
<section class="stats-grid" aria-label="Resumo da biblioteca"><?php foreach ($stats as [$label,$value,$detail,$symbol]): ?><article class="stat-card"><div class="stat-top"><span><?= e($label) ?></span><?= icon($symbol) ?></div><strong><?= fmt_number($value) ?></strong><small><?= e($detail) ?></small></article><?php endforeach; ?></section>
<div class="dashboard-grid"><section class="panel"><div class="panel-heading"><div><h2>Empréstimos recentes</h2><p>As últimas movimentações do acervo.</p></div><a class="text-link" href="<?= e(url('emprestimos.php')) ?>">Ver todos <?= icon('arrow') ?></a></div><div class="table-scroll"><table><thead><tr><th>Livro</th><th>Usuário</th><th>Empréstimo</th><th>Devolução prevista</th><th>Status</th></tr></thead><tbody><?php foreach ($recent as $loan): [$state,$kind]=loan_state($loan); ?><tr><td><a class="table-title" href="<?= e(url('emprestimo.php?id=' . $loan['id'])) ?>"><?= e($loan['titulo']) ?></a></td><td><?= e($loan['nome']) ?></td><td><?= fmt_date($loan['data_emprestimo']) ?></td><td><?= fmt_date($loan['data_devolucao_prevista']) ?></td><td><?= badge($state,$kind) ?></td></tr><?php endforeach; ?><?php if (!$recent): ?><tr><td colspan="5" class="empty-cell">Nenhum empréstimo ainda. <a class="text-link" href="<?= e(url('emprestimo_form.php')) ?>">Registrar o primeiro</a></td></tr><?php endif; ?></tbody></table></div></section>
<section class="panel popular-panel"><div class="panel-heading"><div><h2>Livros mais emprestados</h2><p>Os favoritos dos leitores.</p></div></div><ol class="popular-list"><?php foreach ($popular as $position=>$book): ?><li><span class="rank rank-<?= $position ?>"><?= $position+1 ?></span><a href="<?= e(url('livro.php?id='.$book['id'])) ?>"><?= e($book['titulo']) ?></a><strong><?= fmt_number($book['total']) ?></strong></li><?php endforeach; ?></ol><?php if (!$popular): ?><div class="empty-state compact"><?= icon('book') ?><p>O ranking aparece após os primeiros empréstimos.</p></div><?php endif; ?></section></div>
<section class="quick-start"><div><?= icon('book') ?><span><strong>Tudo pronto para a próxima leitura.</strong><small>Atualize o acervo ou registre um novo empréstimo.</small></span></div><a class="button button-secondary" href="<?= e(url('emprestimo_form.php')) ?>"><?= icon('plus') ?>Novo empréstimo</a></section>
<?php require __DIR__ . '/../app/footer.php'; ?>
