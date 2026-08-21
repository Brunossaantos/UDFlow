<?php

namespace Udflow\controller;

use Udflow\dao\ExecucaoDao;
use Udflow\dao\AutomacaoDao;
use Udflow\rn\ExecucaoRn;
use Udflow\util\Csrf;
use Udflow\util\Saida;
use Udflow\util\ControleAcesso;

/**
 * AutomacaoController
 *
 * Controller genérico para TODAS as automações (KPI, Programação semanal,
 * Estadia, Relatório de Avarias, etc). A automação é identificada pela
 * rota (?pagina=kpi, ?pagina=estadia, etc) e carregada dinamicamente do banco.
 * 
 * Nenhuma controller específica é mais necessária - tudo é dinâmico!
 */
class AutomacaoController
{
    private string $chave;
    private array $automacao;

    public function __construct()
    {
        $this->automacao = $this->obterAutomacaoViaRota();
        $this->chave = $this->automacao['chave'];
    }

    /**
     * Descobre qual automação é pela rota atual (?pagina=kpi,
     * ?pagina=status-ar, etc), buscando direto em tb_automacoes -
     * nenhuma automação nova precisa de código pra ficar acessível,
     * só cadastrar pela tela de Automações (o "rota" que a pessoa
     * digita lá é exatamente esse slug).
     */
    private function obterAutomacaoViaRota(): array
    {
        $pagina = $_GET['pagina'] ?? '';

        // Remover sufixos de ação (-clientes, -enviar, -status)
        $rotaBase = preg_replace('/-clientes$|-enviar$|-status$/', '', $pagina);

        $automacao = (new AutomacaoDao())->buscarPorRota($rotaBase);

        return $automacao ?? throw new \Exception("Rota '{$pagina}' não mapeada para nenhuma automação ativa.");
    }

    public function tela(): void
    {
        ControleAcesso::exigirPapel($this->chave, 'usuario');

        $execucaoDao = new ExecucaoDao();
        $minhasExecucoes = $execucaoDao->listarDoUsuario(ControleAcesso::usuarioLogadoId(), $this->chave);

        // Usa view genérica (automacao.php) para TODAS as automações!
        require __DIR__ . '/../../views/automacao.php';
    }

    /** Endpoint chamado via fetch() enquanto a pessoa digita o nome do cliente */
    public function buscarClientes(): void
    {
        try {
            ControleAcesso::exigirPapel($this->chave, 'usuario');

            $termo = trim($_GET['termo'] ?? '');

            $execucaoRn = new \Udflow\rn\ExecucaoRn();
            $clientes = $execucaoRn->buscarClientesParaAutocomplete($termo, $this->chave);

            Saida::json(['clientes' => array_map(fn ($c) => [
                'id' => $c->id ?? null,
                'nome' => $c->nomeExibicao ?? null,
                'codigo' => $c->codigoCliente ?? null,
                'unidade' => $c->unidadeNome ?? null,
            ], $clientes)]);
        } catch (\Exception $e) {
            Saida::json(['erro' => $e->getMessage()], 500);
        }
    }

    /** Endpoint chamado via fetch() periodicamente pra atualizar "minhas solicitações" sozinho */
    public function statusExecucoes(): void
    {
        ControleAcesso::exigirPapel($this->chave, 'usuario');

        $execucaoDao = new ExecucaoDao();
        $execucoes = $execucaoDao->listarDoUsuario(ControleAcesso::usuarioLogadoId(), $this->chave);

        Saida::json(['execucoes' => $execucoes]);
    }

    public function enviar(): void
    {
        ControleAcesso::exigirPapel($this->chave, 'usuario');

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada, atualiza a página.'], 419);
        }

        $clienteId = (int) ($_POST['cliente_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');

        if ($clienteId <= 0 || $email === '') {
            Saida::json(['sucesso' => false, 'mensagem' => 'Selecione um cliente e informe o e-mail.'], 422);
        }

        $resultado = (new ExecucaoRn())->executarManual($this->chave, $clienteId, $email, ControleAcesso::usuarioLogadoId());

        Saida::json($resultado, $resultado['sucesso'] ? 200 : 422);
    }
}