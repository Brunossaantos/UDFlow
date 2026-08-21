-- =====================================================================
-- UDFLOW — SCHEMA DE BANCO DE DADOS
-- Motor: MariaDB 10.4+ | Charset: utf8mb4 | Engine: InnoDB
--
-- Convenção de nomes:
--   tb_  -> tabelas
--   vw_  -> views
--   *_id -> chave estrangeira para a tabela correspondente
--   ativo (TINYINT 0/1) -> soft delete (nunca DELETE físico em cadastros)
--
-- Este arquivo é a fonte de verdade pra um banco NOVO (deploy do
-- zero). Reflete o banco real em produção/dev na data da última
-- atualização - se algo aqui divergir do banco de verdade de novo no
-- futuro, o jeito mais seguro de corrigir é gerar de novo a partir do
-- banco real (`mysqldump --no-data`), não tentar adivinhar.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. UNIDADES
-- Ex.: Mauá I, Mauá II. Cadastro à parte (não ENUM) porque a UDLOG
-- pode abrir novas unidades no futuro sem precisar migração de schema.
-- ---------------------------------------------------------------------
CREATE TABLE tb_unidades (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(20)  NOT NULL,      -- MAUA_I, MAUA_II
    nome            VARCHAR(60)  NOT NULL,      -- Mauá I
    ativo           TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_unidades_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. AUTOMAÇÕES
-- Cada linha aqui é um item da sidebar E de fato define a rota
-- (?pagina={slug de "rota" sem o prefixo /automacoes/}) - o
-- AutomacaoController resolve tudo dinamicamente contra essa tabela,
-- não precisa de código novo pra cadastrar uma automação.
--
-- `posicao` é o que controla a ordem de exibição (sidebar, Início,
-- telas de admin) - convenção de usar passos de 10 (10, 20, 30...)
-- pra sobrar espaço de inserir no meio depois sem precisar renumerar
-- tudo. `ordem_menu` é um campo legado, não é mais lido em lugar
-- nenhum do código - mantido só pra não quebrar quem ainda tiver
-- alguma consulta antiga referenciando ele.
-- `visivel_para_usuarios` é o interruptor que restringe uma automação
-- só a admins (super_admin sempre enxerga tudo, independente deste
-- campo).
-- ---------------------------------------------------------------------
CREATE TABLE tb_automacoes (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave                       VARCHAR(40)  NOT NULL,      -- kpi, programacao_semanal, estadia (sempre com underscore)
    nome                        VARCHAR(80)  NOT NULL,      -- KPI · Relatórios Anuais
    icone                       VARCHAR(40)  NOT NULL DEFAULT 'automacao', -- legado, não é mais renderizado (ver icon_svg)
    rota                        VARCHAR(80)  NOT NULL,      -- /automacoes/kpi (sempre com hífen, é o slug de ?pagina=)
    webhook_url                 VARCHAR(255) NULL,          -- endpoint do n8n
    webhook_token                VARCHAR(255) NULL,          -- token de autenticação do webhook (ver observação no final do arquivo)
    permite_execucao_manual     TINYINT(1)   NOT NULL DEFAULT 1,
    possui_agendamento          TINYINT(1)   NOT NULL DEFAULT 0,
    visivel_para_usuarios       TINYINT(1)   NOT NULL DEFAULT 1,
    ativo                       TINYINT(1)   NOT NULL DEFAULT 1,
    ordem_menu                  SMALLINT UNSIGNED NOT NULL DEFAULT 0, -- legado, ver comentário acima
    criado_em                   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    webhook_metodo               ENUM('GET','POST','PUT','PATCH','DELETE') DEFAULT 'POST',
    posicao                     INT          DEFAULT 10 COMMENT 'Ordem de exibição (10,20,30...)',
    icon_svg                    LONGTEXT     NULL COMMENT 'Conteúdo interno de um <svg viewBox="0 0 24 24"> (path/circle/rect)',
    timeout_ms                  INT          DEFAULT 5000 COMMENT 'Timeout do webhook em milissegundos',
    label_botao                 VARCHAR(100) DEFAULT 'Executar agora',
    aviso_email_udlog           TINYINT(1)   DEFAULT 0 COMMENT 'Se 1, restringe e-mail de destino a @udlog',
    aviso_proximo_disparo       TEXT         NULL COMMENT 'Mensagem exibida na tela sobre o próximo disparo automático',
    mostrar_coluna_origem       TINYINT(1)   DEFAULT 0 COMMENT 'Se 1, mostra a coluna Manual/Automático em "minhas solicitações"',
    UNIQUE KEY uk_automacoes_chave (chave),
    KEY idx_posicao (posicao),
    KEY idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. USUÁRIOS
-- `super_admin` é global (não passa pela tabela de permissões por
-- automação). `trocar_senha_no_login` força a troca da senha
-- provisória "Udlog123" no primeiro acesso.
-- ---------------------------------------------------------------------
CREATE TABLE tb_usuarios (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome                    VARCHAR(120) NOT NULL,
    usuario                 VARCHAR(60)  NOT NULL,      -- login, ex: bruno.carvalho
    email                   VARCHAR(150) NOT NULL,      -- pessoal, usado na redefinição de senha
    senha_hash              VARCHAR(255) NOT NULL,
    super_admin             TINYINT(1)   NOT NULL DEFAULT 0,
    trocar_senha_no_login   TINYINT(1)   NOT NULL DEFAULT 1,
    ativo                   TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuarios_usuario (usuario),
    UNIQUE KEY uk_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. CLIENTES
-- Cadastro único, compartilhado por todas as automações. O mesmo
-- cliente (mesmo CNPJ) pode existir em mais de uma unidade (ex:
-- "Monfiza" em Mauá I e Mauá II) - por isso o CNPJ só é único DENTRO
-- da unidade, não globalmente. `codigo_talent` é opcional, exigido só
-- pelo Relatório de Avarias (ver ExecucaoRn).
-- `razao_social` = nome jurídico completo. `nome_exibicao` = nome
-- curto usado nos relatórios/telas (ex: "Cargill Colina").
-- ---------------------------------------------------------------------
CREATE TABLE tb_clientes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unidade_id          INT UNSIGNED NOT NULL,
    codigo_cliente      VARCHAR(40)  NOT NULL,      -- mesmo código usado no n8n, ex: CARGILL_62
    codigo_talent       VARCHAR(30)  NULL,          -- código no sistema Talent, exigido só pelo Relatório de Avarias
    razao_social        VARCHAR(180) NOT NULL,
    nome_exibicao       VARCHAR(80)  NOT NULL,
    cnpj                CHAR(14)     NOT NULL,      -- somente dígitos, sem máscara
    email_responsavel   VARCHAR(150) NULL,
    ativo               TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_clientes_codigo (codigo_cliente),
    UNIQUE KEY uk_clientes_unidade_cnpj (unidade_id, cnpj),
    UNIQUE KEY uk_unidade_codigo_talent (unidade_id, codigo_talent),
    KEY idx_clientes_unidade (unidade_id),
    CONSTRAINT fk_clientes_unidade FOREIGN KEY (unidade_id) REFERENCES tb_unidades (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. CONFIGURAÇÃO VISUAL DO CLIENTE
-- Logo e cores de capa usadas nos relatórios que aceitam identidade
-- visual (hoje só o KPI usa, via regra map_from_banco na configuração
-- de payload - mas qualquer automação pode usar). Fica fora de
-- tb_clientes de propósito: nem todo cliente/automação precisa disso.
-- ---------------------------------------------------------------------
CREATE TABLE tb_clientes_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT UNSIGNED NOT NULL,
    logo_url        VARCHAR(255) NULL,
    cor_primaria    CHAR(7)      NULL,      -- #005c12
    cor_secundaria  CHAR(7)      NULL,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_clientes_config_cliente (cliente_id),
    CONSTRAINT fk_clientes_config_cliente FOREIGN KEY (cliente_id) REFERENCES tb_clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. CLIENTE × AUTOMAÇÃO
-- Habilita/desabilita um cliente automação por automação
-- (ex: ativo no KPI, desativado na Estadia).
-- ---------------------------------------------------------------------
CREATE TABLE tb_cliente_automacao (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT UNSIGNED NOT NULL,
    automacao_id    INT UNSIGNED NOT NULL,
    ativo           TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cliente_automacao (cliente_id, automacao_id),
    CONSTRAINT fk_ca_cliente FOREIGN KEY (cliente_id) REFERENCES tb_clientes (id) ON DELETE CASCADE,
    CONSTRAINT fk_ca_automacao FOREIGN KEY (automacao_id) REFERENCES tb_automacoes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. USUÁRIO × AUTOMAÇÃO (permissão)
-- Papel por automação. super_admin (tb_usuarios) não passa por aqui:
-- ele tem acesso admin a tudo por definição.
-- ---------------------------------------------------------------------
CREATE TABLE tb_usuario_automacao (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NOT NULL,
    automacao_id    INT UNSIGNED NOT NULL,
    papel           ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario_automacao (usuario_id, automacao_id),
    CONSTRAINT fk_ua_usuario FOREIGN KEY (usuario_id) REFERENCES tb_usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_ua_automacao FOREIGN KEY (automacao_id) REFERENCES tb_automacoes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. EXECUÇÕES
-- Tabela única pra todas as automações — é o que alimenta "minhas
-- solicitações" (filtrando por usuario_id) e "Logs e status" (sem
-- filtro, visão do admin). origem=automatico + usuario_id NULL
-- representa um disparo do cronograma, sem usuário por trás.
-- Execuções pendente/processando com mais de 3 minutos são expiradas
-- automaticamente pra "erro" (ver ExecucaoDao::expirarPendentesAntigas).
-- ---------------------------------------------------------------------
CREATE TABLE tb_execucoes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automacao_id        INT UNSIGNED NOT NULL,
    cliente_id          INT UNSIGNED NOT NULL,
    usuario_id          INT UNSIGNED NULL,          -- NULL quando origem = automatico
    origem              ENUM('manual','automatico') NOT NULL DEFAULT 'manual',
    email_destino       VARCHAR(150) NOT NULL,
    status              ENUM('pendente','processando','concluido','erro') NOT NULL DEFAULT 'pendente',
    mensagem_erro       VARCHAR(255) NULL,
    n8n_execution_id    VARCHAR(80)  NULL,
    arquivo_url         VARCHAR(255) NULL,
    mes_referencia      TINYINT UNSIGNED NULL,
    ano_referencia      SMALLINT UNSIGNED NULL,
    criado_em           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalizado_em       DATETIME     NULL,
    KEY idx_execucoes_automacao (automacao_id),
    KEY idx_execucoes_cliente (cliente_id),
    KEY idx_execucoes_usuario (usuario_id),
    KEY idx_execucoes_status (status),
    CONSTRAINT fk_exec_automacao FOREIGN KEY (automacao_id) REFERENCES tb_automacoes (id),
    CONSTRAINT fk_exec_cliente FOREIGN KEY (cliente_id) REFERENCES tb_clientes (id),
    CONSTRAINT fk_exec_usuario FOREIGN KEY (usuario_id) REFERENCES tb_usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. CRONOGRAMA
-- Agendamento automático, lido a cada minuto por cron/executar_agendamentos.php
-- (cron do cPanel). `frequencia` = diária (roda todo dia, ou só em
-- dias_semana específicos, ex: "1,2,3,4,5" = seg a sex) ou mensal
-- (roda uma vez, no dia_mes configurado). IMPORTANTE: isso é só o
-- agendamento dentro do UDFlow — não reprograma nada dentro do n8n,
-- o n8n só recebe o webhook quando o cron dispara.
-- ---------------------------------------------------------------------
CREATE TABLE tb_cronograma (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automacao_id    INT UNSIGNED NOT NULL,
    cliente_id      INT UNSIGNED NOT NULL,
    frequencia      ENUM('diaria','mensal') NOT NULL DEFAULT 'mensal',
    dias_semana     VARCHAR(20)  NULL,          -- "1,2,3,4,5" (1=segunda...7=domingo), NULL = todo dia
    dia_mes         TINYINT UNSIGNED NULL,      -- só pra frequencia=mensal
    horario         TIME         NOT NULL,
    ativo           TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cronograma (automacao_id, cliente_id, horario),
    CONSTRAINT fk_cron_automacao FOREIGN KEY (automacao_id) REFERENCES tb_automacoes (id) ON DELETE CASCADE,
    CONSTRAINT fk_cron_cliente FOREIGN KEY (cliente_id) REFERENCES tb_clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. REDEFINIÇÃO DE SENHA
-- Código de 6 dígitos, hasheado (nunca texto puro), expira em 15 min
-- e é descartado após o uso.
-- ---------------------------------------------------------------------
CREATE TABLE tb_password_resets (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NOT NULL,
    codigo_hash     VARCHAR(255) NOT NULL,
    expira_em       DATETIME     NOT NULL,
    usado           TINYINT(1)   NOT NULL DEFAULT 0,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_password_resets_usuario (usuario_id),
    CONSTRAINT fk_reset_usuario FOREIGN KEY (usuario_id) REFERENCES tb_usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11. LOG DE AUDITORIA (admin)
-- Quem mexeu em quê: criação de usuário, troca de cor de cliente,
-- ativação de automação etc.
-- ---------------------------------------------------------------------
CREATE TABLE tb_logs_admin (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NULL,
    acao            VARCHAR(80)  NOT NULL,      -- ex: cliente.atualizado, usuario.criado
    entidade        VARCHAR(60)  NULL,          -- ex: tb_clientes
    entidade_id     INT UNSIGNED NULL,
    detalhes        TEXT         NULL,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_logs_admin_usuario (usuario_id),
    CONSTRAINT fk_logs_admin_usuario FOREIGN KEY (usuario_id) REFERENCES tb_usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 12. LOG DE SISTEMA
-- Erros/exceptions/fatais capturados automaticamente (ver
-- src/util/LogSistema.php) + falhas de negócio relevantes (webhook,
-- e-mail, chat). Tela exclusiva do super_admin: ?pagina=admin-logs-sistema
-- ---------------------------------------------------------------------
CREATE TABLE tb_logs_sistema (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nivel       VARCHAR(20)  NOT NULL,      -- warning, error, exception, fatal
    mensagem    TEXT         NOT NULL,
    arquivo     VARCHAR(255) NULL,
    linha       INT          NULL,
    contexto    TEXT         NULL,          -- JSON livre: trace, url, usuario_id, etc.
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_logs_sistema_nivel (nivel),
    KEY idx_logs_sistema_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 13. CONFIGURAÇÃO DE PAYLOAD/WEBHOOK POR AUTOMAÇÃO
-- Suporta a tela "Configurar Automações": monta dinamicamente o JSON
-- enviado pro n8n (campos + de onde cada campo tira o valor) e os
-- headers HTTP customizados. Ver src/util/PayloadBuilder.php.
-- ---------------------------------------------------------------------

-- Campos que entram no payload (ex: execucaoId, codigo_cliente, cor_secundaria)
CREATE TABLE tb_automacao_payload_campos (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automacao_id            INT UNSIGNED NOT NULL,
    nome_campo              VARCHAR(100) NOT NULL,
    label_campo             VARCHAR(150) NULL,
    tipo_dado               ENUM('string','integer','decimal','boolean','email','timestamp','uuid','json','array','date','time') DEFAULT 'string',
    obrigatorio             TINYINT(1)   DEFAULT 0,
    valor_padrao            VARCHAR(255) NULL,
    validacao_customizada   LONGTEXT     CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(validacao_customizada)),
    posicao                 INT          DEFAULT 10,
    descricao               TEXT         NULL,
    criado_em                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_automacao_campo (automacao_id, nome_campo),
    KEY idx_automacao_id (automacao_id),
    KEY idx_posicao (posicao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Regras de transformação: de onde/como cada campo tira o valor
-- (fixed_value, map_from_banco, timestamp, uuid, expression via
-- symfony/expression-language, concatenate, if_condition).
CREATE TABLE tb_automacao_payload_regras (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automacao_id    INT UNSIGNED NOT NULL,
    campo_id        INT UNSIGNED NULL,
    tipo_regra      ENUM('fixed_value','map_from_banco','timestamp','uuid','expression','concatenate','if_condition') DEFAULT 'fixed_value',
    configuracao    LONGTEXT     CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(configuracao)),
    ativo           TINYINT(1)   DEFAULT 1,
    ordem_execucao  INT          DEFAULT 10,
    criado_em       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_automacao_id (automacao_id),
    KEY idx_campo_id (campo_id),
    KEY idx_tipo_regra (tipo_regra),
    CONSTRAINT fk_apr_campo FOREIGN KEY (campo_id) REFERENCES tb_automacao_payload_campos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Headers HTTP customizados enviados junto no webhook.
CREATE TABLE tb_automacao_webhook_headers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automacao_id    INT UNSIGNED NOT NULL,
    chave           VARCHAR(100) NOT NULL,
    valor           VARCHAR(500) NOT NULL,
    valor_dinamico  TINYINT(1)   DEFAULT 0,
    regra_id        INT UNSIGNED NULL,
    posicao         INT          DEFAULT 10,
    criado_em       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_automacao_header (automacao_id, chave),
    KEY idx_automacao_id (automacao_id),
    KEY idx_posicao (posicao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Histórico de toda chamada feita ao n8n (payload enviado, resposta,
-- status HTTP, tempo de execução) - alimenta a aba de Logs dentro de
-- "Configurar Automações".
CREATE TABLE tb_automacao_webhook_log (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automacao_id        INT UNSIGNED NOT NULL,
    url_enviado         VARCHAR(500) NULL,
    metodo_http         ENUM('GET','POST','PUT','PATCH','DELETE') DEFAULT 'POST',
    headers_enviado     LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(headers_enviado)),
    payload_enviado     LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(payload_enviado)),
    http_status         INT          NULL,
    headers_resposta    LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(headers_resposta)),
    resposta_webhook    LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(resposta_webhook)),
    resposta_texto      LONGTEXT     NULL,
    tempo_execucao_ms   INT          NULL,
    erro_tipo           VARCHAR(100) NULL,
    erro_mensagem       TEXT         NULL,
    cliente_id          INT          NULL,
    criado_em           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_automacao_id (automacao_id),
    KEY idx_cliente_id (cliente_id),
    KEY idx_criado_em (criado_em),
    KEY idx_http_status (http_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Auditoria de mudanças na configuração de payload (quem mudou o quê,
-- antes/depois em JSON) - independente do tb_logs_admin geral porque
-- guarda o snapshot da configuração, não só uma linha de texto.
CREATE TABLE tb_automacao_config_historico (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automacao_id            INT UNSIGNED NOT NULL,
    usuario_id              INT UNSIGNED NOT NULL,
    tabela_afetada          VARCHAR(100) NULL,
    registro_id             INT          NULL,
    tipo_operacao           ENUM('INSERT','UPDATE','DELETE') NULL,
    configuracao_anterior   LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(configuracao_anterior)),
    configuracao_nova       LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(configuracao_nova)),
    descricao               TEXT         NULL,
    criado_em               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_automacao_id (automacao_id),
    KEY idx_usuario_id (usuario_id),
    KEY idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Registro de testes de payload feitos pela tela de admin antes de
-- salvar uma configuração de verdade (botão "testar" em Configurar Automações).
CREATE TABLE tb_automacao_payload_testes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automacao_id    INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,
    dados_entrada   LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(dados_entrada)),
    payload_gerado  LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (JSON_VALID(payload_gerado)),
    sucesso         TINYINT(1)   DEFAULT 0,
    erro_mensagem   TEXT         NULL,
    criado_em       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_automacao_id (automacao_id),
    KEY idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- VIEWS
-- =====================================================================

-- vw_permissoes_usuario
-- Papel efetivo de cada usuário em cada automação, já considerando
-- super_admin. Consulta única pra checar permissão em qualquer tela.
CREATE VIEW vw_permissoes_usuario AS
SELECT
    u.id                     AS usuario_id,
    u.usuario,
    u.super_admin,
    a.id                     AS automacao_id,
    a.chave                  AS automacao_chave,
    a.visivel_para_usuarios,
    CASE
        WHEN u.super_admin = 1 THEN 'admin'
        ELSE ua.papel
    END                      AS papel_efetivo
FROM tb_usuarios u
CROSS JOIN tb_automacoes a
LEFT JOIN tb_usuario_automacao ua
    ON ua.usuario_id = u.id AND ua.automacao_id = a.id
WHERE u.ativo = 1 AND a.ativo = 1;

-- vw_execucoes_detalhadas
-- Junta execução + cliente + unidade + usuário + automação —
-- alimenta direto "minhas solicitações" e "Logs e status".
CREATE VIEW vw_execucoes_detalhadas AS
SELECT
    e.id,
    a.chave           AS automacao_chave,
    a.nome            AS automacao_nome,
    c.codigo_cliente,
    c.nome_exibicao   AS cliente_nome,
    un.nome           AS unidade_nome,
    u.nome            AS usuario_nome,
    e.origem,
    e.email_destino,
    e.status,
    e.mensagem_erro,
    e.mes_referencia,
    e.ano_referencia,
    e.criado_em,
    e.finalizado_em
FROM tb_execucoes e
JOIN tb_automacoes a ON a.id = e.automacao_id
JOIN tb_clientes c ON c.id = e.cliente_id
JOIN tb_unidades un ON un.id = c.unidade_id
LEFT JOIN tb_usuarios u ON u.id = e.usuario_id;

-- vw_cronograma_ativo
-- Só os disparos automáticos ligados, prontos pra tela de Cronograma.
CREATE VIEW vw_cronograma_ativo AS
SELECT
    cr.id,
    a.chave          AS automacao_chave,
    a.nome           AS automacao_nome,
    c.nome_exibicao  AS cliente_nome,
    un.nome          AS unidade_nome,
    cr.dia_mes,
    cr.horario,
    cr.ativo
FROM tb_cronograma cr
JOIN tb_automacoes a ON a.id = cr.automacao_id
JOIN tb_clientes c ON c.id = cr.cliente_id
JOIN tb_unidades un ON un.id = c.unidade_id
WHERE cr.ativo = 1;

-- v_automacao_completa
-- Resumo de configuração por automação (quantos campos/regras/headers
-- já foram cadastrados) - usado como painel rápido dentro de
-- "Configurar Automações".
CREATE VIEW v_automacao_completa AS
SELECT
    a.id,
    a.nome,
    a.chave,
    a.webhook_url,
    a.posicao,
    a.ativo,
    COUNT(DISTINCT apc.id)  AS total_campos,
    COUNT(DISTINCT apr.id)  AS total_regras,
    COUNT(DISTINCT apwh.id) AS total_headers
FROM tb_automacoes a
LEFT JOIN tb_automacao_payload_campos apc ON a.id = apc.automacao_id
LEFT JOIN tb_automacao_payload_regras apr ON a.id = apr.automacao_id
LEFT JOIN tb_automacao_webhook_headers apwh ON a.id = apwh.automacao_id
GROUP BY a.id;

-- v_automacao_ultima_execucao
-- Estatísticas de webhook por automação (última chamada, taxa de
-- sucesso, tempo médio) - painel de estatísticas dentro de
-- "Configurar Automações".
CREATE VIEW v_automacao_ultima_execucao AS
SELECT
    automacao_id,
    MAX(criado_em) AS ultima_execucao,
    COUNT(*) AS total_execucoes,
    SUM(CASE WHEN http_status = 200 THEN 1 ELSE 0 END) AS execucoes_sucesso,
    SUM(CASE WHEN http_status <> 200 THEN 1 ELSE 0 END) AS execucoes_erro,
    AVG(tempo_execucao_ms) AS tempo_medio_ms
FROM tb_automacao_webhook_log
GROUP BY automacao_id;

-- =====================================================================
-- DADOS INICIAIS (seed)
-- =====================================================================

INSERT INTO tb_unidades (codigo, nome) VALUES
    ('MAUA_I', 'Mauá I'),
    ('MAUA_II', 'Mauá II');

-- visivel_para_usuarios = 0 em Programação Semanal e Estadia e
-- Relatório de Avarias implementa a regra combinada: só admin/
-- super_admin veem essas automações por padrão, até liberar.
INSERT INTO tb_automacoes (chave, nome, icone, rota, webhook_metodo, permite_execucao_manual, possui_agendamento, visivel_para_usuarios, posicao, aviso_email_udlog, mostrar_coluna_origem) VALUES
    ('kpi', 'KPI', 'bar-chart', '/automacoes/kpi', 'POST', 1, 1, 1, 10, 1, 0),
    ('programacao_semanal', 'Programação Semanal', 'clock', '/automacoes/programacao-semanal', 'POST', 1, 1, 0, 20, 0, 1),
    ('estadia', 'Estadia', 'truck', '/automacoes/estadia', 'POST', 1, 1, 0, 30, 0, 1),
    ('relatorio_avarias', 'Relatório de Avarias', 'automacao', '/automacoes/relatorio-avarias', 'POST', 1, 0, 0, 40, 0, 0);

-- Observação sobre credenciais (webhook_token, SMTP, DB) —
-- ver decisão já alinhada: infraestrutura fixa (token do webhook,
-- SMTP, chave de sessão) fica no .env, nunca em texto puro no banco.
-- A coluna webhook_token acima existe só para o caso de vocês
-- decidirem, no futuro, rotacionar o token por automação direto
-- pela tela de admin — por ora pode ficar NULL e a aplicação lê do
-- .env mesmo.
