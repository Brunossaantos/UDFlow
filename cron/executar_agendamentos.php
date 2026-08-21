<?php

/**
 * cron/executar_agendamentos.php
 *
 * Chamado pelo Cron Job do cPanel a cada minuto. Olha a
 * tb_cronograma, acha o que precisa disparar agora e chama
 * exatamente a mesma regra de negócio que o botão "Executar agora"
 * usa (ExecucaoRn) - só que sem usuário logado por trás, então fica
 * registrado como origem "automatico".
 *
 * Configurar no cPanel > Cron Jobs:
 *   Minuto: *   Hora: *   Dia: *   Mês: *   Dia da semana: *
 *   Comando: php /home/SEU_USUARIO_CPANEL/public_html/udflow/cron/executar_agendamentos.php
 */

require_once __DIR__ . '/../config/bootstrap-cli.php';

use Udflow\dao\CronogramaDao;
use Udflow\rn\ExecucaoRn;

$agora = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$horarioAtual = $agora->format('H:i:00');
$diaMesAtual = (int) $agora->format('j');
$diaSemanaAtual = (int) $agora->format('N');

$itens = (new CronogramaDao())->buscarParaExecutarAgora($horarioAtual, $diaMesAtual, $diaSemanaAtual);

foreach ($itens as $item) {
    if (empty($item['email_responsavel'])) {
        error_log("Cronograma {$item['id']} ({$item['cliente_nome']}) sem e-mail do responsável cadastrado - pulando.");
        continue;
    }

    $resultado = (new ExecucaoRn())->executarAutomatico(
        $item['automacao_chave'],
        (int) $item['cliente_id'],
        $item['email_responsavel']
    );

    if (!$resultado['sucesso']) {
        error_log("Falha ao disparar automático - cronograma {$item['id']} ({$item['cliente_nome']}): {$resultado['mensagem']}");
    }
}
