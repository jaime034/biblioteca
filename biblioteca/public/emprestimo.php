<?php
require __DIR__ . '/../app/bootstrap.php';
$user=require_login();
$id=positive_id(input($_GET,'id'));
$loan=one('SELECT e.*,l.titulo,u.nome,u.email,f.nome AS funcionario FROM emprestimos e JOIN livros l ON l.id=e.livro_id JOIN usuarios u ON u.id=e.usuario_id LEFT JOIN usuarios f ON f.id=e.funcionario_id WHERE e.id=:id',['id'=>$id]);
if (!$loan || (!is_staff() && (int)$loan['usuario_id']!==(int)$user['id'])) { http_response_code(404); render_error('Empréstimo não encontrado','Não foi possível localizar este empréstimo.'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_staff(); verify_csrf();
    try {
        if (input($_POST,'acao')!=='devolver') { throw new DomainException('Ação inválida.'); }
        $cents=return_loan($id);
        flash('Devolução registrada. O exemplar está disponível novamente.'.($cents>0?' Multa por atraso: '.money($cents/100).'.':''));
        redirect('emprestimo.php?id='.$id);
    } catch (Throwable $exception) { $error=friendly_error($exception); }
}
$fine=one('SELECT * FROM multas WHERE emprestimo_id=:id',['id'=>$id]);
[$label,$kind]=loan_state($loan);
$due=new DateTimeImmutable($loan['data_devolucao_prevista']); $today=new DateTimeImmutable('today');
$days=(int)$loan['status']===0 && $today>$due ? (int)$due->diff($today)->days : 0;
$estimated=$days*(int)$config['multa_por_dia_centavos']/100;
$title='Empréstimo #'.$id; $active='emprestimos'; require __DIR__ . '/../app/header.php';
?>
<a class="text-link back-link" href="<?= e(url('emprestimos.php')) ?>">← Voltar para empréstimos</a><div class="page-heading"><div><span class="eyebrow">CIRCULAÇÃO DO ACERVO</span><h1>Empréstimo #<?= $id ?></h1><p>Confira o prazo e acompanhe a devolução.</p></div><?= badge($label,$kind) ?></div>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="detail-layout"><section class="panel detail-panel"><h2><?= e($loan['titulo']) ?></h2><a class="text-link" href="<?= e(url('livro.php?id='.$loan['livro_id'])) ?>">Ver informações do livro <?= icon('arrow') ?></a><dl class="detail-list"><div><dt>Leitor</dt><dd><?= e($loan['nome']) ?></dd></div><div><dt>E-mail do leitor</dt><dd><?= e($loan['email']) ?></dd></div><div><dt>Data do empréstimo</dt><dd><?= fmt_date($loan['data_emprestimo']) ?></dd></div><div><dt>Devolução prevista</dt><dd><?= fmt_date($loan['data_devolucao_prevista']) ?></dd></div><div><dt>Devolução registrada</dt><dd><?= fmt_date($loan['data_devolucao_real']) ?></dd></div><div><dt>Funcionário responsável</dt><dd><?= e($loan['funcionario'] ?: 'Não informado') ?></dd></div></dl><div class="detail-description"><h3>Observações</h3><p><?= e($loan['observacoes'] ?: 'Nenhuma observação registrada.') ?></p></div></section><aside class="panel side-panel"><h2><?= (int)$loan['status']===0?'Registrar devolução':'Devolução concluída' ?></h2><?php if ((int)$loan['status']===0): ?><p>Prazo: <strong><?= fmt_date($loan['data_devolucao_prevista']) ?></strong>. Ao confirmar a devolução, um exemplar retorna ao estoque.</p><?php if ($days>0): ?><div class="alert alert-warning"><?= $days ?> dia(s) de atraso. Multa estimada: <strong><?= money($estimated) ?></strong>.<br>Valor por dia: <?= money((int)$config['multa_por_dia_centavos']/100) ?>.</div><?php endif; ?><?php if (is_staff()): ?><form method="post" action="<?= e(url('emprestimo.php?id='.$id)) ?>"><?= csrf_field() ?><input type="hidden" name="acao" value="devolver"><button class="button button-block" type="submit"><?= icon('check') ?>Confirmar devolução</button></form><?php else: ?><p>Entregue o livro à equipe da biblioteca para registrar a devolução.</p><?php endif; ?><?php else: ?><p>Livro devolvido em <?= fmt_date($loan['data_devolucao_real']) ?>.</p><?= badge('Exemplar devolvido','success') ?><?php endif; ?><?php if ($fine): ?><div class="detail-description"><h3>Multa registrada</h3><p><?= money($fine['valor']) ?> · <?= e(ucfirst($fine['status'])) ?><br><?= e($fine['motivo']) ?></p><a class="text-link" href="<?= e(url('multas.php')) ?>">Consultar multas <?= icon('arrow') ?></a></div><?php endif; ?></aside></div>
<?php require __DIR__ . '/../app/footer.php'; ?>
