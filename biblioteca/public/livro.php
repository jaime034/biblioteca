<?php
require __DIR__ . '/../app/bootstrap.php';
require_login();
$id = positive_id(input($_GET,'id'));
$book = one("SELECT l.*,c.nome AS categoria,(SELECT GROUP_CONCAT(a.nome ORDER BY a.nome SEPARATOR ', ') FROM livros_autores la JOIN autores a ON a.id=la.autor_id WHERE la.livro_id=l.id) AS autores FROM livros l JOIN categorias c ON c.id=l.categoria_id WHERE l.id=:id",['id'=>$id]);
if (!$book) { http_response_code(404); render_error('Livro não encontrado','Este livro não está no acervo.'); }
$title = $book['titulo'];
$active = 'livros';
$history = count_value('SELECT COUNT(*) FROM emprestimos WHERE livro_id=:id',['id'=>$id]);
require __DIR__ . '/../app/header.php';
?>
<a class="text-link back-link" href="<?= e(url('livros.php')) ?>">← Voltar para livros</a>
<div class="page-heading"><div><span class="eyebrow">DETALHES DO ACERVO</span><h1>Conheça este livro</h1><p>Informações e disponibilidade para empréstimo.</p></div><?php if (is_staff()): ?><a class="button button-secondary" href="<?= e(url('livro_form.php?id='.$id)) ?>"><?= icon('edit') ?>Editar livro</a><?php endif; ?></div>
<div class="detail-layout"><article class="panel detail-panel"><div class="book-hero"><?= book_cover($book) ?><div><h2><?= e($book['titulo']) ?></h2><p><?= e($book['autores'] ?: 'Autor não informado') ?></p><?= badge($book['categoria'],'muted') ?></div></div><dl class="detail-list"><div><dt>ISBN</dt><dd><?= e($book['isbn'] ?: 'Não informado') ?></dd></div><div><dt>Editora</dt><dd><?= e($book['editora'] ?: 'Não informada') ?></dd></div><div><dt>Ano da edição</dt><dd><?= e($book['ano_publicacao'] ?: 'Não informado') ?></dd></div><div><dt>Exemplares no acervo</dt><dd><?= (int)$book['quantidade_total'] ?></dd></div></dl><div class="detail-description"><h3>Sobre o livro</h3><p><?= e($book['descricao'] ?: 'Nenhuma descrição cadastrada.') ?></p></div></article><aside class="panel side-panel"><h2>Disponibilidade</h2><span class="stock-number"><?= (int)$book['quantidade_disponivel'] ?></span><p>exemplar(es) disponível(is) de <?= (int)$book['quantidade_total'] ?> no acervo.</p><?= (int)$book['quantidade_disponivel']>0 ? badge('Disponível para empréstimo','success') : badge('Todos os exemplares emprestados','warning') ?><div class="side-actions"><?php if (is_staff() && (int)$book['quantidade_disponivel']>0): ?><a class="button" href="<?= e(url('emprestimo_form.php?livro_id='.$id)) ?>"><?= icon('plus') ?>Registrar empréstimo</a><?php elseif (!is_staff()): ?><p>Procure a equipe da biblioteca para solicitar um empréstimo.</p><?php endif; ?><?php if (is_staff() && !$history): ?><a class="button button-danger" href="<?= e(url('livro_excluir.php?id='.$id)) ?>">Excluir livro</a><?php elseif (is_staff()): ?><p>O histórico de empréstimos deste livro é preservado.</p><?php endif; ?></div></aside></div>
<?php require __DIR__ . '/../app/footer.php'; ?>
