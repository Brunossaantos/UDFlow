<?php

namespace Udflow\rn;

/**
 * N8nRn
 *
 * Só sabe fazer uma coisa: mandar um POST pro webhook do n8n com o
 * payload da execução, carregando o token de autenticação no
 * header. Se o n8n não responder rápido, a gente não trava a tela
 * do usuário esperando - por isso o timeout curto: o n8n processa
 * assíncrono e avisa o UDFlow depois pelo endpoint de callback.
 */
class N8nRn
{
    public function dispararWebhook(?string $webhookUrl, array $payload): bool
    {
        if (empty($webhookUrl)) {
            error_log('Tentativa de disparar webhook sem URL configurada.');
            return false;
        }

        $curl = curl_init($webhookUrl);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Udflow-Token: ' . $_ENV['N8N_WEBHOOK_TOKEN'],
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, // não precisa esperar o relatório terminar, só confirmar que o n8n recebeu
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        curl_exec($curl);
        $codigoHttp = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $erro = curl_error($curl);
        curl_close($curl);

        if ($erro !== '') {
            error_log("Falha ao chamar webhook do n8n ({$webhookUrl}): {$erro}");
            return false;
        }

        return $codigoHttp >= 200 && $codigoHttp < 300;
    }
}
