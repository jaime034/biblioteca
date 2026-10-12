<?php
require __DIR__ . '/../app/bootstrap.php';
$user=require_login();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_staff(); verify_csrf();
    try { $action=input($_POST,'acao'); settle_fine(positive_id(input($_POST,'id')),$action); flash($action==='pagar'?'Pagamento registrado.':'Multa cancelada.'); }
    catch (Throwable $exception) { flash(friendly_error($exception),'error'); }
    redirect('multas.php');
}
$confirmation=null; $action=input($_GET,'acao');
if (positive_id(input($_GET,'confirmar'))) {
    require_staff();
    if (!in_array($action,['pagar','cancelar'],true) || ($action==='cancelar' && !is_admin())) { http_response_code(403); render_error('Acesso restrito','Esta ação não está disponível para sua conta.'); }
    $confirmation=one("SELECT m.*,l.titulo,u.nome FROM multas m JOIN emprestimos e ON e.id=m.emprestimo_id JOIN livros l ON l.id=e.livro_id JOIN usuarios u ON u.id=e.usuario_id WHERE m.id=:id AND m.status='pendente'",['id'=>positive_id(input($_GET,'confirmar'))]);
    if (!$confirmation) { http_response_code(404); render_error('Multa não encontrada','A multa não existe ou já foi encerrada.'); }
}
$q=input($_GET,'q'); $status=input($_GET,'status');
$where=[]; $params=[];
if (!is_staff()) { $where[]='e.usuario_id=:dono'; $params['dono']=(int)$user['id']; }
if ($q!=='') { $where[]='(l.titulo LIKE :q1 OR u.nome LIKE :q2)'; $params+=['q1'=>'%'.$q.'%','q2'=>'%'.$q.'%']; }
if (in_array($status,['pendente','paga','cancelada'],true)) { $where[]='m.status=:status'; $params['status']=$status; }
$joins=' FROM multas m JOIN emprestimos e ON e.id=m.emprestimo_id JOIN livros l ON l.id=e.livro_id JOIN usuarios u ON u.id=e.usuario_id';
$filter=$where?' WHERE '.implode(' AND ',$where):'';
$pagination=page_data(count_value('SELECT COUNT(*)'.$joins.$filter,$params),15);
$fines=rows('SELECT m.*,l.titulo,u.nome'.$joins.$filter.' ORDER BY m.criado_em DESC,m.id DESC LIMIT :limite OFFSET :offset',$params+['limite'=>$pagination['limit'],'offset'=>$pagination['offset']]);
$balance=query("SELECT COALESCE(SUM(m.valor-m.valor_pago),0) FROM multas m JOIN emprestimos e ON e.id=m.emprestimo_id WHERE m.status='pendente'".(!is_staff()?' AND e.usuario_id=:dono':''),is_staff()?[]:['dono'=>(int)$user['id']])->fetchColumn();
$title=is_staff()?'Multas':'Minhas multas'; $active='multas'; require __DIR__ . '/../app/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">DEVOLUÇÕES EM ATRASO</span><h1><?= e($title) ?></h1><p>Multas são calculadas no registro da devolução. Valor diário: <?= money((int)$config['multa_por_dia_centavos']/100) ?>.</p></div></div>
<?php if ($confirmation): ?><section class="panel confirm-box"><h2><?= $action==='pagar'?'Registrar pagamento':'Cancelar multa' ?></h2><p><?= e($confirmation['nome']) ?> · <?= e($confirmation['titulo']) ?><br>Saldo pendente: <strong><?= money((float)$confirmation['valor']-(float)$confirmation['valor_pago']) ?></strong>. <?= $action==='pagar'?'Confirme apenas se a biblioteca já recebeu o valor. Este botão registra a quitação no sistema.':'O cancelamento encerra a cobrança deste registro.' ?></p><form method="post" action="<?= e(url('multas.php')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$confirmation['id'] ?>"><input type="hidden" name="acao" value="<?= e($action) ?>"><div class="form-actions"><a class="button button-secondary" href="<?= e(url('multas.php')) ?>">Voltar</a><button class="button <?= $action==='cancelar'?'button-danger':'' ?>" type="submit">Confirmar <?= $action==='pagar'?'pagamento':'cancelamento' ?></button></div></form></section><br><?php endif; ?>
<div class="mini-stat"><?= icon('coin') ?><div><small>Total pendente</small><strong><?= money($balance) ?></strong></div></div>
<div class="toolbar"><form method="get" class="filter-bar" action="<?= e(url('multas.php')) ?>"><div class="search-input"><?= icon('search') ?><input type="search" name="q" maxlength="200" aria-label="Buscar multas" placeholder="Pesquisar por livro ou leitor..." value="<?= e($q) ?>"></div><select name="status" aria-label="Status da multa"><option value="">Todos os status</option><?php foreach (['pendente'=>'Pendentes','paga'=>'Pagas','cancelada'=>'Canceladas'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= $status===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select><button class="button button-secondary" type="submit">Filtrar</button><a class="button button-secondary" href="<?= e(url('multas.php')) ?>">Limpar</a></form></div>
<section class="panel"><div class="table-scroll"><table><thead><tr><th>Livro / empréstimo</th><?php if (is_staff()): ?><th>Usuário</th><?php endif; ?><th>Valor</th><th>Valor pago</th><th>Status</th><?php if (is_staff()): ?><th>Ações</th><?php endif; ?></tr></thead><tbody><?php foreach ($fines as $fine): ?><tr><td><a class="table-title" href="<?= e(url('emprestimo.php?id='.$fine['emprestimo_id'])) ?>"><?= e($fine['titulo']) ?></a><small class="table-subtitle">#<?= (int)$fine['emprestimo_id'] ?> · <?= e($fine['motivo']) ?></small></td><?php if (is_staff()): ?><td><?= e($fine['nome']) ?></td><?php endif; ?><td><?= money($fine['valor']) ?></td><td><?= money($fine['valor_pago']) ?></td><td><?= badge(ucfirst($fine['status']),['pendente'=>'warning','paga'=>'success','cancelada'=>'muted'][$fine['status']]) ?></td><?php if (is_staff()): ?><td><div class="table-actions"><?php if ($fine['status']==='pendente'): ?><a class="button button-secondary button-small" href="<?= e(url('multas.php?confirmar='.$fine['id'].'&acao=pagar')) ?>">Registrar pagamento</a><?php if (is_admin()): ?><a class="button button-danger button-small" href="<?= e(url('multas.php?confirmar='.$fine['id'].'&acao=cancelar')) ?>">Cancelar</a><?php endif; ?><?php else: ?>—<?php endif; ?></div></td><?php endif; ?></tr><?php endforeach; ?><?php if (!$fines): ?><tr><td colspan="<?= is_staff()?6:4 ?>" class="empty-cell">Nenhuma multa encontrada. As multas por atraso são registradas quando o livro é devolvido.</td></tr><?php endif; ?></tbody></table></div></section>
<?php pagination($pagination,'multas.php'); require __DIR__ . '/../app/footer.php'; ?>
