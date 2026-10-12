<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();
$title='Usuários'; $active='usuarios';
$q=input($_GET,'q'); $status=input($_GET,'status');
$where=[]; $params=[];
if ($q!=='') { $where[]='(nome LIKE :q1 OR email LIKE :q2 OR cpf LIKE :q3)'; $params=['q1'=>'%'.$q.'%','q2'=>'%'.$q.'%','q3'=>'%'.$q.'%']; }
if (in_array($status,['0','1'],true)) { $where[]='status=:status'; $params['status']=(int)$status; }
$filter=$where?' WHERE '.implode(' AND ',$where):'';
$pagination=page_data(count_value('SELECT COUNT(*) FROM usuarios'.$filter,$params),15);
$users=rows('SELECT id,nome,email,telefone,perfil,status FROM usuarios'.$filter.' ORDER BY nome LIMIT :limite OFFSET :offset',$params+['limite'=>$pagination['limit'],'offset'=>$pagination['offset']]);
$total=count_value('SELECT COUNT(*) FROM usuarios');
require __DIR__ . '/../app/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">LEITORES E EQUIPE</span><h1>Usuários</h1><p>Gerencie os usuários da biblioteca.</p></div></div><div class="mini-stat"><?= icon('users') ?><div><small>Total de usuários</small><strong><?= fmt_number($total) ?></strong></div></div>
<div class="toolbar"><form class="search-form" method="get" action="<?= e(url('usuarios.php')) ?>"><div class="search-input"><?= icon('search') ?><input type="search" name="q" maxlength="200" aria-label="Buscar usuários" placeholder="Buscar por nome, e-mail ou CPF..." value="<?= e($q) ?>"></div><select name="status" aria-label="Filtrar status"><option value="">Todos</option><option value="1" <?= $status==='1'?'selected':'' ?>>Ativos</option><option value="0" <?= $status==='0'?'selected':'' ?>>Inativos</option></select><button class="button button-secondary" type="submit">Buscar</button></form><a class="button" href="<?= e(url('usuario_form.php')) ?>"><?= icon('plus') ?>Novo usuário</a></div>
<div class="results-line"><span><?= fmt_number($pagination['total']) ?> usuário(s) encontrado(s)</span><?php if ($q!=='' || $status!==''): ?><a class="text-link" href="<?= e(url('usuarios.php')) ?>">Limpar filtros</a><?php endif; ?></div>
<section class="panel"><div class="table-scroll"><table><thead><tr><th>Usuário</th><th>E-mail</th><th>Telefone</th><th>Perfil</th><th>Status</th><th>Ações</th></tr></thead><tbody><?php foreach ($users as $person): ?><tr><td><div class="user-cell"><span class="avatar avatar-small avatar-tone-<?= (int)$person['id']%5 ?>"><?= e(initials($person['nome'])) ?></span><strong class="table-title"><?= e($person['nome']) ?></strong></div></td><td><?= e($person['email']) ?></td><td><?= e($person['telefone'] ?: '—') ?></td><td><?= e(role_label($person['perfil'])) ?></td><td><?= (int)$person['status']===1 ? badge('Ativo','success') : badge('Inativo','danger') ?></td><td><?php if (is_admin() || $person['perfil']==='leitor'): ?><a class="button button-secondary button-small" href="<?= e(url('usuario_form.php?id='.$person['id'])) ?>"><?= icon('edit') ?>Editar</a><?php else: ?><span class="table-subtitle">Conta da equipe</span><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$users): ?><tr><td colspan="6" class="empty-cell">Nenhum usuário encontrado para esta busca.</td></tr><?php endif; ?></tbody></table></div></section>
<?php pagination($pagination,'usuarios.php'); require __DIR__ . '/../app/footer.php'; ?>
