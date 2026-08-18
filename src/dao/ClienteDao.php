<?php

namespace Udflow\dao;

use Udflow\config\Conexao;
use Udflow\model\Cliente;
use PDO;

class ClienteDao
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexao::pegar();
    }

    /**
     * Busca pro autocomplete da tela de execução. Só retorna
     * clientes ativos e que estão habilitados pra automação
     * informada (join com tb_cliente_automacao).
     *
     * O "%{$termo}%" nunca é concatenado direto no SQL - ele entra
     * como valor de parâmetro normal, então continua seguro mesmo
     * que o termo tenha aspas ou qualquer caractere esquisito.
     */
    public function buscarPorNomeEAutomacao(string $termo, string $automacaoChave, int $limite = 8): array
    {
        $sql = "SELECT c.*, u.nome AS unidade_nome
                FROM tb_clientes c
                JOIN tb_unidades u ON u.id = c.unidade_id
                JOIN tb_cliente_automacao ca ON ca.cliente_id = c.id
                JOIN tb_automacoes a ON a.id = ca.automacao_id
                WHERE c.ativo = 1
                  AND ca.ativo = 1
                  AND a.chave = :automacao_chave
                  AND c.nome_exibicao LIKE :termo
                ORDER BY c.nome_exibicao
                LIMIT :limite";

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':automacao_chave', $automacaoChave, PDO::PARAM_STR);
        $consulta->bindValue(':termo', '%' . $termo . '%', PDO::PARAM_STR);
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->execute();

        return array_map(fn ($linha) => Cliente::apartirDoBanco($linha), $consulta->fetchAll());
    }

    public function buscarPorId(int $id): ?Cliente
    {
        $sql = 'SELECT c.*, u.nome AS unidade_nome
                FROM tb_clientes c
                JOIN tb_unidades u ON u.id = c.unidade_id
                WHERE c.id = :id
                LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ? Cliente::apartirDoBanco($linha) : null;
    }

    public function listarTodos(?int $unidadeId = null): array
    {
        $sql = 'SELECT c.*, u.nome AS unidade_nome
                FROM tb_clientes c
                JOIN tb_unidades u ON u.id = c.unidade_id';

        if ($unidadeId !== null) {
            $sql .= ' WHERE c.unidade_id = :unidade_id';
        }

        $sql .= ' ORDER BY c.nome_exibicao';

        $consulta = $this->pdo->prepare($sql);
        if ($unidadeId !== null) {
            $consulta->bindValue(':unidade_id', $unidadeId, PDO::PARAM_INT);
        }
        $consulta->execute();

        return array_map(fn ($linha) => Cliente::apartirDoBanco($linha), $consulta->fetchAll());
    }

    public function existeCodigoOuCnpj(string $codigo, string $cnpj, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM tb_clientes WHERE (codigo_cliente = :codigo OR cnpj = :cnpj)';
        if ($ignorarId !== null) {
            $sql .= ' AND id != :ignorar_id';
        }

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->bindValue(':cnpj', $cnpj, PDO::PARAM_STR);
        if ($ignorarId !== null) {
            $consulta->bindValue(':ignorar_id', $ignorarId, PDO::PARAM_INT);
        }
        $consulta->execute();

        return ((int) $consulta->fetchColumn()) > 0;
    }

        public function existeCodigoTalent(int $unidadeId, string $codigoTalent, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM tb_clientes WHERE unidade_id = :unidade_id AND codigo_talent = :codigo_talent';
        if ($ignorarId !== null) {
            $sql .= ' AND id != :ignorar_id';
        }

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':unidade_id', $unidadeId, PDO::PARAM_INT);
        $consulta->bindValue(':codigo_talent', $codigoTalent, PDO::PARAM_STR);
        if ($ignorarId !== null) {
            $consulta->bindValue(':ignorar_id', $ignorarId, PDO::PARAM_INT);
        }
        $consulta->execute();

        return ((int) $consulta->fetchColumn()) > 0;
    }

    public function criar(
        int $unidadeId,
        string $codigo,
        ?string $codigoTalent,
        string $razaoSocial,
        string $nomeExibicao,
        string $cnpj,
        ?string $emailResponsavel
    ): int {
        $sql = 'INSERT INTO tb_clientes (unidade_id, codigo_cliente, codigo_talent, razao_social, nome_exibicao, cnpj, email_responsavel)
                VALUES (:unidade_id, :codigo, :codigo_talent, :razao_social, :nome_exibicao, :cnpj, :email_responsavel)';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':unidade_id', $unidadeId, PDO::PARAM_INT);
        $comando->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $comando->bindValue(':codigo_talent', $codigoTalent, $codigoTalent === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':razao_social', $razaoSocial, PDO::PARAM_STR);
        $comando->bindValue(':nome_exibicao', $nomeExibicao, PDO::PARAM_STR);
        $comando->bindValue(':cnpj', $cnpj, PDO::PARAM_STR);
        $comando->bindValue(':email_responsavel', $emailResponsavel, $emailResponsavel === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(
        int $id,
        int $unidadeId,
        string $codigo,
        ?string $codigoTalent,
        string $razaoSocial,
        string $nomeExibicao,
        string $cnpj,
        ?string $emailResponsavel,
        bool $ativo
    ): void {
        $sql = 'UPDATE tb_clientes
                SET unidade_id = :unidade_id,
                    codigo_cliente = :codigo,
                    codigo_talent = :codigo_talent,
                    razao_social = :razao_social,
                    nome_exibicao = :nome_exibicao,
                    cnpj = :cnpj,
                    email_responsavel = :email_responsavel,
                    ativo = :ativo
                WHERE id = :id';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':unidade_id', $unidadeId, PDO::PARAM_INT);
        $comando->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $comando->bindValue(':codigo_talent', $codigoTalent, $codigoTalent === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':razao_social', $razaoSocial, PDO::PARAM_STR);
        $comando->bindValue(':nome_exibicao', $nomeExibicao, PDO::PARAM_STR);
        $comando->bindValue(':cnpj', $cnpj, PDO::PARAM_STR);
        $comando->bindValue(':email_responsavel', $emailResponsavel, $emailResponsavel === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':ativo', $ativo ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':id', $id, PDO::PARAM_INT);
        $comando->execute();
    }

    public function buscarKpiConfig(int $clienteId): ?array
    {
        $sql = 'SELECT * FROM tb_clientes_kpi_config WHERE cliente_id = :cliente_id LIMIT 1';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $consulta->execute();

        $linha = $consulta->fetch();

        return $linha ?: null;
    }

    public function salvarKpiConfig(int $clienteId, ?string $logoUrl, ?string $corPrimaria, ?string $corSecundaria): void
    {
        // "upsert": se já existe config pra esse cliente, atualiza; senão, cria.
        // ON DUPLICATE KEY funciona aqui porque cliente_id é UNIQUE na tabela.
        $sql = 'INSERT INTO tb_clientes_kpi_config (cliente_id, logo_url, cor_primaria, cor_secundaria)
                VALUES (:cliente_id, :logo_url, :cor_primaria, :cor_secundaria)
                ON DUPLICATE KEY UPDATE
                    logo_url = :logo_url2,
                    cor_primaria = :cor_primaria2,
                    cor_secundaria = :cor_secundaria2';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $comando->bindValue(':logo_url', $logoUrl, $logoUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':cor_primaria', $corPrimaria, $corPrimaria === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':cor_secundaria', $corSecundaria, $corSecundaria === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':logo_url2', $logoUrl, $logoUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':cor_primaria2', $corPrimaria, $corPrimaria === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->bindValue(':cor_secundaria2', $corSecundaria, $corSecundaria === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $comando->execute();
    }

    /** Quais automações esse cliente participa hoje, e se está ativo em cada uma */
    public function automacoesDoCliente(int $clienteId): array
    {
        $sql = 'SELECT automacao_id, ativo FROM tb_cliente_automacao WHERE cliente_id = :cliente_id';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $consulta->execute();

        // vira um mapa automacao_id => ativo(bool), mais fácil de consultar na view
        $mapa = [];
        foreach ($consulta->fetchAll() as $linha) {
            $mapa[(int) $linha['automacao_id']] = (bool) $linha['ativo'];
        }

        return $mapa;
    }

    public function definirAutomacao(int $clienteId, int $automacaoId, bool $ativo): void
    {
        $sql = 'INSERT INTO tb_cliente_automacao (cliente_id, automacao_id, ativo)
                VALUES (:cliente_id, :automacao_id, :ativo)
                ON DUPLICATE KEY UPDATE ativo = :ativo2';

        $comando = $this->pdo->prepare($sql);
        $comando->bindValue(':cliente_id', $clienteId, PDO::PARAM_INT);
        $comando->bindValue(':automacao_id', $automacaoId, PDO::PARAM_INT);
        $comando->bindValue(':ativo', $ativo ? 1 : 0, PDO::PARAM_INT);
        $comando->bindValue(':ativo2', $ativo ? 1 : 0, PDO::PARAM_INT);
        $comando->execute();
    }
}
