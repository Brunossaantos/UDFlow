<?php

namespace Udflow\rn;

use Udflow\dao\CronogramaDao;
use Udflow\dao\AutomacaoDao;
use Udflow\dao\LogAdminDao;

/**
 * CronogramaRn
 *
 * Importante lembrar: alternar o "ativo" aqui só muda o que fica
 * registrado no UDFlow. Isso ainda NÃO liga/desliga o cron trigger
 * de verdade dentro do n8n - falta a integração descrita no README.
 * Por enquanto essa tela é o "espelho" do que deveria estar rodando.
 */
class CronogramaRn
{
    private CronogramaDao $cronogramaDao;
    private AutomacaoDao $automacaoDao;
    private LogAdminDao $logAdminDao;

    public function __construct()
    {
        $this->cronogramaDao = new CronogramaDao();
        $this->automacaoDao = new AutomacaoDao();
        $this->logAdminDao = new LogAdminDao();
    }

    public function listar(array $filtros = []): array
    {
        return $this->cronogramaDao->listar($filtros);
    }

    public function alternarAtivo(int $id, bool $ativo, int $executorId): void
    {
        $this->cronogramaDao->definirAtivo($id, $ativo);
        $acao = $ativo ? 'cronograma.ativado' : 'cronograma.pausado';
        $this->logAdminDao->registrar($executorId, $acao, 'tb_cronograma', $id, null);
    }

    /**
     * Dispara na hora um item do cronograma. Usa o e-mail do
     * responsável cadastrado no cliente - se não tiver, não dá pra
     * disparar (a pessoa precisa cadastrar o e-mail antes).
     *
     * @return array{sucesso: bool, mensagem: ?string}
     */
    public function executarAgora(int $cronogramaId, int $executorId): array
    {
        $item = $this->cronogramaDao->buscarPorId($cronogramaId);
        if ($item === null) {
            return ['sucesso' => false, 'mensagem' => 'Item de cronograma não encontrado.'];
        }

        if (empty($item['email_responsavel'])) {
            return ['sucesso' => false, 'mensagem' => "Cadastra o e-mail do responsável de {$item['cliente_nome']} antes de executar."];
        }

        $rn = $item['automacao_chave'] === 'kpi' ? new KpiExecucaoRn() : new ExecucaoRn();

        return $rn->executarManual(
            $item['automacao_chave'],
            (int) $item['cliente_id'],
            $item['email_responsavel'],
            $executorId
        );
    }
}
