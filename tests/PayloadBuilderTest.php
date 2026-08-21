<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Udflow\util\PayloadBuilder;
use Udflow\config\Conexao;
use PDO;

/**
 * PayloadBuilderTest
 *
 * Cobre o bug corrigido em 2026-08-20: regra map_from_banco resolvendo
 * contra o banco de verdade (não usava o parâmetro certo na query) e
 * fluxo básico de campo obrigatório/conversão de tipo. Roda contra o
 * banco local de dev configurado no .env - cada teste abre uma
 * transação e dá rollback no tearDown, então nada fica gravado.
 *
 * Usa o cliente "Monfiza" (id 63) já existente como fonte de leitura
 * (nunca escrito por este teste) pra validar o map_from_banco.
 */
class PayloadBuilderTest extends TestCase
{
    private const CLIENTE_ID_MONFIZA = 63;

    private PDO $pdo;
    private int $automacaoId;

    protected function setUp(): void
    {
        $this->pdo = Conexao::pegar();
        $this->pdo->beginTransaction();

        $stmt = $this->pdo->prepare(
            "INSERT INTO tb_automacoes (chave, nome, rota, webhook_url)
             VALUES ('teste_payload_builder', 'Teste PayloadBuilder', '/automacoes/teste-payload-builder', 'https://exemplo.invalido/webhook')"
        );
        $stmt->execute();
        $this->automacaoId = (int) $this->pdo->lastInsertId();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function criarCampo(string $nomeCampo, string $tipoDado, bool $obrigatorio = false, ?string $valorPadrao = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tb_automacao_payload_campos (automacao_id, nome_campo, tipo_dado, obrigatorio, valor_padrao, posicao)
             VALUES (:automacao_id, :nome_campo, :tipo_dado, :obrigatorio, :valor_padrao, 1)'
        );
        $stmt->execute([
            ':automacao_id' => $this->automacaoId,
            ':nome_campo' => $nomeCampo,
            ':tipo_dado' => $tipoDado,
            ':obrigatorio' => $obrigatorio ? 1 : 0,
            ':valor_padrao' => $valorPadrao,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function criarRegraMapFromBanco(int $campoId, string $tabela, string $coluna, string $condicao): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tb_automacao_payload_regras (automacao_id, campo_id, tipo_regra, configuracao, ativo, ordem_execucao)
             VALUES (:automacao_id, :campo_id, 'map_from_banco', :configuracao, 1, 1)"
        );
        $stmt->execute([
            ':automacao_id' => $this->automacaoId,
            ':campo_id' => $campoId,
            ':configuracao' => json_encode(['tabela' => $tabela, 'coluna' => $coluna, 'condicao' => $condicao]),
        ]);
    }

    public function testResolveCamposViaMapFromBanco(): void
    {
        $this->criarCampo('execucaoId', 'integer', true);
        $this->criarCampo('emailDestino', 'email', true);

        $campoCodigo = $this->criarCampo('codigo_cliente', 'string');
        $this->criarRegraMapFromBanco($campoCodigo, 'tb_clientes', 'codigo_cliente', 'id = :clienteId');

        $campoCor = $this->criarCampo('cor_secundaria', 'string');
        $this->criarRegraMapFromBanco($campoCor, 'tb_clientes_config', 'cor_secundaria', 'cliente_id = :clienteId');

        $resultado = (new PayloadBuilder())->construir($this->automacaoId, self::CLIENTE_ID_MONFIZA, 'teste@udlog.com.br', 'MANUAL', 999);

        $this->assertTrue($resultado['sucesso'], $resultado['mensagem'] ?? '');
        $this->assertSame('MONFIZA', $resultado['payload']['codigo_cliente']);
        $this->assertSame('#EE8904', $resultado['payload']['cor_secundaria']);
        $this->assertSame(999, $resultado['payload']['execucaoId']);
        $this->assertSame('teste@udlog.com.br', $resultado['payload']['emailDestino']);
    }

    public function testCampoObrigatorioSemValorFalha(): void
    {
        $this->criarCampo('execucaoId', 'integer', true);
        $this->criarCampo('campo_sem_valor_nenhum', 'string', true);

        $resultado = (new PayloadBuilder())->construir($this->automacaoId, self::CLIENTE_ID_MONFIZA, 'teste@udlog.com.br', 'MANUAL', 1);

        $this->assertFalse($resultado['sucesso']);
    }

    public function testConverteValorPadraoParaInteger(): void
    {
        $this->criarCampo('quantidade', 'integer', false, '42');

        $resultado = (new PayloadBuilder())->construir($this->automacaoId, self::CLIENTE_ID_MONFIZA, 'teste@udlog.com.br', 'MANUAL', 1);

        $this->assertTrue($resultado['sucesso']);
        $this->assertSame(42, $resultado['payload']['quantidade']);
        $this->assertIsInt($resultado['payload']['quantidade']);
    }

    public function testCampoNaoObrigatorioSemValorNaoQuebraOPayload(): void
    {
        $this->criarCampo('execucaoId', 'integer', true);
        $this->criarCampo('campo_opcional_sem_valor', 'string', false);

        $resultado = (new PayloadBuilder())->construir($this->automacaoId, self::CLIENTE_ID_MONFIZA, 'teste@udlog.com.br', 'MANUAL', 1);

        $this->assertTrue($resultado['sucesso']);
        $this->assertArrayNotHasKey('campo_opcional_sem_valor', $resultado['payload']);
    }
}
