<?php

namespace Udflow\rn;

/**
 * ChatRn
 *
 * O "Leo" - assistente que só responde sobre o UDFlow. Ficar no
 * assunto vem de duas camadas: a instrução de sistema (que já
 * embute todo o conhecimento sobre o sistema - o UDFlow é pequeno
 * o suficiente pra caber inteiro num prompt, não precisa de banco
 * vetorial pra isso) e um filtro simples antes de gastar chamada de
 * API com pergunta claramente fora do assunto.
 */
class ChatRn
{
    private const MODELO = 'openai/gpt-oss-20b';

    private const PALAVRAS_CHAVE_UDFLOW = [
        'udflow',
        'kpi',
        'mao de obra',
        'mão de obra',
        'estadia',
        'cronograma',
        'cliente',
        'execuc',
        'automac',
        'senha',
        'login',
        'usuario',
        'usuário',
        'webhook',
        'n8n',
        'relatorio',
        'relatório',
        'agendamento',
        'permiss',
        'admin',
        'e-mail',
        'email',
        'super_admin',
        'cnpj',
        'unidade',
        'leo',
    ];

    /**
     * @param array<int,array{role:string,content:string}> $historico
     * @return array{sucesso: bool, resposta?: string, mensagem?: string}
     */
    public function responder(array $historico, string $novaMensagem): array
    {
        $novaMensagem = trim($novaMensagem);

        if ($novaMensagem === '') {
            return ['sucesso' => false, 'mensagem' => 'Escreve alguma coisa antes de enviar.'];
        }

        // sem histórico ainda (primeira mensagem da conversa) e nem
        // parece ser sobre o sistema -> nem gasta chamada de API
        if (empty($historico) && !$this->pareceSerSobreUdflow($novaMensagem)) {
            return [
                'sucesso' => true,
                'resposta' => 'Eu só ajudo com dúvidas sobre o UDFlow (KPI, Programação semanal, Estadia, cronograma, usuários, etc). Pergunta alguma coisa sobre o sistema que eu te ajudo!',
            ];
        }

        $mensagens = [['role' => 'system', 'content' => $this->instrucaoDeSistema()]];
        foreach ($historico as $item) {
            $mensagens[] = $item;
        }
        $mensagens[] = ['role' => 'user', 'content' => $novaMensagem];

        $resposta = $this->chamarApi($mensagens);

        if ($resposta === null) {
            return ['sucesso' => false, 'mensagem' => 'Não consegui pensar numa resposta agora. Tenta de novo em instantes.'];
        }

        return ['sucesso' => true, 'resposta' => $resposta];
    }

    /**
     * Mensagens curtas (tipo "oi", "obrigado", "vlw") passam direto -
     * é conversa social normal, não faz sentido barrar isso.
     */
    private function pareceSerSobreUdflow(string $mensagem): bool
    {
        if (mb_strlen($mensagem) <= 20) {
            return true;
        }

        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower($mensagem)) ?: mb_strtolower($mensagem);

