# UDFlow — backend

Central de automações da UDLOG. Esse README é o mapa de tudo que já
foi montado e do que ainda falta pra próxima etapa.

## Como tá organizado

Segue o mesmo padrão que já usamos no SysCheck e no etreinamento:
Model, Dao, Rn e Controller, cada camada com uma responsabilidade só.

```
src/
  model/        objeto que representa uma linha do banco, sem lógica nenhuma
  dao/          único lugar que roda SQL (sempre com PDO preparado)
  rn/           regra de negócio - valida, decide, orquestra
  controller/   recebe a requisição, chama o Rn certo, devolve a resposta
  util/         Csrf, controle de acesso, proteção de força bruta, Saida (escape/JSON)
  config/       Conexao.php (PDO)

config/
  bootstrap.php roda em toda requisição: autoload, .env, sessão
  rotas.php     mapa de todas as páginas (?pagina=algumacoisa)

public/
  index.php     porta de entrada única do sistema

database/
  udflow_schema.sql   roda uma vez só, na criação do banco

views/
  (a fazer - ver "o que falta" mais embaixo)
```

Se um dia quiser entender rápido um fluxo específico, segue o rastro:
`Controller` chama `Rn`, `Rn` chama `Dao`, `Dao` fala com o banco. Nunca
pula camada (um Controller não deve importar um Dao direto, por
exemplo) - isso mantém fácil de achar onde mexer quando alguma regra
mudar.

## Por que existe um `AutomacaoController` abstrato

KPI, Programação semanal e Estadia têm exatamente o mesmo fluxo:
buscar cliente, digitar e-mail, disparar. Em vez de copiar e colar o
mesmo código 3 vezes (e esquecer de corrigir em algum lugar quando
mudar algo), o fluxo comum mora em `AutomacaoController` e cada
automação só diz "eu sou a chave X e uso essa regra de negócio Y".
Hoje a única diferença de verdade entre elas é que o KPI trava o
e-mail em `@udlog` (`KpiExecucaoRn`) e as outras duas ainda não
(`ExecucaoRn` puro). Se um dia quiser essa trava em todo mundo, é só
mover a validação de volta pra classe mãe.

## Segurança - o que já tá implementado

- **SQL injection**: todo Dao usa PDO com `prepare()` + parâmetro
  nomeado. Em lugar nenhum do sistema uma string vinda do usuário
  entra direto dentro de um SQL.
- **Senha**: hash com `password_hash()` (bcrypt), nunca texto puro.
  Senha provisória (`Udlog123`) força troca no primeiro login via
  `trocar_senha_no_login`.
- **Código de redefinição de senha**: hash com `hash_hmac` + pepper
  do `.env`, expira em 15 min, é descartado depois de usado.
- **CSRF**: todo formulário que muda dado (POST) carrega um token de
  sessão validado com `hash_equals` antes de qualquer coisa
  acontecer.
- **Sessão**: cookie `HttpOnly` + `SameSite=Lax`, `Secure` em
  produção, ID regenerado no login (contra fixação de sessão),
  expira depois de 30 min parado.
- **Força bruta no login**: depois de 5 tentativas erradas, bloqueia
  a sessão temporariamente, com o tempo de bloqueio dobrando a cada
  nova sequência de erros.
- **Rotas fechadas por lista**: o `index.php` só aceita um
  `?pagina=` que já existe dentro de `rotas.php` - não tem como
  montar caminho de arquivo a partir do que o usuário digitou na
  URL.
- **XSS**: `Saida::e()` escapa qualquer valor antes de ir pra tela
  (falta só aplicar isso dentro das views, quando forem escritas).
- **Callback do n8n autenticado por token**, não por sessão - faz
  sentido, já que quem chama é o próprio n8n, não uma pessoa
  logada.

## Setup local (XAMPP)

1. `composer install`
2. Copia `.env.example` pra `.env` e preenche host/usuário/senha do
   MariaDB, o `APP_PEPPER` (qualquer string grande e aleatória) e o
   token do n8n.
3. Roda o `udflow_schema.sql` no banco.
4. Cadastra o primeiro super_admin direto no banco (só até existir a
   tela de admin de usuários funcionando):

```sql
INSERT INTO tb_usuarios (nome, usuario, email, senha_hash, super_admin, trocar_senha_no_login)
VALUES ('Bruno Santos', 'bruno.santos', 'bruno.santos@udlog.com', '<hash gerado com password_hash>', 1, 0);
```

(gera o hash rodando `php -r "echo password_hash('sua-senha', PASSWORD_DEFAULT);"`)

5. Aponta o `DocumentRoot` do site pra pasta `public/`.

## Testes

Cinco scripts na raiz do projeto, sem precisar de PHPUnit - só `php nome-do-arquivo.php` no terminal:

