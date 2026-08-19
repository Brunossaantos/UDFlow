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

    public function contar(array $filtros = []): int
    {
        return $this->cronogramaDao->contar($filtros);
    }

    public function alternarAtivo(int $id, bool $ativo, int $executorId): void
    {
        $this->cronogramaDao->definirAtivo($id, $ativo);
        $acao = $ativo ? 'cronograma.ativado' : 'cronograma.pausado';
        $this->logAdminDao->registrar($executorId, $acao, 'tb_cronograma', $id, null);
    }

    /**
     * @return array{sucesso: bool, mensagem: ?string, id: ?int}
     */
    public function criar(int $automacaoId, int $clienteId, string $frequencia, ?string $diasSemana, ?int $diaMes, string $horario, int $executorId): array
    {
        if (!in_array($frequencia, ['diaria', 'mensal'], true)) {
            return ['sucesso' => false, 'mensagem' => 'Escolhe a frequência: diária ou mensal.', 'id' => null];
        }

        if ($frequencia === 'mensal') {
            if ($diaMes === null || $diaMes < 1 || $diaMes > 31) {
                return ['sucesso' => false, 'mensagem' => 'Informa um dia do mês válido (1 a 31).', 'id' => null];
            }
        } else {
            $diaMes = null;
        }

        $diasSemana = $this->normalizarDiasSemana($diasSemana);
        if ($diasSemana === false) {
            return ['sucesso' => false, 'mensagem' => 'Dias da semana inválidos.', 'id' => null];
        }

        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)/', $horario)) {
            return ['sucesso' => false, 'mensagem' => 'Informa um horário válido (HH:MM).', 'id' => null];
        }
        $horario = substr($horario, 0, 5) . ':00';

        if ($this->cronogramaDao->existeHorario($automacaoId, $clienteId, $horario)) {
            return ['sucesso' => false, 'mensagem' => 'Esse cliente já tem um agendamento nessa automação nesse mesmo horário.', 'id' => null];
        }

        $id = $this->cronogramaDao->criar($automacaoId, $clienteId, $frequencia, $diasSemana, $diaMes, $horario);
        $this->logAdminDao->registrar($executorId, 'cronograma.criado', 'tb_cronograma', $id, null);

        return ['sucesso' => true, 'mensagem' => null, 'id' => $id];
    }

    private function normalizarDiasSemana(?string $diasSemana): string|false|null
    {
        if ($diasSemana === null || trim($diasSemana) === '') {
            return null;
        }

        $numeros = array_filter(array_map('trim', explode(',', $diasSemana)), fn($v) => $v !== '');
        foreach ($numeros as $numero) {
            if (!ctype_digit($numero) || (int) $numero < 1 || (int) $numero > 7) {
                return false;
            }
        }

        return empty($numeros) ? null : implode(',', $numeros);
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

    public function buscarPorId(int $id): array|null
    {
        return $this->cronogramaDao->buscarPorId($id);
    }

    /**
     * Atualizar horário do cronograma
     * 
     * @return array{sucesso: bool, mensagem: ?string}
     */
    public function atualizar(
        int $id,
        string $frequencia,
        string $horario,
        ?string $diasSemana = null,
        ?int $diaMes = null,
        int $executorId = 0
    ): array
    {
        $item = $this->cronogramaDao->buscarPorId($id);
        if ($item === null) {
            return ['sucesso' => false, 'mensagem' => 'Item de cronograma não encontrado.'];
        }

        // Validar frequência
        if (!in_array($frequencia, ['diaria', 'mensal'])) {
            return ['sucesso' => false, 'mensagem' => 'Frequência inválida.'];
        }

        // Validar horário (formato HH:MM:SS ou HH:MM)
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horario)) {
            return ['sucesso' => false, 'mensagem' => 'Horário em formato inválido.'];
        }

        // Se mensal, validar dia do mês
        if ($frequencia === 'mensal' && ($diaMes === null || $diaMes <= 0 || $diaMes > 31)) {
            return ['sucesso' => false, 'mensagem' => 'Dia do mês deve estar entre 1 e 31.'];
        }

        // Se diária, dias_semana é obrigatório
        if ($frequencia === 'diaria' && empty($diasSemana)) {
            return ['sucesso' => false, 'mensagem' => 'Selecione pelo menos um dia da semana.'];
        }

        // Normalizar dias da semana
        $diasSemana = $this->normalizarDiasSemana($diasSemana);
        if ($diasSemana === false) {
            return ['sucesso' => false, 'mensagem' => 'Dias da semana inválidos.'];
        }

        // Atualizar no banco
        $this->cronogramaDao->atualizar(
            id: $id,
            frequencia: $frequencia,
            horario: $horario,
            diasSemana: $diasSemana,
            diaMes: $diaMes
        );

        $this->logAdminDao->registrar($executorId, 'cronograma.atualizado', 'tb_cronograma', $id, null);

        return ['sucesso' => true, 'mensagem' => null];
    }

    /**
     * @return array{sucesso: bool, mensagem: ?string}
     */
    public function deletar(int $id, int $executorId): array
    {
        $item = $this->cronogramaDao->buscarPorId($id);
        if ($item === null) {
            return ['sucesso' => false, 'mensagem' => 'Item de cronograma não encontrado.'];
        }

        $this->cronogramaDao->deletar($id);
        $this->logAdminDao->registrar($executorId, 'cronograma.deletado', 'tb_cronograma', $id, null);

        return ['sucesso' => true, 'mensagem' => null];
    }
}