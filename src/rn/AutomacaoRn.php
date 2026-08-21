<?php

namespace Udflow\rn;

use Udflow\dao\AutomacaoDao;
use Udflow\dao\LogAdminDao;

class AutomacaoRn
{
    private AutomacaoDao $automacaoDao;
    private LogAdminDao $logAdminDao;

    public function __construct()
    {
        $this->automacaoDao = new AutomacaoDao();
        $this->logAdminDao = new LogAdminDao();
    }

    public function listar(): array
    {
        return $this->automacaoDao->listarTodas();
    }

    public function alternarVisibilidade(int $id, bool $visivel, int $executorId): void
    {
        $this->automacaoDao->atualizarVisibilidade($id, $visivel);
        $acao = $visivel ? 'automacao.liberada_para_usuarios' : 'automacao.restrita_a_admins';
        $this->logAdminDao->registrar($executorId, $acao, 'tb_automacoes', $id, null);
    }

    public function criar(
        string $nome,
        string $chave,
        string $rota,
        ?string $iconSvg = null,
        string $webhookUrl = '',
        string $webhookMetodo = 'POST',
        int $posicao = 1,
        bool $possuiAgendamento = false,
        bool $visivelParaUsuarios = true,
        int $executorId = 0
    ): array
    {
        // Validar chave única
        $automacaoExistente = $this->automacaoDao->buscarPorChave($chave);
        if ($automacaoExistente) {
            return ['sucesso' => false, 'mensagem' => 'Já existe uma automação com essa chave.'];
        }

        // "rota" chega como slug puro (ex: "status-ar") - o prefixo
        // /automacoes/ é sempre o mesmo, então a gente completa aqui
        // em vez de pedir pra pessoa digitar isso na tela.
        $rotaCompleta = '/automacoes/' . $rota;

        $rotaExistente = $this->automacaoDao->buscarPorRota($rota);
        if ($rotaExistente) {
            return ['sucesso' => false, 'mensagem' => 'Já existe uma automação com essa rota.'];
        }

        // Inserir no banco
        $id = $this->automacaoDao->criar(
            nome: $nome,
            chave: $chave,
            rota: $rotaCompleta,
            iconSvg: $iconSvg,
            webhookUrl: $webhookUrl,
            webhookMetodo: $webhookMetodo,
            posicao: $posicao,
            possuiAgendamento: $possuiAgendamento,
            visivelParaUsuarios: $visivelParaUsuarios
        );

        if (!$id) {
            return ['sucesso' => false, 'mensagem' => 'Erro ao inserir no banco.'];
        }

        // Registrar log
        $this->logAdminDao->registrar($executorId, 'automacao.criada', 'tb_automacoes', $id, null);

        return ['sucesso' => true, 'id' => $id];
    }

    public function atualizar(
        int $id,
        string $nome,
        ?string $iconSvg = null,
        string $webhookUrl = '',
        string $webhookMetodo = 'POST',
        int $posicao = 1,
        bool $possuiAgendamento = false,
        bool $visivelParaUsuarios = true,
        bool $ativo = true,
        int $executorId = 0
    ): array
    {
        // Validar que existe
        $automacao = $this->automacaoDao->buscarPorId($id);
        if (!$automacao) {
            return ['sucesso' => false, 'mensagem' => 'Automação não encontrada.'];
        }

        // Atualizar no banco
        $this->automacaoDao->atualizar(
            id: $id,
            nome: $nome,
            iconSvg: $iconSvg,
            webhookUrl: $webhookUrl,
            webhookMetodo: $webhookMetodo,
            posicao: $posicao,
            possuiAgendamento: $possuiAgendamento,
            visivelParaUsuarios: $visivelParaUsuarios,
            ativo: $ativo
        );

        // Registrar log
        $this->logAdminDao->registrar($executorId, 'automacao.atualizada', 'tb_automacoes', $id, null);

        return ['sucesso' => true];
    }
}