- `teste_manual_regras.php` - regras que não tocam banco (CSRF, força bruta, força de senha, sugestão de login, normalização de CNPJ, regex do @udlog). Roda em qualquer lugar, sem MariaDB.
- `teste_carregamento_classes.php` - carrega todas as classes do projeto e confere herança (AutomacaoController abstrato, as 3 automações implementando tudo certo, KpiExecucaoRn sobrescrevendo a validação de e-mail). Também sem banco.
- `teste_renderizar_views.php` - renderiza de verdade as telas de login/redefinição de senha (essas não dependem de banco).
- `teste_fluxo_completo.php` - **precisa de um MariaDB de verdade** (schema aplicado, `.env` configurado). Cria usuário, testa login, troca de senha, permissões (super_admin vs comum), cliente, cronograma, execução manual (com e sem webhook configurado) e redefinição de senha por código - tudo contra o banco real.
- `teste_views_logadas.php` - também precisa de banco. Renderiza Home, KPI, Programação semanal e as 5 telas de admin com dados reais.

Rodei todos os cinco contra um MariaDB de teste antes de fechar essa etapa: **165 conferências, 0 falhas**. Isso pegou 4 bugs reais que só apareciam com banco de verdade (documentados abaixo) - vale rodar de novo sempre que mexer em Dao ou Rn.

### Bugs encontrados testando contra banco real (já corrigidos)

Guardando aqui porque são exatamente o tipo de coisa que só aparece rodando de verdade, não só lendo o código:

1. **`LogAdminDao` quebrava com FK inválida** quando não havia um "executor" humano por trás da ação (ex: o primeiríssimo usuário do sistema, criado antes de existir qualquer admin). Corrigido tratando `usuario_id <= 0` como `NULL`.
2. **`PermissaoDao::definirPapel()` reusava o mesmo parâmetro nomeado duas vezes** na mesma query (`:papel` no INSERT e de novo no UPDATE). MariaDB com prepared statement de verdade (sem emulação, que é a configuração mais segura) não deixa fazer isso - precisa de `:papel` e `:papel2` apontando pro mesmo valor.
3. **`N8nRn::dispararWebhook()` estourava `TypeError`** quando a automação ainda não tinha `webhook_url` cadastrado. Agora `ExecucaoRn` confere isso antes e devolve um erro tratado ("essa automação ainda não tem webhook configurado").
4. **Fuso horário do PHP divergindo do fuso do MariaDB** - o mais sério dos quatro. O código de redefinição de senha calculava a expiração em PHP (`America/Sao_Paulo`) e comparava contra `NOW()` do banco, que roda no fuso do sistema operacional do servidor (geralmente UTC). Resultado: o código parecia expirado na hora de gerar. Corrigido em duas camadas - `Conexao.php` agora força `SET time_zone = '-03:00'` em toda conexão (fixo, já que o Brasil não tem mais horário de verão), e o cálculo de expiração do código passou a rodar dentro do próprio SQL (`DATE_ADD(NOW(), INTERVAL ...)`) em vez de vir pronto do PHP. Isso protege qualquer comparação de data futura do mesmo problema, não só essa.

## O que falta pra próxima etapa

- **Envio de e-mail de verdade**: o `LoginController::enviarCodigo()`
  já gera o código, só falta plugar o SMTP (PHPMailer, do jeito que
  já é feito no TERMOS) no lugar do `// TODO`.
- **Sincronizar `tb_cronograma` com o n8n**: hoje ativar/pausar ali é
  só um registro no banco, não desliga o cron de verdade - falta
  decidir se o n8n consulta essa tabela antes de rodar ou se o
  UDFlow chama a API do n8n pra reprogramar o trigger.
- **Cadastrar o `webhook_url` de cada automação** em `tb_automacoes`
  (hoje fica `NULL` no seed) - sem isso, qualquer disparo (manual ou
  a partir do Cronograma) responde com "webhook não configurado".
- **Primeiro super_admin real**: seguir o passo 4 do Setup local
  abaixo pra cadastrar você mesmo direto no banco.


testar 
6. Configurar no cPanel (não é arquivo, é tela)

cPanel → Cron Jobs → Adicionar novo:

Minuto: * (todo minuto)
Hora, Dia, Mês, Dia da semana: * em todos
Comando: php /home/SEU_USUARIO_CPANEL/public_html/udflow/cron/executar_agendamentos.php

Testei isso tudo de ponta a ponta contra um MariaDB de verdade — 8 conferências, 0 falhas: item diário rodando todo dia, item mensal só rodando no dia certo, item pausado não rodando mesmo no horário certo, e a execução automática ficando gravada com origem = 'automatico' e sem usuário atrelado (bem diferente de um clique manual).