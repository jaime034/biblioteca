# Biblioteca — sistema de gestão em PHP

Projeto baseado nos wireframes enviados: barra lateral azul, dashboard, catálogo de livros, usuários e empréstimos. Usa PHP, MySQL/MariaDB, HTML e CSS próprio. Não usa Bootstrap, Tailwind, JavaScript, Composer ou dependências externas de interface.

## Instalação no XAMPP (Windows)

1. Extraia o ZIP. Copie a pasta **biblioteca** para `C:\xampp\htdocs\biblioteca`.
2. No painel do XAMPP, inicie **Apache** e **MySQL**.
3. Abra `http://localhost/phpmyadmin` e use a aba **Importar**:
   - **Banco novo:** importe `biblioteca/database/schema.sql`. O arquivo cria o banco `biblioteca` e as tabelas.
   - **Você já importou codigo(1).sql:** importe somente `biblioteca/database/migracao.sql`. Ele acrescenta os dois campos necessários e mantém os dados existentes. Não importe `schema.sql` sobre as tabelas que já existem.
4. Confira `biblioteca/config/config.php`. A configuração inicial é host `127.0.0.1`, porta `3306`, banco `biblioteca`, usuário `root` e senha vazia, comuns no XAMPP local. Ajuste se o seu MySQL usa outros dados.
5. Acesse **http://localhost/biblioteca/public/**. No primeiro acesso, o sistema abre a criação do administrador. Preencha nome, CPF válido, e-mail e uma senha com pelo menos 8 caracteres.
6. Entre usando o e-mail e a senha que você acabou de definir. A instalação se fecha automaticamente quando existe uma conta de administrador.
7. Opcionalmente, importe `database/dados_exemplo.sql` para adicionar sete livros com categorias e autores. Ele não cria usuários, senhas ou empréstimos. Pode ser importado novamente sem duplicar esse acervo de exemplo.

Não existe uma senha padrão. A primeira conta é criada por você. Se CPF ou e-mail já estiverem no banco, use dados únicos para criar o primeiro administrador; contas antigas serão inicialmente classificadas como leitores.

## Alternativa com o servidor do PHP

Requisitos: **PHP 8.1 ou superior**, extensão **PDO MySQL** e **MySQL 8.0 ou MariaDB 10.4 ou superior**. A validação deste projeto utiliza PHP 8.3 e MariaDB 10.11.

Depois de importar o banco e conferir a configuração, abra um terminal dentro da pasta `biblioteca`:

```bash
php -S localhost:8000 -t public
```

Abra `http://localhost:8000`. O MySQL precisa estar em execução separadamente. O servidor embutido serve para desenvolvimento local.

## O que funciona

- **Login/logout:** sessões, senhas com `password_hash`/`password_verify`, proteção das páginas e saída pelo botão “Sair da conta”.
- **Dashboard:** totais reais do banco, disponibilidade, empréstimos em andamento, atrasos, usuários, movimentos recentes e ranking dos livros mais emprestados.
- **Livros:** cadastro, edição, detalhes, busca por título/autor/ISBN, filtros por categoria e paginação. Um livro sem histórico pode ser excluído, após confirmação.
- **Categorias/autores:** a categoria pode ser selecionada ou criada no formulário do livro. Autores são digitados pelo nome; novos nomes são cadastrados e associados à obra. Para vários autores, separe por vírgula.
- **Usuários:** cadastro/edição de nome, CPF, e-mail, telefone, perfil, senha e status. CPF e e-mail são únicos. O CPF tem validação dos dígitos verificadores.
- **Empréstimos:** registro para leitores ativos, seleção de livros disponíveis, data prevista, observações, filtros por período/status e paginação.
- **Devoluções:** registram a data real, encerram o empréstimo e liberam o exemplar no estoque. Uma devolução repetida não duplica o estoque.
- **Multas:** o atraso gera uma multa no momento da devolução. A equipe registra a quitação total; administradores também podem cancelar a cobrança. Não há integração com pagamentos online.
- **Tela do leitor:** consulta ao catálogo, aos seus próprios empréstimos e às suas próprias multas.
- **Responsividade:** navegação e telas se adaptam a computadores, tablets e celulares.

As capas e avatares são ilustrativos e feitos em CSS. Não há upload de imagens. O campo `capa` do SQL original é mantido, mas não é utilizado nesta versão. Não há recuperação de senha por e-mail; um administrador pode definir uma nova senha em Usuários.

## Perfis de acesso

| Operação | Administrador | Bibliotecário | Leitor |
| --- | --- | --- | --- |
| Consultar o acervo | Sim | Sim | Sim |
| Ver dashboard e usuários | Sim | Sim | Não |
| Cadastrar/editar livros | Sim | Sim | Não |
| Cadastrar/editar leitores | Sim | Sim | Não |
| Gerenciar contas da equipe | Sim | Não | Não |
| Registrar empréstimos e devoluções | Sim | Sim | Não |
| Consultar empréstimos e multas | Todos | Todos | Somente os próprios |
| Registrar quitação de multa | Sim | Sim | Não |
| Cancelar multa | Sim | Não | Não |

