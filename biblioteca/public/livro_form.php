<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();
$id = positive_id(input($_GET,'id'));
$book = $id ? one("SELECT l.*,(SELECT GROUP_CONCAT(a.nome ORDER BY a.nome SEPARATOR ', ') FROM livros_autores la JOIN autores a ON a.id=la.autor_id WHERE la.livro_id=l.id) AS autores FROM livros l WHERE id=:id",['id'=>$id]) : [];
if ($id && !$book) { http_response_code(404); render_error('Livro não encontrado','Não foi possível localizar o livro para edição.'); }
$data = $_SERVER['REQUEST_METHOD']==='POST' ? $_POST : $book;
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    try { $saved=save_book($_POST,$id); flash($id?'Livro atualizado.':'Livro cadastrado.'); redirect('livro.php?id='.$saved); }
    catch (Throwable $exception) { $error=friendly_error($exception); }
}
$categories=rows('SELECT id,nome FROM categorias ORDER BY nome');
$title=$id?'Editar livro':'Novo livro';
$active='livros';
require __DIR__ . '/../app/header.php';
?>
<a class="text-link back-link" href="<?= e(url($id?'livro.php?id='.$id:'livros.php')) ?>">← Voltar para o acervo</a><div class="page-heading"><div><span class="eyebrow">ACERVO</span><h1><?= e($title) ?></h1><p>Preencha os dados da obra e a quantidade de exemplares.</p></div></div>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<section class="panel form-panel"><div class="form-intro"><h2>Informações do livro</h2><p>Campos com * são obrigatórios. Categorias e autores novos são cadastrados junto com o livro.</p></div><form class="form-grid" method="post" action="<?= e(url('livro_form.php'.($id?'?id='.$id:''))) ?>"><?= csrf_field() ?>
<div class="field field-full"><label for="titulo">Título *</label><input id="titulo" name="titulo" maxlength="255" value="<?= e(input($data,'titulo')) ?>" required></div>
<div class="field field-full"><label for="autores">Autor(es) *</label><input id="autores" name="autores" maxlength="1000" placeholder="Ex.: Machado de Assis" value="<?= e(input($data,'autores')) ?>" required><small>Para mais de um autor, separe os nomes por vírgula.</small></div>
<div class="field"><label for="isbn">ISBN</label><input id="isbn" name="isbn" maxlength="20" value="<?= e(input($data,'isbn')) ?>" placeholder="ISBN da edição"></div><div class="field"><label for="editora">Editora</label><input id="editora" name="editora" maxlength="150" value="<?= e(input($data,'editora')) ?>"></div>
<div class="field"><label for="categoria_id">Categoria *</label><select id="categoria_id" name="categoria_id"><option value="">Selecione uma categoria</option><?php foreach ($categories as $item): ?><option value="<?= (int)$item['id'] ?>" <?= (int)input($data,'categoria_id')===(int)$item['id']?'selected':'' ?>><?= e($item['nome']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="nova_categoria">Ou crie uma nova categoria</label><input id="nova_categoria" name="nova_categoria" maxlength="100" value="<?= e(input($data,'nova_categoria')) ?>" placeholder="Ex.: Ciências"><small>Se preenchido, este nome será usado como categoria.</small></div>
<div class="field"><label for="ano_publicacao">Ano da edição</label><input type="number" id="ano_publicacao" name="ano_publicacao" min="1901" max="2155" value="<?= e(input($data,'ano_publicacao')) ?>"><small>O campo YEAR do banco aceita 1901 a 2155. Deixe vazio se desconhecido.</small></div><div class="field"><label for="quantidade_total">Quantidade total *</label><input type="number" id="quantidade_total" name="quantidade_total" min="1" max="1000000" value="<?= e(input($data,'quantidade_total','1')) ?>" required><small>Os exemplares disponíveis são recalculados a partir dos empréstimos.</small></div>
<div class="field field-full"><label for="descricao">Descrição</label><textarea id="descricao" name="descricao" maxlength="255" rows="3" placeholder="Uma breve apresentação do livro..."><?= e(input($data,'descricao')) ?></textarea></div>
<div class="form-actions field-full"><a class="button button-secondary" href="<?= e(url($id?'livro.php?id='.$id:'livros.php')) ?>">Cancelar</a><button type="submit" class="button"><?= icon('check') ?>Salvar livro</button></div></form></section>
<?php require __DIR__ . '/../app/footer.php'; ?>
