<?php

namespace Udflow\rn;

use Udflow\util\LogSistema;

/**
 * Responsável por enviar os dados das automações
 * para os webhooks do n8n.
 */
class N8nRn
{
    public function dispararWebhook(?string $webhookUrl, array $payload): bool
    {
        if (empty($webhookUrl)) {
            LogSistema::registrar('error', 'Tentativa de disparar webhook sem URL configurada.');
            return false;
        }

        $curl = curl_init($webhookUrl);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
            ),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Udflow-Token: ' . ($_ENV['N8N_WEBHOOK_TOKEN'] ?? ''),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $resposta = curl_exec($curl);
        $codigoHttp = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $erro = curl_error($curl);

        curl_close($curl);

        /*
         * Registra detalhes somente quando o disparo falhar.
         * O token não é colocado no log.
         */
        if ($erro !== '') {
            LogSistema::registrar('error', 'Falha ao chamar webhook do n8n: ' . $erro, null, null, ['url' => $webhookUrl]);
            return false;
        }

        if ($codigoHttp < 200 || $codigoHttp >= 300) {
            LogSistema::registrar('error', 'Webhook do n8n retornou erro HTTP ' . $codigoHttp, null, null, [
                'url' => $webhookUrl,
                'http_status' => $codigoHttp,
                'resposta' => substr((string) $resposta, 0, 2000),
            ]);

            return false;
        }

        return true;
    }
}