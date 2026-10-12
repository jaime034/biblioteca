<?php
require __DIR__ . '/../app/bootstrap.php';
require_login();
$title = 'Livros';
$active = 'livros';
$q = input($_GET, 'q');
$category = positive_id(input($_GET, 'categoria'));
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(l.titulo LIKE :q1 OR l.isbn LIKE :q2 OR EXISTS (SELECT 1 FROM livros_autores la JOIN autores a ON a.id=la.autor_id WHERE la.livro_id=l.id AND a.nome LIKE :q3))';
    $params = ['q1' => '%' . $q . '%', 'q2' => '%' . $q . '%', 'q3' => '%' . $q . '%'];
}
if ($category) {
    $where[] = 'l.categoria_id=:categoria';
    $params['categoria'] = $category;
}
$filter = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$pagination = page_data(count_value('SELECT COUNT(*) FROM livros l' . $filter, $params));
$books = rows("SELECT l.*,c.nome AS categoria,(SELECT GROUP_CONCAT(a.nome ORDER BY a.nome SEPARATOR ', ') FROM livros_autores la JOIN autores a ON a.id=la.autor_id WHERE la.livro_id=l.id) AS autores FROM livros l JOIN categorias c ON c.id=l.categoria_id" . $filter . ' ORDER BY l.titulo LIMIT :limite OFFSET :offset', $params + ['limite' => $pagination['limit'], 'offset' => $pagination['offset']]);
$categories = rows('SELECT id,nome FROM categorias ORDER BY nome');
require __DIR__ . '/../app/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">ACERVO</span><h1>Livros</h1><p><?= is_staff() ? 'Gerencie o acervo da biblioteca.' : 'Encontre sua próxima leitura no nosso acervo.' ?></p></div></div>
<div class="toolbar"><form class="search-form" method="get" action="<?= e(url('livros.php')) ?>"><div class="search-input"><?= icon('search') ?><input type="search" name="q" aria-label="Buscar livros" placeholder="Pesquisar por título, autor ou ISBN..." maxlength="200" value="<?= e($q) ?>"></div><?php if ($category): ?><input type="hidden" name="categoria" value="<?= $category ?>"><?php endif; ?><button class="button button-secondary" type="submit">Buscar</button></form><?php if (is_staff()): ?><a class="button" href="<?= e(url('livro_form.php')) ?>"><?= icon('plus') ?>Novo livro</a><?php endif; ?></div>
<h2 class="filter-heading">Categorias</h2><nav class="category-tabs" aria-label="Categorias de livros"><a class="category-tab <?= !$category ? 'active' : '' ?>" href="<?= e(url('livros.php?' . http_build_query(['q' => $q]))) ?>">Todos</a><?php foreach ($categories as $item): ?><a class="category-tab <?= $category===(int)$item['id'] ? 'active' : '' ?>" href="<?= e(url('livros.php?' . http_build_query(['categoria' => $item['id'], 'q' => $q]))) ?>"><?= e($item['nome']) ?></a><?php endforeach; ?></nav>
<div class="results-line"><span><?= fmt_number($pagination['total']) ?> livro(s) encontrado(s)</span><?php if ($q!=='' || $category): ?><a class="text-link" href="<?= e(url('livros.php')) ?>">Limpar filtros</a><?php endif; ?></div>
<div class="book-grid"><?php foreach ($books as $book): ?><article class="book-card"><div class="book-card-main"><?= book_cover($book) ?><div class="book-info"><h2><a href="<?= e(url('livro.php?id='.$book['id'])) ?>"><?= e($book['titulo']) ?></a></h2><p class="book-author"><?= e($book['autores'] ?: 'Autor não informado') ?></p><p class="book-isbn">ISBN: <?= e($book['isbn'] ?: 'não informado') ?></p><?= (int)$book['quantidade_disponivel']>0 ? badge('Disponível','success') : badge('Indisponível','warning') ?><p class="book-stock"><?= (int)$book['quantidade_disponivel'] ?> de <?= (int)$book['quantidade_total'] ?> exemplar(es)</p></div></div><div class="book-card-footer"><span class="book-category"><?= e($book['categoria']) ?></span><a class="text-link" href="<?= e(url('livro.php?id='.$book['id'])) ?>">Ver detalhes <?= icon('arrow') ?></a></div></article><?php endforeach; ?></div>
<?php if (!$books): ?><div class="empty-state"><?= icon('book') ?><h2>Nenhum livro encontrado</h2><p><?= $q!=='' || $category ? 'Tente outro termo ou limpe os filtros da busca.' : 'Adicione os primeiros livros para começar a organizar o acervo.' ?></p><?php if (is_staff()): ?><a class="button" href="<?= e(url('livro_form.php')) ?>"><?= icon('plus') ?>Cadastrar livro</a><?php endif; ?></div><?php endif; ?>
<?php pagination($pagination,'livros.php'); require __DIR__ . '/../app/footer.php'; ?>
