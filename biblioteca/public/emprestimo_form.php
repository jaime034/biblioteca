<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();
$error='';
$data=$_SERVER['REQUEST_METHOD']==='POST'?$_POST:['livro_id'=>input($_GET,'livro_id'),'data_devolucao_prevista'=>date('Y-m-d',strtotime('+'.(int)$config['prazo_emprestimo_dias'].' days'))];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    try { $id=create_loan($_POST); flash('Empréstimo registrado. O estoque foi atualizado.'); redirect('emprestimo.php?id='.$id); }
    catch (Throwable $exception) { $error=friendly_error($exception); }
}
$books=rows('SELECT id,titulo,quantidade_disponivel FROM livros WHERE quantidade_disponivel>0 ORDER BY titulo');
$borrowers=rows("SELECT id,nome,email FROM usuarios WHERE status=1 AND perfil='leitor' ORDER BY nome");
$title='Novo empréstimo'; $active='emprestimos'; require __DIR__ . '/../app/header.php';
?>
<a class="text-link back-link" href="<?= e(url('emprestimos.php')) ?>">← Voltar para empréstimos</a><div class="page-heading"><div><span class="eyebrow">CIRCULAÇÃO DO ACERVO</span><h1>Novo empréstimo</h1><p>Escolha o livro, o leitor e o prazo de devolução.</p></div></div>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?><?php if (!$books || !$borrowers): ?><div class="alert alert-info">Você precisa de pelo menos um livro disponível e um leitor ativo. <a class="text-link" href="<?= e(url(!$books?'livro_form.php':'usuario_form.php')) ?>">Cadastrar agora</a></div><?php endif; ?>
<section class="panel form-panel"><div class="form-intro"><h2>Dados do empréstimo</h2><p>O registro reserva um exemplar do livro. O prazo padrão é de <?= (int)$config['prazo_emprestimo_dias'] ?> dias.</p></div><form class="form-grid" method="post" action="<?= e(url('emprestimo_form.php')) ?>"><?= csrf_field() ?>
<div class="field field-full"><label for="livro_id">Livro *</label><select id="livro_id" name="livro_id" required><option value="">Selecione um livro disponível</option><?php foreach ($books as $book): ?><option value="<?= (int)$book['id'] ?>" <?= (int)input($data,'livro_id')===(int)$book['id']?'selected':'' ?>><?= e($book['titulo']) ?> · <?= (int)$book['quantidade_disponivel'] ?> disponível(is)</option><?php endforeach; ?></select></div><div class="field field-full"><label for="usuario_id">Leitor *</label><select id="usuario_id" name="usuario_id" required><option value="">Selecione um leitor ativo</option><?php foreach ($borrowers as $person): ?><option value="<?= (int)$person['id'] ?>" <?= (int)input($data,'usuario_id')===(int)$person['id']?'selected':'' ?>><?= e($person['nome']) ?> · <?= e($person['email']) ?></option><?php endforeach; ?></select><small>Apenas contas com perfil Leitor e status Ativo aparecem aqui.</small></div><div class="field"><label for="data_devolucao_prevista">Devolução prevista *</label><input type="date" id="data_devolucao_prevista" name="data_devolucao_prevista" min="<?= date('Y-m-d') ?>" value="<?= e(input($data,'data_devolucao_prevista')) ?>" required></div><div class="field"><label>Funcionário responsável</label><input aria-label="Funcionário responsável" value="<?= e(current_user()['nome']) ?>" readonly></div><div class="field field-full"><label for="observacoes">Observações</label><textarea id="observacoes" name="observacoes" maxlength="2000" rows="3" placeholder="Informações adicionais sobre este empréstimo..."><?= e(input($data,'observacoes')) ?></textarea></div><div class="form-actions field-full"><a class="button button-secondary" href="<?= e(url('emprestimos.php')) ?>">Cancelar</a><button class="button" type="submit" <?= !$books || !$borrowers?'disabled':'' ?>><?= icon('check') ?>Registrar empréstimo</button></div></form></section>
<?php require __DIR__ . '/../app/footer.php'; ?>
