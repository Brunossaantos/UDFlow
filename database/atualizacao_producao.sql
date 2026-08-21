-- =====================================================================
-- UDFLOW — ATUALIZAÇÃO PARA PRODUÇÃO
--
-- Script único, seguro de rodar mais de uma vez (idempotente): tudo
-- aqui confere antes se já existe e pula sem duplicar/quebrar caso já
-- tenha sido aplicado. Traz qualquer banco que já tinha o UDFlow
-- instalado (mesmo que só parcialmente atualizado) pra estrutura
-- atual, igual ao que está em database/udflow_schema.sql.
--
-- Se o banco for NOVO (nunca rodou o UDFlow antes), não precisa
-- rodar este arquivo - só rode database/udflow_schema.sql, que já
-- nasce completo.
--
-- Requer MariaDB 10.0.2+ (ADD COLUMN/INDEX IF NOT EXISTS).
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. tb_automacoes - colunas novas do motor de payload configurável,
--    ícone customizado e ordenação (posicao).
-- ---------------------------------------------------------------------
ALTER TABLE tb_automacoes
    ADD COLUMN IF NOT EXISTS webhook_metodo         ENUM('GET','POST','PUT','PATCH','DELETE') DEFAULT 'POST' AFTER atualizado_em,
    ADD COLUMN IF NOT EXISTS posicao                 INT          DEFAULT 10 COMMENT 'Ordem de exibição (10,20,30...)' AFTER webhook_metodo,
    ADD COLUMN IF NOT EXISTS icon_svg                LONGTEXT     NULL COMMENT 'Conteúdo interno de um <svg viewBox="0 0 24 24"> (path/circle/rect)' AFTER posicao,
    ADD COLUMN IF NOT EXISTS timeout_ms              INT          DEFAULT 5000 COMMENT 'Timeout do webhook em milissegundos' AFTER icon_svg,
    ADD COLUMN IF NOT EXISTS label_botao             VARCHAR(100) DEFAULT 'Executar agora' AFTER timeout_ms,
    ADD COLUMN IF NOT EXISTS aviso_email_udlog       TINYINT(1)   DEFAULT 0 COMMENT 'Se 1, restringe e-mail de destino a @udlog' AFTER label_botao,
    ADD COLUMN IF NOT EXISTS aviso_proximo_disparo   TEXT         NULL COMMENT 'Mensagem exibida na tela sobre o próximo disparo automático' AFTER aviso_email_udlog,
    ADD COLUMN IF NOT EXISTS mostrar_coluna_origem   TINYINT(1)   DEFAULT 0 COMMENT 'Se 1, mostra a coluna Manual/Automático em "minhas solicitações"' AFTER aviso_proximo_disparo,
    ADD INDEX IF NOT EXISTS idx_posicao (posicao),
    ADD INDEX IF NOT EXISTS idx_ativo (ativo);

-- Se a coluna posicao acabou de ser criada agora (valor default 10
-- pra todas as linhas), numera 10/20/30/40... pela ordem de criação
-- (id). NÃO usa ordem_menu aqui de propósito - é a mesma coluna
-- quebrada/desatualizada que causava a bagunça na sidebar antes desse
-- ajuste, não faz sentido herdar o problema pra dentro de posicao.
SET @ja_numerado = (SELECT COUNT(*) FROM tb_automacoes WHERE posicao <> 10);
SET @total_automacoes = (SELECT COUNT(*) FROM tb_automacoes);

SET @linha := 0;
SET @sql_numerar = IF(
    @ja_numerado = 0 AND @total_automacoes > 1,
    'UPDATE tb_automacoes SET posicao = (@linha := @linha + 1) * 10 ORDER BY id',
    'SELECT 1'
);
PREPARE stmt_numerar FROM @sql_numerar;
EXECUTE stmt_numerar;
DEALLOCATE PREPARE stmt_numerar;

-- Corrige a rota do Relatório de Avarias se ainda estiver com
-- underscore (inconsistente com a rota que já funcionava de verdade,
-- "/automacoes/relatorio-avarias").
UPDATE tb_automacoes SET rota = '/automacoes/relatorio-avarias'
WHERE chave = 'relatorio_avarias' AND rota <> '/automacoes/relatorio-avarias';

-- ---------------------------------------------------------------------
-- 2. tb_clientes - código Talent (exigido só pelo Relatório de
--    Avarias) e ajuste de unicidade: mesmo CNPJ pode existir em mais
--    de uma unidade (ex: um cliente com filial em Mauá I e Mauá II).
-- ---------------------------------------------------------------------
ALTER TABLE tb_clientes
    ADD COLUMN IF NOT EXISTS codigo_talent VARCHAR(30) NULL AFTER codigo_cliente;

SET @tem_uk_cnpj_antiga = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'tb_clientes' AND index_name = 'uk_clientes_cnpj'
);
SET @sql_drop_uk_cnpj = IF(@tem_uk_cnpj_antiga > 0, 'ALTER TABLE tb_clientes DROP KEY uk_clientes_cnpj', 'SELECT 1');
PREPARE stmt_drop_uk_cnpj FROM @sql_drop_uk_cnpj;
EXECUTE stmt_drop_uk_cnpj;
DEALLOCATE PREPARE stmt_drop_uk_cnpj;

ALTER TABLE tb_clientes
    ADD UNIQUE KEY IF NOT EXISTS uk_clientes_unidade_cnpj (unidade_id, cnpj),
    ADD UNIQUE KEY IF NOT EXISTS uk_unidade_codigo_talent (unidade_id, codigo_talent);

