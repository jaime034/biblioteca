# Validação do projeto

Data: 08/10/2026.
Ambiente: PHP 8.3.6 e MariaDB 10.11.14, com banco isolado de teste.

- 22 arquivos PHP verificados sem erros de sintaxe.
- 63 verificações funcionais aprovadas, usando requisições HTTP e consultas ao banco real.
- Concorrência testada com processos PHP e conexões de banco independentes.
- A versão final não apresentou avisos, erros fatais ou depreciações do PHP nos fluxos testados.
- O esquema original incluído foi comparado byte a byte com o SQL enviado.

## Verificações realizadas

- Migração preserva o esquema original e aceita reexecução.
- Acervo de exemplo importa sem duplicação.
- Cópia do SQL original permanece idêntica.
- Páginas protegidas exigem login.
- Primeiro acesso abre a instalação.
- Primeira conta criada com senha protegida.
- Senha armazenada como hash.
- Instalação fecha após criar administrador.
- Mensagem de instalação aparece no login.
- Senha incorreta não autentica.
- Login funciona e sessão usa cookies protegidos.
- Edição carrega quantidades numéricas do banco.
- Tela acessível: index.php.
- Tela acessível: livros.php.
- Tela acessível: usuarios.php.
- Tela acessível: emprestimos.php.
- Tela acessível: multas.php.
- Tela acessível: livro.php?id=1.
- Tela acessível: livro_form.php.
- Tela acessível: usuario_form.php.
- Tela acessível: emprestimo_form.php.
- Leitor sem senha não autentica usando o hash de comparação.
- Cadastros de leitores e bibliotecário funcionam.
- Bibliotecário autentica.
- Bibliotecário não cria administradores.
- Bibliotecário não altera contas administrativas.
- Cadastro de livro relaciona autores, categoria e estoque.
- Conteúdo do usuário é escapado no HTML.
- Falha de ISBN duplicado desfaz toda a transação.
- POST sem CSRF não altera os dados.
- Datas inexistentes são recusadas.
- Empréstimo reduz o estoque e registra o responsável.
- Não empresta quando não há exemplares.
- Aumento de exemplares preserva os empréstimos ativos.
- Impede empréstimos simultâneos do mesmo título para o mesmo leitor.
- Segundo exemplar pode ser emprestado a outro leitor.
- Total não pode ficar menor que os exemplares emprestados.
- Leitor com senha autentica.
- Leitor inicia no catálogo.
- Leitor não acessa cadastros administrativos.
- Leitor não acessa empréstimo de outra pessoa.
- Listagem do leitor contém apenas seus empréstimos.
- Leitor não registra a própria devolução.
- Devolução registra data real e repõe o estoque.
- Devolução repetida não duplica estoque.
- Atraso é calculado pela data prevista.
- Atraso gera multa correta na devolução.
- Quitação da multa atualiza valor pago e status.
- Leitor não acessa quitação de multa.
- Bibliotecário não cancela multa.
- Administrador cancela multa.
- Livro com histórico de empréstimos não pode ser excluído.
- Livro sem histórico pode ser excluído após confirmação.
- Busca usa parâmetros sem injeção SQL.
- Administrador não desativa a própria conta.
- Dois empréstimos concorrentes disputam corretamente o último exemplar.
- Duas devoluções concorrentes repõem apenas um exemplar.
- Desativação revoga o acesso da sessão já aberta.
- Conta inativa não autentica.
- Limite de tentativas persiste entre requisições.
- Logout destrói a sessão e bloqueia páginas protegidas.
- Estoque final mantém disponibilidade dentro do total.
- Nenhum aviso, erro fatal ou depreciação do PHP nos fluxos testados.

## Limite de verificação

A conferência visual em navegador não estava disponível neste ambiente. As regras de responsividade foram implementadas em CSS, mas o layout deve ser conferido no navegador durante a apresentação. O ambiente Windows/XAMPP não foi executado aqui; as instruções usam a organização de pastas e a configuração local descritas no LEIA-ME.md.

Os registros, senhas e arquivos temporários dos testes não fazem parte do projeto entregue.
