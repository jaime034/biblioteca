<?php
require __DIR__ . '/../app/bootstrap.php';
$user=require_login();
$title=is_staff()?'Empréstimos':'Meus empréstimos'; $active='emprestimos';
$q=input($_GET,'q'); $state=input($_GET,'status');
$from=input($_GET,'de'); $to=input($_GET,'ate');
$where=[]; $params=[];
$scope=is_staff()?'':' AND usuario_id=:dono';
$scopeParams=is_staff()?[]:['dono'=>(int)$user['id']];
$open=count_value('SELECT COUNT(*) FROM emprestimos WHERE status=0'.$scope,$scopeParams);
$returned=count_value('SELECT COUNT(*) FROM emprestimos WHERE status=1'.$scope,$scopeParams);
$overdue=count_value('SELECT COUNT(*) FROM emprestimos WHERE status=0 AND data_devolucao_prevista < :hoje'.$scope,$scopeParams+['hoje'=>date('Y-m-d')]);
if (!is_staff()) { $where[]='e.usuario_id=:dono'; $params['dono']=(int)$user['id']; }
if ($q!=='') { $where[]='(l.titulo LIKE :q1 OR u.nome LIKE :q2)'; $params+=['q1'=>'%'.$q.'%','q2'=>'%'.$q.'%']; }
if ($state==='ativos') { $where[]='e.status=0'; }
elseif ($state==='atrasados') { $where[]='e.status=0 AND e.data_devolucao_prevista < :hoje'; $params['hoje']=date('Y-m-d'); }
elseif ($state==='devolvidos') { $where[]='e.status=1'; }
if (valid_date($from)) { $where[]='e.data_emprestimo >= :de'; $params['de']=$from.' 00:00:00'; }
if (valid_date($to)) { $where[]='e.data_emprestimo <= :ate'; $params['ate']=$to.' 23:59:59'; }
$filter=$where?' WHERE '.implode(' AND ',$where):'';
$joins=' FROM emprestimos e JOIN livros l ON l.id=e.livro_id JOIN usuarios u ON u.id=e.usuario_id';
$pagination=page_data(count_value('SELECT COUNT(*)'.$joins.$filter,$params),15);
$loans=rows('SELECT e.*,l.titulo,u.nome'.$joins.$filter.' ORDER BY e.data_emprestimo DESC,e.id DESC LIMIT :limite OFFSET :offset',$params+['limite'=>$pagination['limit'],'offset'=>$pagination['offset']]);
require __DIR__ . '/../app/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">CIRCULAÇÃO DO ACERVO</span><h1><?= e($title) ?></h1><p><?= is_staff()?'Controle os empréstimos e as devoluções.':'Acompanhe suas leituras e os prazos de devolução.' ?></p></div><?php if (is_staff()): ?><a class="button" href="<?= e(url('emprestimo_form.php')) ?>"><?= icon('plus') ?>Novo empréstimo</a><?php endif; ?></div>
<section class="loan-stats" aria-label="Resumo dos empréstimos"><article class="loan-stat"><?= icon('loan') ?><div><strong><?= fmt_number($open) ?></strong><small>Em andamento</small></div></article><article class="loan-stat stat-green"><?= icon('check') ?><div><strong><?= fmt_number($returned) ?></strong><small>Devolvidos</small></div></article><article class="loan-stat stat-red"><?= icon('clock') ?><div><strong><?= fmt_number($overdue) ?></strong><small>Atrasados</small></div></article></section>
<div class="toolbar"><form method="get" class="filter-bar" action="<?= e(url('emprestimos.php')) ?>"><div class="search-input"><?= icon('search') ?><input type="search" name="q" maxlength="200" aria-label="Pesquisar empréstimos" placeholder="Pesquisar por livro ou leitor..." value="<?= e($q) ?>"></div><select name="status" aria-label="Status do empréstimo"><option value="">Todos os status</option><option value="ativos" <?= $state==='ativos'?'selected':'' ?>>Em andamento</option><option value="atrasados" <?= $state==='atrasados'?'selected':'' ?>>Atrasados</option><option value="devolvidos" <?= $state==='devolvidos'?'selected':'' ?>>Devolvidos</option></select><div class="date-filter"><label for="de">De</label><input type="date" id="de" name="de" value="<?= e($from) ?>"></div><div class="date-filter"><label for="ate">Até</label><input type="date" id="ate" name="ate" value="<?= e($to) ?>"></div><button class="button button-secondary" type="submit">Filtrar</button><a class="button button-secondary" href="<?= e(url('emprestimos.php')) ?>">Limpar</a></form></div>
<div class="results-line"><span><?= fmt_number($pagination['total']) ?> empréstimo(s) encontrado(s)</span><a class="text-link" href="<?= e(url('multas.php')) ?>"><?= icon('coin') ?><?= is_staff()?'Consultar multas':'Minhas multas' ?></a></div>
<section class="panel"><div class="table-scroll"><table><thead><tr><th>#</th><th>Livro</th><?php if (is_staff()): ?><th>Usuário</th><?php endif; ?><th>Empréstimo</th><th>Devolução prevista</th><th>Status</th><th>Ações</th></tr></thead><tbody><?php foreach ($loans as $loan): [$label,$kind]=loan_state($loan); ?><tr><td><?= (int)$loan['id'] ?></td><td><div class="book-cell"><?= book_cover(['id'=>$loan['livro_id'],'titulo'=>$loan['titulo']],true) ?><a class="table-title" href="<?= e(url('livro.php?id='.$loan['livro_id'])) ?>"><?= e($loan['titulo']) ?></a></div></td><?php if (is_staff()): ?><td><?= e($loan['nome']) ?></td><?php endif; ?><td><?= fmt_date($loan['data_emprestimo']) ?></td><td><?= fmt_date($loan['data_devolucao_prevista']) ?></td><td><?= badge($label,$kind) ?></td><td><a class="button button-secondary button-small" href="<?= e(url('emprestimo.php?id='.$loan['id'])) ?>"><?= is_staff() && (int)$loan['status']===0?'Devolver':'Detalhes' ?></a></td></tr><?php endforeach; ?><?php if (!$loans): ?><tr><td colspan="<?= is_staff()?7:6 ?>" class="empty-cell">Nenhum empréstimo encontrado. <?= !is_staff()?'Procure a equipe para solicitar uma leitura.':'' ?></td></tr><?php endif; ?></tbody></table></div></section>
<?php pagination($pagination,'emprestimos.php'); require __DIR__ . '/../app/footer.php'; ?>