-- ---------------------------------------------------------------------
-- 3. Configuração de payload/webhook por automação (tela "Configurar
--    Automações") - 6 tabelas novas, ver src/util/PayloadBuilder.php
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tb_automacao_payload_campos (
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

CREATE TABLE IF NOT EXISTS tb_automacao_payload_regras (
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

CREATE TABLE IF NOT EXISTS tb_automacao_webhook_headers (
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

CREATE TABLE IF NOT EXISTS tb_automacao_webhook_log (
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

CREATE TABLE IF NOT EXISTS tb_automacao_config_historico (
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

CREATE TABLE IF NOT EXISTS tb_automacao_payload_testes (
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

-- ---------------------------------------------------------------------
-- 4. tb_cronograma - frequência diária/mensal + dias da semana
--    (idêntico ao database/migracao_cronograma_frequencia.sql, só que
--    idempotente)
-- ---------------------------------------------------------------------
ALTER TABLE tb_cronograma
    ADD COLUMN IF NOT EXISTS frequencia ENUM('diaria', 'mensal') NOT NULL DEFAULT 'mensal' AFTER cliente_id,
    ADD COLUMN IF NOT EXISTS dias_semana VARCHAR(20) NULL AFTER frequencia,
    MODIFY dia_mes TINYINT UNSIGNED NULL;

SET @tem_uk_antiga = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'tb_cronograma'
      AND index_name = 'uk_cronograma' AND column_name = 'horario'
);
SET @sql_uk_cronograma = IF(
    @tem_uk_antiga = 0,
    'ALTER TABLE tb_cronograma DROP KEY IF EXISTS uk_cronograma, ADD UNIQUE KEY uk_cronograma (automacao_id, cliente_id, horario)',
    'SELECT 1'
);
PREPARE stmt_uk_cronograma FROM @sql_uk_cronograma;
EXECUTE stmt_uk_cronograma;
DEALLOCATE PREPARE stmt_uk_cronograma;

-- ---------------------------------------------------------------------
-- 5. tb_logs_sistema (log de sistema - super_admin)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tb_logs_sistema (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nivel       VARCHAR(20)  NOT NULL,
    mensagem    TEXT         NOT NULL,
    arquivo     VARCHAR(255) NULL,
    linha       INT          NULL,
    contexto    TEXT         NULL,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_logs_sistema_nivel (nivel),
    KEY idx_logs_sistema_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. tb_clientes_kpi_config -> tb_clientes_config (se ainda não foi
--    renomeada)
-- ---------------------------------------------------------------------
SET @tem_tabela_antiga = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'tb_clientes_kpi_config'
);
SET @sql_rename = IF(@tem_tabela_antiga > 0, 'RENAME TABLE tb_clientes_kpi_config TO tb_clientes_config', 'SELECT 1');
PREPARE stmt_rename FROM @sql_rename;
EXECUTE stmt_rename;
DEALLOCATE PREPARE stmt_rename;

CREATE TABLE IF NOT EXISTS tb_clientes_config (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT UNSIGNED NOT NULL,
    logo_url        VARCHAR(255) NULL,
    cor_primaria    CHAR(7)      NULL,
    cor_secundaria  CHAR(7)      NULL,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_clientes_config_cliente (cliente_id),
    CONSTRAINT fk_clientes_config_cliente FOREIGN KEY (cliente_id) REFERENCES tb_clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @tem_uk_kpi_config = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'tb_clientes_config' AND index_name = 'uk_kpi_config_cliente'
);
SET @sql_fix_constraints = IF(
    @tem_uk_kpi_config > 0,
    'ALTER TABLE tb_clientes_config
        DROP FOREIGN KEY fk_kpi_config_cliente,
        DROP KEY uk_kpi_config_cliente,
        ADD UNIQUE KEY uk_clientes_config_cliente (cliente_id),
        ADD CONSTRAINT fk_clientes_config_cliente FOREIGN KEY (cliente_id) REFERENCES tb_clientes (id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt_fix_constraints FROM @sql_fix_constraints;
EXECUTE stmt_fix_constraints;
DEALLOCATE PREPARE stmt_fix_constraints;

UPDATE tb_automacao_payload_regras
SET configuracao = REPLACE(configuracao, 'tb_clientes_kpi_config', 'tb_clientes_config')
WHERE configuracao LIKE '%tb_clientes_kpi_config%';

-- ---------------------------------------------------------------------
-- 7. Views - CREATE OR REPLACE já é idempotente por natureza
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW vw_permissoes_usuario AS
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

CREATE OR REPLACE VIEW vw_execucoes_detalhadas AS
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

CREATE OR REPLACE VIEW vw_cronograma_ativo AS
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

CREATE OR REPLACE VIEW v_automacao_completa AS
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

CREATE OR REPLACE VIEW v_automacao_ultima_execucao AS
SELECT
    automacao_id,
    MAX(criado_em) AS ultima_execucao,
    COUNT(*) AS total_execucoes,
    SUM(CASE WHEN http_status = 200 THEN 1 ELSE 0 END) AS execucoes_sucesso,
    SUM(CASE WHEN http_status <> 200 THEN 1 ELSE 0 END) AS execucoes_erro,
    AVG(tempo_execucao_ms) AS tempo_medio_ms
FROM tb_automacao_webhook_log
GROUP BY automacao_id;
