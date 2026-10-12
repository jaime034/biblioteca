<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();
$id=positive_id(input($_GET,'id'));
$person=$id?one('SELECT id,nome,cpf,email,telefone,perfil,status FROM usuarios WHERE id=:id',['id'=>$id]):[];
if ($id && !$person) { http_response_code(404); render_error('Usuário não encontrado','Este usuário não está cadastrado.'); }
if ($id && !is_admin() && $person['perfil']!=='leitor') { http_response_code(403); render_error('Acesso restrito','Somente o administrador pode alterar contas da equipe.'); }
$data=$_SERVER['REQUEST_METHOD']==='POST'?$_POST:$person;
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    try { save_user($_POST,$id); flash($id?'Usuário atualizado.':'Usuário cadastrado.'); redirect('usuarios.php'); }
    catch (Throwable $exception) { $error=friendly_error($exception); }
}
$title=$id?'Editar usuário':'Novo usuário'; $active='usuarios';
require __DIR__ . '/../app/header.php';
?>
<a class="text-link back-link" href="<?= e(url('usuarios.php')) ?>">← Voltar para usuários</a><div class="page-heading"><div><span class="eyebrow">LEITORES E EQUIPE</span><h1><?= e($title) ?></h1><p>Cadastre os dados de contato e as permissões de acesso.</p></div></div>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<section class="panel form-panel"><div class="form-intro"><h2>Dados do usuário</h2><p>Campos com * são obrigatórios. Para preservar o histórico, use o status Inativo em vez de excluir usuários.</p></div><form class="form-grid" method="post" action="<?= e(url('usuario_form.php'.($id?'?id='.$id:''))) ?>"><?= csrf_field() ?>
<div class="field field-full"><label for="nome">Nome completo *</label><input id="nome" name="nome" maxlength="150" autocomplete="name" value="<?= e(input($data,'nome')) ?>" required></div><div class="field"><label for="cpf">CPF *</label><input id="cpf" name="cpf" maxlength="14" inputmode="numeric" placeholder="000.000.000-00" value="<?= e(input($data,'cpf')) ?>" required></div><div class="field"><label for="email">E-mail *</label><input type="email" id="email" name="email" maxlength="150" autocomplete="off" value="<?= e(input($data,'email')) ?>" required></div><div class="field"><label for="telefone">Telefone</label><input type="tel" id="telefone" name="telefone" maxlength="20" placeholder="(00) 00000-0000" value="<?= e(input($data,'telefone')) ?>"></div><div class="field"><label for="status">Status *</label><select id="status" name="status"><option value="1" <?= input($data,'status','1')==='1'?'selected':'' ?>>Ativo</option><option value="0" <?= input($data,'status','1')==='0'?'selected':'' ?>>Inativo</option></select></div>
<h2 class="form-section-title field-full">Acesso ao sistema</h2><div class="field"><label for="perfil">Perfil *</label><select id="perfil" name="perfil"><option value="leitor" <?= input($data,'perfil','leitor')==='leitor'?'selected':'' ?>>Leitor</option><?php if (is_admin()): ?><option value="bibliotecario" <?= input($data,'perfil')==='bibliotecario'?'selected':'' ?>>Bibliotecário</option><option value="admin" <?= input($data,'perfil')==='admin'?'selected':'' ?>>Administrador</option><?php endif; ?></select><small>Leitores consultam o acervo e seus próprios empréstimos. A equipe gerencia a biblioteca.</small></div><div class="field"><label for="senha"><?= $id?'Nova senha':'Senha' ?></label><input type="password" id="senha" name="senha" minlength="8" autocomplete="new-password"><small><?= $id?'Deixe vazio para manter a senha atual.':'Opcional para leitores; obrigatória para a equipe. Sem senha, o leitor não faz login.' ?> Use pelo menos 8 caracteres.</small></div><div class="form-actions field-full"><a class="button button-secondary" href="<?= e(url('usuarios.php')) ?>">Cancelar</a><button class="button" type="submit"><?= icon('check') ?>Salvar usuário</button></div></form></section>
<?php require __DIR__ . '/../app/footer.php'; ?>
