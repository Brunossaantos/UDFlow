# UDFlow

Central de automações da UDLOG. Um único lugar onde qualquer pessoa da
empresa dispara relatórios e processos que rodavam soltos (planilha,
e-mail manual, favorito de navegador perdido) — e onde a TI enxerga
tudo o que rodou, quando e com que resultado.

## Sumário

- [O que o sistema faz](#o-que-o-sistema-faz)
- [Como é organizado](#como-é-organizado)
- [O motor genérico de automações](#o-motor-genérico-de-automações)
- [Papéis e permissões](#papéis-e-permissões)
- [Leo — assistente virtual](#leo--assistente-virtual)
- [Log de sistema](#log-de-sistema)
- [Segurança](#segurança)
- [Banco de dados](#banco-de-dados)
- [Setup local (XAMPP)](#setup-local-xampp)
- [Deploy em produção (Hostgator)](#deploy-em-produção-hostgator)
- [Testes](#testes)
- [O que falta / próximos passos](#o-que-falta--próximos-passos)

---

## O que o sistema faz

Cada **automação** (KPI, Estadia, Programação Semanal, Relatório de
Avarias, ou qualquer outra que for cadastrada) segue o mesmo fluxo:

1. A pessoa loga, escolhe a automação na barra lateral, busca o
   cliente e informa o e-mail de destino.
2. O UDFlow monta um payload (JSON) com os dados configurados pra
   aquela automação e dispara um webhook pro **n8n**, que faz o
   trabalho pesado (gerar relatório, montar planilha, etc).
3. O n8n chama de volta um endpoint do UDFlow (`n8n-callback`)
   avisando se deu certo ou não. A tela "Minhas solicitações" mostra o
   status (Pendente → Processando → Concluído/Erro) **se atualizando
   sozinha**, sem precisar recarregar a página.
4. Se o n8n não responder em até 3 minutos, o UDFlow marca a execução
   como erro automaticamente — ninguém fica esperando pra sempre uma
   resposta que não vai chegar.

Além do fluxo de execução, o sistema também cobre agendamento
automático (cronograma), cadastro de clientes, controle de quem pode
usar o quê, e um chat de IA (Leo) pra tirar dúvida sobre o próprio
sistema.

## Como é organizado

Arquitetura simples, em camadas, sem framework:

```
src/
  model/        objeto que representa uma linha do banco, sem lógica
  dao/          único lugar que roda SQL (sempre PDO com prepared statement)
  rn/           regra de negócio - valida, decide, orquestra
  controller/   recebe a requisição, chama o Rn certo, devolve a resposta
  util/         Csrf, ControleAcesso, LogSistema, Mailer, PayloadBuilder, Saida (escape)
  config/       Conexao.php (PDO)

config/
  bootstrap.php     roda em toda requisição web: autoload, .env, sessão, log de erros
  bootstrap-cli.php mesma coisa, versão enxuta pro cron (sem sessão)
  rotas.php         mapa de todas as páginas (?pagina=algumacoisa)

public/
  index.php     porta de entrada única do sistema

cron/
  executar_agendamentos.php   chamado pelo Cron Job do cPanel a cada minuto

database/
  udflow_schema.sql       schema completo, roda uma vez só (banco novo)
  novos_recursos.sql      migrações incrementais, aplicadas manualmente uma a uma

views/
  admin/        telas de administração
  partials/     cabeçalho, rodapé, e o formulário de execução (compartilhado por todas as automações)
```

O rastro de uma requisição é sempre o mesmo:
**`index.php` → `Controller` → `Rn` → `Dao` → banco.** Nunca se pula
camada (um Controller não fala direto com um Dao) — isso mantém fácil
achar onde mexer quando uma regra muda.

## O motor genérico de automações

KPI, Estadia, Programação Semanal e Relatório de Avarias **não têm
controller próprio**. Todas passam por um único
[`AutomacaoController`](src/controller/AutomacaoController.php), que
descobre qual automação é pela URL (`?pagina=kpi`, `?pagina=estadia`,
etc), carrega a configuração dela do banco (tabela `tb_automacoes`) e
usa sempre a mesma view ([`views/automacao.php`](views/automacao.php)).
Cadastrar uma automação nova não exige escrever código — é tudo feito
pela tela **Automações** e **Configurar Automações** (ambas exclusivas
do super_admin).

**Configurar Automações** é onde se decide *o que* vai no payload
enviado pro n8n:

- **Campos**: quais chaves entram no JSON (`execucaoId`, `codigo_cliente`,
  `cor_secundaria`, etc), obrigatórios ou não, com tipo de dado
  (string, integer, email, data...).
- **Regras**: de onde cada campo tira o valor — `map_from_banco` busca
  direto de uma tabela (ex: `logo_url` de `tb_clientes_config`),
  `fixed_value` é um valor fixo, `timestamp`/`uuid` geram na hora,
  `expression`/`if_condition` avaliam uma expressão (usando
  [`symfony/expression-language`](https://symfony.com/doc/current/components/expression_language.html) —
  **não é `eval()` de PHP puro**, é uma linguagem de expressão restrita,
  sem acesso a função nenhuma do sistema).
- **Headers**: cabeçalhos HTTP extras enviados no webhook.
- **Logs**: toda chamada ao n8n fica registrada (URL, payload, resposta,
  tempo de execução), visível nessa mesma tela.

Isso é implementado em [`PayloadBuilder`](src/util/PayloadBuilder.php) e
[`AutomacaoConfigRn`](src/rn/AutomacaoConfigRn.php).

## Papéis e permissões

| Papel | O que pode fazer |
|---|---|
| **usuário** | Usa as automações liberadas pra ele — busca cliente, dispara, acompanha status. |
| **admin** | Tudo que usuário faz, mais: gerencia clientes e cronograma das automações em que é admin. |
| **super_admin** | Acesso total: cria usuário, cria/edita automação, configura payload/webhook (Configurar Automações) e vê o Log de sistema. Não passa pela tabela de permissões por automação — sempre tem acesso a tudo. |

A checagem acontece em duas camadas: `config/rotas.php` decide se a
rota exige login e um papel mínimo numa automação específica; dentro
do Controller, [`ControleAcesso`](src/util/ControleAcesso.php) faz a
checagem de verdade antes de qualquer dado ser tocado.

Usuário novo recebe senha provisória e é obrigado a trocar no primeiro
login. Esqueceu a senha? "Redefinir agora" na tela de login manda um
código de 6 dígitos por e-mail, válido por 15 minutos.

## Leo — assistente virtual

Chat de IA (canto inferior direito, em toda tela logada) que só
responde sobre o UDFlow — pergunta fora do assunto é recusada
educadamente. Implementado em [`ChatRn`](src/rn/ChatRn.php), roda no
modelo `openai/gpt-oss-20b` via API da Groq. Tom corporativo, direto,
sem gírias, sem emoji, respostas curtas (a instrução de sistema inteira
sobre como o UDFlow funciona cabe no próprio prompt — o sistema é
pequeno o suficiente pra não precisar de busca vetorial).

## Log de sistema

Tela exclusiva do **super_admin** (`Administração → Logs do sistema`)
que reúne automaticamente qualquer erro do sistema — sem precisar
entrar no servidor pra ler arquivo de log:

- **Captura automática**: `set_error_handler`, `set_exception_handler`
  e `register_shutdown_function` (fatais) registrados em
  `config/bootstrap.php` e `config/bootstrap-cli.php`, cobrindo tanto
  requisição web quanto o cron.
- **Captura manual** nos pontos de negócio que mais importam: falha ao
  chamar o webhook do n8n, falha ao mandar e-mail, falha na API do
  Leo, falha ao montar ou validar um payload.

Implementado em [`LogSistema`](src/util/LogSistema.php) (fachada que
nunca deixa uma falha *ao logar* derrubar a requisição — se gravar no
banco falhar, cai pro `error_log` de arquivo) e
[`LogSistemaDao`](src/dao/LogSistemaDao.php).

## Segurança

- **SQL injection**: todo Dao usa PDO com `prepare()` + parâmetro
  nomeado. Nenhuma string vinda do usuário entra direto num SQL.
- **Senha**: hash com `password_hash()` (bcrypt). Senha provisória
  força troca no primeiro login.
- **Código de redefinição de senha**: hash com `hash_hmac` + pepper do
  `.env`, expira em 15 min, descartado depois de usado.
- **CSRF**: todo formulário que muda dado (POST) carrega um token de
  sessão validado com `hash_equals`.
- **Sessão**: cookie `HttpOnly` + `SameSite=Lax`, `Secure` em produção,
  expira depois de 30 min parado.
- **Força bruta no login**: depois de 5 tentativas erradas, bloqueia
  temporariamente, dobrando o tempo de bloqueio a cada nova sequência.
- **Rotas fechadas por lista**: `index.php` só aceita um `?pagina=`
  que já existe em `rotas.php` — não tem como montar caminho de
  arquivo a partir do que a pessoa digitou na URL.
- **XSS**: `Saida::e()` escapa qualquer valor antes de ir pra view.
- **Callback do n8n autenticado por token**, não por sessão — faz
  sentido, já que quem chama é o próprio n8n.
- **Regras de expressão sandboxed**: `symfony/expression-language` no
  lugar de `eval()` de PHP puro nas regras de payload configuráveis —
  sem acesso a função do sistema, só operadores e as variáveis de
  entrada.

## Banco de dados

MariaDB/MySQL. Tabelas principais:

| Tabela | Guarda |
|---|---|
| `tb_usuarios` | Login, papel (super_admin), status |
| `tb_unidades` | Mauá I, Mauá II, etc |
| `tb_clientes` | Cadastro único, compartilhado por todas as automações |
| `tb_clientes_config` | Logo e cores de capa por cliente (usado nos relatórios que aceitam identidade visual) |
| `tb_cliente_automacao` | Quais clientes estão habilitados em quais automações |
| `tb_usuario_automacao` | Papel de cada usuário em cada automação |
| `tb_automacoes` | Cadastro dinâmico de cada automação (chave, webhook, ícone, textos) |
| `tb_automacao_payload_campos` / `_regras` / `_webhook_headers` | Configuração do payload/headers de cada automação |
| `tb_automacao_webhook_log` | Histórico de toda chamada ao n8n |
| `tb_execucoes` | Cada disparo (manual ou automático), com status |
| `tb_cronograma` | Agendamento automático por cliente + automação |
| `tb_logs_admin` | Auditoria de ações administrativas |
| `tb_logs_sistema` | Erros/exceptions/fatais capturados automaticamente |
| `tb_password_resets` | Códigos de redefinição de senha |

**Ordem de aplicação num banco novo:**

1. `database/udflow_schema.sql` — cria tudo do zero.
2. `database/novos_recursos.sql` — migrações incrementais que vieram
   depois do schema inicial. **Lido de cima pra baixo, um bloco de
   cada vez** — cada bloco tem um comentário dizendo o que faz e se
   depende do anterior. Não é idempotente por design (evita rodar a
   mesma migração duas vezes sem perceber): se um bloco já foi
   aplicado, não rode de novo.

## Setup local (XAMPP)

1. `composer install`
2. Copia `.env.example` pra `.env` e preenche host/usuário/senha do
   MariaDB, o `APP_PEPPER` (string grande e aleatória), SMTP e as
   chaves de n8n/Groq.
3. Roda `database/udflow_schema.sql` no banco, depois cada bloco de
   `database/novos_recursos.sql` que ainda não tiver sido aplicado.
4. Cadastra o primeiro `super_admin` direto no banco (só até existir
   uma pessoa que possa criar outras pela tela):

```sql
INSERT INTO tb_usuarios (nome, usuario, email, senha_hash, super_admin, trocar_senha_no_login)
VALUES ('Seu Nome', 'seu.usuario', 'seu@email.com', '<hash gerado com password_hash>', 1, 0);
```

Gera o hash com:

```bash
php -r "echo password_hash('sua-senha', PASSWORD_DEFAULT);"
```

5. Aponta o `DocumentRoot` do site pra pasta `public/`.

## Deploy em produção (Hostgator)

1. **PHP**: confirma no MultiPHP Selector do cPanel que a versão
   selecionada é **8.0.2 ou mais recente** (o
   `symfony/expression-language` exige isso).
2. Sobe os arquivos do projeto (exceto `vendor/` e `.env` — nenhum dos
   dois vai pro Git).
3. No servidor: `composer install --no-dev` (não precisa do PHPUnit em
   produção).
4. Cria o `.env` de produção (mesmas variáveis do `.env.example`,
   `APP_AMBIENTE=producao`).
5. Aplica `database/udflow_schema.sql` e depois cada bloco pendente de
   `database/novos_recursos.sql` no banco de produção.
6. Cadastra o primeiro `super_admin` (mesmo passo do setup local).
7. Aponta o domínio/subdomínio pra pasta `public/`.
8. Configura o Cron Job no cPanel (**Cron Jobs → Adicionar novo**):

   | Campo | Valor |
   |---|---|
   | Minuto | `*` (todo minuto) |
   | Hora / Dia / Mês / Dia da semana | `*` em todos |
   | Comando | `php /home/SEU_USUARIO_CPANEL/public_html/udflow/cron/executar_agendamentos.php` |

9. Em **Automações**, cadastra o `webhook_url` de cada automação —
   sem isso, qualquer disparo (manual ou pelo Cronograma) responde
   "webhook não configurado".
10. Confere que `storage/logs/` existe e tem permissão de escrita
    (o `.gitkeep` garante que a pasta vai junto no deploy).

Depois do primeiro deploy, `git pull` + `composer install` (sem
`--no-dev` se for rodar teste no próprio servidor) é suficiente pra
atualizações — só rodar migração nova de `novos_recursos.sql` quando
tiver alguma pendente.

## Testes

PHPUnit, configurado em `phpunit.xml`:

```bash
vendor/bin/phpunit
```

Os testes rodam contra o banco local de verdade configurado no
`.env` (não é um banco de teste separado) — cada teste que grava dado
abre uma transação e dá `rollback` no `tearDown`, então nada fica
gravado. Cobertura atual: `tests/PayloadBuilderTest.php`, focado no
`PayloadBuilder` (resolução de `map_from_banco`, campo obrigatório
faltando, conversão de tipo).

## O que falta / próximos passos

- **Sincronizar `tb_cronograma` com o n8n de verdade**: hoje
  ativar/pausar um item do cronograma só muda um registro no banco —
  quem efetivamente decide se dispara é o cron do UDFlow lendo essa
  tabela a cada minuto (já funciona), mas não existe um caminho pro
  n8n consultar/ser avisado dessa mudança fora desse ciclo.
- **Cadastrar o `webhook_url`** de cada automação nova assim que for
  criada — item 9 do deploy acima, fácil de esquecer.
