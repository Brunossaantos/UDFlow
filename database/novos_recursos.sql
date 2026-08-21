-- =====================================================================
-- HISTÓRICO DE MIGRAÇÕES - já incorporadas no udflow_schema.sql
--
-- Banco NOVO (deploy do zero)? Não precisa rodar nada deste arquivo -
-- todo bloco abaixo já está refletido no udflow_schema.sql atual.
-- Este arquivo só serve pra atualizar um banco que já existia ANTES
-- de cada mudança (ex: o banco de produção antes de vocês subirem a
-- versão que criou o Log de sistema). Fica como registro histórico.
-- =====================================================================

-- ---------------------------------------------------------------------
-- Log de sistema
-- Guarda erros/exceptions/fatais capturados automaticamente pelos
-- manipuladores globais (config/bootstrap.php e config/bootstrap-cli.php)
-- e falhas de negócio relevantes (webhook, e-mail, chat). Tela exclusiva
-- do super_admin: index.php?pagina=admin-logs-sistema
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
-- Renomeia tb_clientes_kpi_config -> tb_clientes_config
-- A tabela de logo/cores deixou de ser exclusiva do KPI (qualquer
-- automação pode usar via map_from_banco na configuração de payload),
-- então o nome "kpi" no meio não fazia mais sentido.
--
-- IMPORTANTE: rode esse bloco só UMA VEZ, depois que o bloco de
-- tb_logs_sistema acima já tiver sido aplicado nesse banco.
-- ---------------------------------------------------------------------
RENAME TABLE tb_clientes_kpi_config TO tb_clientes_config;

ALTER TABLE tb_clientes_config
    DROP FOREIGN KEY fk_kpi_config_cliente,
    DROP KEY uk_kpi_config_cliente,
    ADD UNIQUE KEY uk_clientes_config_cliente (cliente_id),
    ADD CONSTRAINT fk_clientes_config_cliente FOREIGN KEY (cliente_id) REFERENCES tb_clientes (id) ON DELETE CASCADE;

-- Atualiza as regras de payload já salvas (Configurar Automações) que
-- apontavam pro nome antigo da tabela.
UPDATE tb_automacao_payload_regras
SET configuracao = REPLACE(configuracao, 'tb_clientes_kpi_config', 'tb_clientes_config')
WHERE configuracao LIKE '%tb_clientes_kpi_config%';

-- ---------------------------------------------------------------------
-- Roteamento dinâmico de automações
-- Antes, só KPI/Estadia/Programação Semanal/Relatório de Avarias
-- funcionavam de verdade - qualquer automação nova cadastrada pela
-- tela de Automações dava "Página não encontrada", porque as rotas
-- eram fixas em config/rotas.php e o AutomacaoController tinha um
-- mapeamento de chave hardcoded. Agora o index.php resolve a rota na
-- hora, buscando direto em tb_automacoes.rota - qualquer automação
-- nova já funciona sem precisar mexer em código.
--
-- Isso exige que a coluna "rota" esteja sempre em
-- "/automacoes/{slug-com-hifen}" - a linha do Relatório de Avarias
-- foi cadastrada com underscore (inconsistente com a própria rota
-- que já funcionava, "relatorio-avarias"). Corrige aqui.
-- ---------------------------------------------------------------------
UPDATE tb_automacoes SET rota = '/automacoes/relatorio-avarias' WHERE chave = 'relatorio_avarias';
