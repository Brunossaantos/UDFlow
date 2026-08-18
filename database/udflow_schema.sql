-- =====================================================================
-- UDFLOW — SCHEMA DE BANCO DE DADOS
-- Motor: MariaDB 10.4+ | Charset: utf8mb4 | Engine: InnoDB
--
-- Convenção de nomes:
--   tb_  -> tabelas
--   vw_  -> views
--   *_id -> chave estrangeira para a tabela correspondente
--   ativo (TINYINT 0/1) -> soft delete (nunca DELETE físico em cadastros)
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
-- Cada linha aqui é um item da sidebar. `visivel_para_usuarios` é o
-- interruptor que hoje deixa Programação semanal e Estadia visíveis
-- só pra quem é admin (super_admin sempre enxerga tudo, independente
-- deste campo).
-- ---------------------------------------------------------------------
CREATE TABLE tb_automacoes (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave                       VARCHAR(40)  NOT NULL,      -- kpi, mao_obra_batida, estadia
    nome                        VARCHAR(80)  NOT NULL,      -- KPI · Relatórios Anuais
    icone                       VARCHAR(40)  NOT NULL DEFAULT 'automacao',
    rota                        VARCHAR(80)  NOT NULL,      -- /automacoes/kpi
    webhook_url                 VARCHAR(255) NULL,          -- endpoint do n8n
    webhook_token                VARCHAR(255) NULL,          -- token de autenticação do webhook (ver observação no final do arquivo)
    permite_execucao_manual     TINYINT(1)   NOT NULL DEFAULT 1,
    possui_agendamento          TINYINT(1)   NOT NULL DEFAULT 0,
    visivel_para_usuarios       TINYINT(1)   NOT NULL DEFAULT 1,
    ativo                       TINYINT(1)   NOT NULL DEFAULT 1,
    ordem_menu                  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    criado_em                   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_automacoes_chave (chave)
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
-- Cadastro único, compartilhado pelas 3 automações.
-- `razao_social` = nome jurídico completo. `nome_exibicao` = nome
-- curto usado nos relatórios/telas (ex: "Cargill Colina").
-- ---------------------------------------------------------------------
CREATE TABLE tb_clientes (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unidade_id          INT UNSIGNED NOT NULL,
    codigo_cliente      VARCHAR(40)  NOT NULL,      -- mesmo código usado no n8n, ex: CARGILL_62
    razao_social        VARCHAR(180) NOT NULL,
    nome_exibicao       VARCHAR(80)  NOT NULL,
    cnpj                CHAR(14)     NOT NULL,      -- somente dígitos, sem máscara
    email_responsavel   VARCHAR(150) NULL,
    ativo               TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_clientes_codigo (codigo_cliente),
    UNIQUE KEY uk_clientes_cnpj (cnpj),
    KEY idx_clientes_unidade (unidade_id),
    CONSTRAINT fk_clientes_unidade FOREIGN KEY (unidade_id) REFERENCES tb_unidades (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. CONFIGURAÇÃO VISUAL DO CLIENTE — EXCLUSIVA DO KPI
-- Fica fora de tb_clientes de propósito: logo e cores só existem
-- pro KPI, então não fazem sentido como coluna de um cadastro
-- compartilhado pelas 3 automações.
-- ---------------------------------------------------------------------
CREATE TABLE tb_clientes_kpi_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT UNSIGNED NOT NULL,
    logo_url        VARCHAR(255) NULL,
    cor_primaria    CHAR(7)      NULL,      -- #005c12
    cor_secundaria  CHAR(7)      NULL,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_kpi_config_cliente (cliente_id),
    CONSTRAINT fk_kpi_config_cliente FOREIGN KEY (cliente_id) REFERENCES tb_clientes (id) ON DELETE CASCADE
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
-- Tabela única pras 3 automações — é o que alimenta "minhas
-- solicitações" (filtrando por usuario_id) e "Logs e status" (sem
-- filtro, visão do admin). origem=automatico + usuario_id NULL
-- representa um disparo do cronograma, sem usuário por trás.
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
-- Espelha o agendamento automático de Programação semanal e Estadia
-- pra tela "Administração · Cronograma". IMPORTANTE: por enquanto
-- esta tabela é só visualização/controle no UDFlow — ela NÃO
-- reprograma os cron triggers dentro do n8n sozinha. Ativar/pausar
-- aqui precisa de uma rotina própria de sincronização com o n8n
-- (ou, no curto prazo, o n8n consultar `ativo` antes de rodar).
-- ---------------------------------------------------------------------
CREATE TABLE tb_cronograma (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    automacao_id    INT UNSIGNED NOT NULL,
    cliente_id      INT UNSIGNED NOT NULL,
    dia_mes         TINYINT UNSIGNED NOT NULL,
    horario         TIME         NOT NULL,
    ativo           TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cronograma (automacao_id, cliente_id),
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

-- =====================================================================
-- DADOS INICIAIS (seed)
-- =====================================================================

INSERT INTO tb_unidades (codigo, nome) VALUES
    ('MAUA_I', 'Mauá I'),
    ('MAUA_II', 'Mauá II');

-- visivel_para_usuarios = 0 em Programação semanal e Estadia
-- implementa a regra combinada: só admin/super_admin veem essas
-- duas automações por enquanto.
INSERT INTO tb_automacoes (chave, nome, icone, rota, permite_execucao_manual, possui_agendamento, visivel_para_usuarios, ordem_menu) VALUES
    ('kpi', 'KPI · Relatórios Anuais', 'bar-chart', '/automacoes/kpi', 1, 0, 1, 10),
    ('mao_obra_batida', 'Programação semanal', 'clock', '/automacoes/mao-obra-batida', 1, 1, 0, 20),
    ('estadia', 'Estadia', 'truck', '/automacoes/estadia', 1, 1, 0, 30);

-- Observação sobre credenciais (webhook_token, SMTP, DB) —
-- ver decisão já alinhada: infraestrutura fixa (token do webhook,
-- SMTP, chave de sessão) fica no .env, nunca em texto puro no banco.
-- A coluna webhook_token acima existe só para o caso de vocês
-- decidirem, no futuro, rotacionar o token por automação direto
-- pela tela de admin — por ora pode ficar NULL e a aplicação lê do
-- .env mesmo.