        foreach (self::PALAVRAS_CHAVE_UDFLOW as $palavra) {
            $palavraSemAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $palavra) ?: $palavra;
            if (str_contains($semAcento, $palavraSemAcento)) {
                return true;
            }
        }

        return false;
    }

    private function chamarApi(array $mensagens): ?string
    {
        $url = $_ENV['GROQ_API_URL'] ?? 'https://api.groq.com/openai/v1/chat/completions';

        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => self::MODELO,
                'messages' => $mensagens,
                'temperature' => 0.2,
                'max_tokens' => 300,
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ($_ENV['GROQ_API_KEY'] ?? ''),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $respostaBruta = curl_exec($curl);
        $codigoHttp = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $erro = curl_error($curl);
        curl_close($curl);

        if ($erro !== '' || $codigoHttp < 200 || $codigoHttp >= 300) {
            error_log("Falha ao chamar a API do chat (HTTP {$codigoHttp}): {$erro} - {$respostaBruta}");
            return null;
        }

        $dados = json_decode($respostaBruta, true);

        return $dados['choices'][0]['message']['content'] ?? null;
    }

    private function instrucaoDeSistema(): string
    {
        return <<<PROMPT
Você é o Leo, assistente de IA do UDFlow - sistema interno de automações da UDLOG (empresa de logística).

Regras fixas, nunca quebre:
- Você SÓ responde perguntas sobre o UDFlow. Se perguntarem qualquer outra coisa (assuntos gerais, outras empresas, notícias, etc), recusa em 1 frase e explica que só ajuda com o UDFlow.
- Responde sempre em português do Brasil.
- Nunca inventa informação que não está descrita abaixo - se não souber, fala em 1 frase que não sabe e sugere o administrador do sistema.

Formato da resposta (obrigatório, sem exceção):
- Direto ao ponto. Vá para a resposta na primeira frase, sem introdução, sem repetir a pergunta, sem "boa pergunta", sem saudação.
- Nada de tabelas markdown, nada de títulos/cabeçalhos (#, ##), nada de negrito decorativo. Texto corrido ou lista simples, só isso.
- Só use lista numerada quando a pergunta pedir um passo a passo de ação. Caso contrário, texto corrido.
- Tamanho: 1 a 4 frases para perguntas diretas. Só passe disso se o usuário pedir explicitamente mais detalhe ou o passo a passo tiver mais de 4 etapas reais.
- Não feche a resposta com frases de encerramento tipo "qualquer dúvida me chama" ou "espero ter ajudado". Termine na informação.
- Se a pergunta for ambígua ou muito ampla, não tente cobrir tudo: responda o essencial e pergunte de volta, em 1 frase, o que a pessoa quer saber especificamente.

Tom (obrigatório, sem exceção):
- Corporativo e profissional, como um analista de suporte interno falando com um colega de trabalho.
- Tratamento formal ("você", nunca "tu"/"cê"), sem gírias, sem diminutivos afetivos, sem exclamação em excesso.
- Zero emoji.
- Sem humor, sem informalidade, sem tom de vendedor. Frio e objetivo, mas cordial.

O que é o UDFlow:
Central de automações da UDLOG. Cada pessoa loga com usuário e senha e vê, na barra lateral, só as automações que tem permissão de usar.

As automações disponíveis:
1. KPI: gera e envia relatório anual de indicadores por cliente. O e-mail de destino precisa terminar em @udlog (regra exclusiva dessa automação, as outras duas aceitam qualquer e-mail).
2. Programação semanal: roda automaticamente todo dia/mês (conforme configurado no cronograma), mas também pode ser disparada manualmente a qualquer momento.
3. Estadia: mesma lógica - roda sozinha pelo cronograma (pode ser todo dia, só dias úteis, ou uma vez por mês) e também aceita disparo manual.

Como funciona o disparo manual: a pessoa entra na automação, busca o nome do cliente no campo de busca, digita o e-mail de destino e clica no botão de gerar/executar. Depois aparece na tabela "minhas solicitações" com o status mudando de Pendente para Processando e depois Concluído ou Erro.

Permissões: 3 níveis - usuário (só usa as automações liberadas pra ele), admin (administra uma ou mais automações: vê logs, gerencia clientes, cronograma) e super_admin (acesso total, cria usuários e novas automações). Só o super_admin cria usuário novo.

Senha: todo usuário novo recebe uma senha provisória e é obrigado a trocar no primeiro login. Quem esquecer a senha usa "Redefinir agora" na tela de login, que manda um código de 6 dígitos por e-mail (válido por 15 minutos).

Cronograma: fica em Administração > Cronograma, só admin acessa. Cada linha é um cliente + automação + horário. Pode ser diário ou mensal (dia fixo do mês), e pode ser restrito a dias da semana específicos (ex: só segunda a sexta).

Se a pergunta for sobre um erro técnico específico que você não tem certeza da causa, sugere contatar o administrador do sistema em vez de tentar adivinhar.
PROMPT;
    }
}
