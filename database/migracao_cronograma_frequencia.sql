-- Banco NOVO (deploy do zero)? Não precisa rodar este arquivo - o
-- tb_cronograma do udflow_schema.sql atual já nasce com frequencia/
-- dias_semana. Isso aqui é só histórico, pra quem tinha um banco de
-- ANTES dessa migração ter sido aplicada.

ALTER TABLE tb_cronograma
    DROP KEY uk_cronograma,
    ADD COLUMN frequencia ENUM('diaria', 'mensal') NOT NULL DEFAULT 'mensal' AFTER cliente_id,
    MODIFY dia_mes TINYINT UNSIGNED NULL,
    ADD UNIQUE KEY uk_cronograma (automacao_id, cliente_id, horario);


    ALTER TABLE tb_cronograma
    ADD COLUMN dias_semana VARCHAR(20) NULL AFTER frequencia;


    carga_estadia_todos_clientes

    INSERT IGNORE INTO tb_cronograma (automacao_id, cliente_id, frequencia, dias_semana, dia_mes, horario)
SELECT
    a.id,
    ca.cliente_id,
    'diaria',
    '1,2,3,4,5',
    NULL,
    h.horario
FROM tb_cliente_automacao ca
JOIN tb_automacoes a ON a.id = ca.automacao_id AND a.chave = 'estadia'
JOIN tb_clientes c ON c.id = ca.cliente_id AND c.ativo = 1
CROSS JOIN (
    SELECT '08:00:00' AS horario
    UNION ALL SELECT '11:00:00'
    UNION ALL SELECT '14:00:00'
    UNION ALL SELECT '17:00:00'
) AS h
WHERE ca.ativo = 1;


INSERT IGNORE INTO tb_cliente_automacao (cliente_id, automacao_id, ativo)
SELECT c.id, a.id, 1
FROM tb_clientes c
CROSS JOIN tb_automacoes a
WHERE c.ativo = 1 AND a.chave = 'estadia';


INSERT IGNORE INTO tb_cronograma (automacao_id, cliente_id, frequencia, dias_semana, dia_mes, horario)
SELECT
    a.id,
    ca.cliente_id,
    'diaria',
    '1,2,3,4,5',
    NULL,
    h.horario
FROM tb_cliente_automacao ca
JOIN tb_automacoes a ON a.id = ca.automacao_id AND a.chave = 'estadia'
JOIN tb_clientes c ON c.id = ca.cliente_id AND c.ativo = 1
CROSS JOIN (
    SELECT '08:00:00' AS horario
    UNION ALL SELECT '11:00:00'
    UNION ALL SELECT '14:00:00'
    UNION ALL SELECT '17:00:00'
) AS h
WHERE ca.ativo = 1;