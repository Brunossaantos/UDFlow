<?php

namespace Udflow\controller;

use Udflow\dao\ExecucaoDao;
use Udflow\rn\ExecucaoRn;
use Udflow\util\Csrf;
use Udflow\util\Saida;
use Udflow\util\ControleAcesso;

/**
 * AutomacaoController
 *
 * Base comum das telas de execução (KPI, Programação semanal,
 * Estadia). Cada uma só precisa dizer qual é a sua "chave" (o nome
 * dela em tb_automacoes) e qual Rn usar - o resto do fluxo
 * (autocomplete, disparo manual, listagem de "minhas solicitações")
 * é sempre o mesmo, então fica escrito uma vez só aqui.
 */
abstract class AutomacaoController
{
    abstract protected function chave(): string;
    abstract protected function rn(): ExecucaoRn;
    abstract protected function view(): string;

    public function tela(): void
    {
        ControleAcesso::exigirPapel($this->chave(), 'usuario');

        $execucaoDao = new ExecucaoDao();
        $minhasExecucoes = $execucaoDao->listarDoUsuario(ControleAcesso::usuarioLogadoId(), $this->chave());

        require __DIR__ . '/../../views/' . $this->view();
    }

    /** Endpoint chamado via fetch() enquanto a pessoa digita o nome do cliente */
    public function buscarClientes(): void
    {
        ControleAcesso::exigirPapel($this->chave(), 'usuario');

        $termo = trim($_GET['termo'] ?? '');
        $clientes = $this->rn()->buscarClientesParaAutocomplete($termo, $this->chave());

        Saida::json(['clientes' => array_map(fn ($c) => [
            'id' => $c->id,
            'nome' => $c->nomeExibicao,
            'codigo' => $c->codigoCliente,
            'unidade' => $c->unidadeNome,
        ], $clientes)]);
    }

    public function enviar(): void
    {
        ControleAcesso::exigirPapel($this->chave(), 'usuario');

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada, atualiza a página.'], 419);
        }

        $clienteId = (int) ($_POST['cliente_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');

        if ($clienteId <= 0 || $email === '') {
            Saida::json(['sucesso' => false, 'mensagem' => 'Selecione um cliente e informe o e-mail.'], 422);
        }

        $resultado = $this->rn()->executarManual($this->chave(), $clienteId, $email, ControleAcesso::usuarioLogadoId());

        Saida::json($resultado, $resultado['sucesso'] ? 200 : 422);
    }
}
