<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();
$id=positive_id(input($_GET,'id'));
$book=one('SELECT id,titulo FROM livros WHERE id=:id',['id'=>$id]);
if (!$book) { http_response_code(404); render_error('Livro não encontrado','Este livro não está no acervo.'); }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    try { delete_book($id); flash('Livro excluído do acervo.'); redirect('livros.php'); }
    catch (Throwable $exception) { $error=friendly_error($exception); }
}
$title='Excluir livro'; $active='livros'; require __DIR__ . '/../app/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">ACERVO</span><h1>Excluir livro</h1><p>Confirme a remoção do registro.</p></div></div><?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?><section class="panel confirm-box"><h2><?= e($book['titulo']) ?></h2><p>O livro será removido do acervo. Livros que possuem histórico de empréstimos não podem ser excluídos.</p><form method="post" action="<?= e(url('livro_excluir.php?id='.$id)) ?>"><?= csrf_field() ?><div class="form-actions"><a class="button button-secondary" href="<?= e(url('livro.php?id='.$id)) ?>">Manter livro</a><button class="button button-danger" type="submit">Confirmar exclusão</button></div></form></section>
<?php require __DIR__ . '/../app/footer.php'; ?>