O leitor só consegue fazer login se tiver uma senha definida. É possível cadastrar leitores sem senha para operar os empréstimos no balcão. Usuários inativos não entram no sistema nem recebem novos empréstimos. Seu histórico é preservado. O sistema impede que uma pessoa desative a própria conta ou remova o último administrador ativo.

## Adaptação do banco enviado

Todas as tabelas e relações originais são mantidas. Apenas dois campos foram acrescentados:

| Tabela | Campo | Função |
| --- | --- | --- |
| `usuarios` | `perfil` | Distingue `admin`, `bibliotecario` e `leitor` |
| `emprestimos` | `data_devolucao_real` | Guarda quando o livro foi devolvido |

`database/schema_original.sql` é uma cópia sem alterações do arquivo SQL enviado. `schema.sql` é a versão para instalação nova. `migracao.sql` adapta uma instalação existente e pode ser executado novamente.

- `usuarios.status`: `1` ativo, `0` inativo.
- `emprestimos.status`: `0` em andamento, `1` devolvido. “Atrasado” é calculado comparando a previsão com a data atual; não fica gravado em outro status.
- `quantidade_total` é o número de exemplares. `quantidade_disponivel` diminui no empréstimo e aumenta na devolução.
- O dashboard chama a soma de exemplares de “Total de livros”; também informa o número de títulos distintos.
- O campo `ano_publicacao` é do tipo `YEAR` no banco original e aceita 1901 a 2155. Use o ano da edição cadastrada ou deixe vazio se não for conhecido.
- Livros com histórico e usuários não são removidos em cascata. Isso preserva a integridade dos registros.

Dados importados de sistemas antigos devem ter estoque e status coerentes. Devoluções anteriores à migração não recebem uma data real inventada; essa informação aparece como não registrada.

## Configurações e regras

Em `config/config.php`:

```php
'prazo_emprestimo_dias' => 14,
'multa_por_dia_centavos' => 100,
```

O prazo inicial é de 14 dias e pode ser alterado em cada empréstimo. A multa padrão é de **R$ 1,00 por dia de atraso**. Defina `multa_por_dia_centavos` como `0` para desativar novas multas. Alterar a configuração não recalcula multas já registradas. As datas seguem o fuso de São Paulo.

Um leitor não pode ter dois empréstimos simultâneos do mesmo título. O estoque é atualizado em transações com bloqueio do livro, para evitar empréstimos simultâneos acima da disponibilidade. O total de exemplares não pode ser reduzido para menos do que a quantidade atualmente emprestada.

Senhas devem ter pelo menos 8 caracteres e no máximo 72 bytes. Para senhas com acentos ou emojis, o número de bytes pode ser maior que o de caracteres. O login limita tentativas por conta e IP a 8 em 15 minutos. O diretório temporário do PHP precisa permitir escrita.

## Organização dos arquivos

| Pasta / arquivo | Responsabilidade |
| --- | --- |
| `public/` | Páginas acessadas pelo navegador |
| `public/assets/css/style.css` | Toda a aparência e a responsividade |
| `public/assets/img/favicon.svg` | Ícone vetorial local |
| `app/bootstrap.php` | Configuração, sessão e conexão PDO |
| `app/functions.php` | Funções de autenticação, validação e interface |
| `app/services.php` | Operações do acervo, usuários, empréstimos e multas |
| `app/auth.php` | Verificação de senha e limite de tentativas |
| `app/header.php` / `footer.php` | Layout compartilhado entre as páginas |
| `config/config.php` | Dados de conexão e regras configuráveis |
| `database/` | Esquema original, esquema adaptado, migração e acervo opcional |

Os formulários enviam requisições normais ao PHP. O servidor valida os dados, executa consultas preparadas com PDO e redireciona para evitar reenvio acidental. Os valores exibidos no HTML são escapados e as ações de alteração exigem token CSRF.

## Solução de problemas

| Problema | O que conferir |
| --- | --- |
| “Vamos conectar o banco?” | MySQL iniciado, credenciais e porta corretas, banco importado e migração aplicada |
| “could not find driver” no log do PHP | Habilite a extensão `pdo_mysql` no `php.ini` e reinicie o Apache |
| Página PHP aparece como texto | Abra pelo endereço localhost; não abra o arquivo com duplo clique |
| Erro de CPF, e-mail ou ISBN duplicado | Use valores únicos; ISBN vazio é permitido |
| Nenhum leitor no novo empréstimo | Cadastre um usuário com perfil Leitor e status Ativo |
| Nenhum livro no novo empréstimo | Cadastre um livro com quantidade disponível maior que zero |
| Login temporariamente indisponível | Confira permissão de escrita no diretório temporário do PHP |
| “Formulário expirado” | Atualize a página e tente novamente |

Para hospedagem, configure a raiz pública como `public/`, utilize credenciais próprias e HTTPS. Os arquivos `.htaccess` protegem `app`, `config` e `database` quando o projeto fica dentro do `htdocs` do Apache. Não copie o usuário root e a senha vazia do ambiente local para a hospedagem.
