<?php

namespace Udflow\model;

/**
 * Cliente
 *
 * Representa uma linha de tb_clientes já com o nome da unidade
 * junto (join simples), do jeito que a tela de busca/autocomplete
 * precisa.
 */
class Cliente
{
    public ?int $id = null;
    public string $codigoCliente = '';
    public ?string $codigoTalent = null;
    public string $razaoSocial = '';
    public string $nomeExibicao = '';
    public string $cnpj = '';
    public ?string $emailResponsavel = null;
    public int $unidadeId = 0;
    public string $unidadeNome = '';
    public bool $ativo = true;

    public static function apartirDoBanco(array $linha): self
    {
        $cliente = new self();
        $cliente->id = (int) $linha['id'];
        $cliente->codigoCliente = $linha['codigo_cliente'];
        $cliente->codigoTalent = $linha['codigo_talent'] ?? null;
        $cliente->razaoSocial = $linha['razao_social'];
        $cliente->nomeExibicao = $linha['nome_exibicao'];
        $cliente->cnpj = $linha['cnpj'];
        $cliente->emailResponsavel = $linha['email_responsavel'] ?? null;
        $cliente->unidadeId = (int) $linha['unidade_id'];
        $cliente->unidadeNome = $linha['unidade_nome'] ?? '';
        $cliente->ativo = (bool) $linha['ativo'];

        return $cliente;
    }
}
