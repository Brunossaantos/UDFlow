<?php

/**
 * rotas.php
 *
 * Cada chave é o valor de ?pagina= na URL. O index.php procura a
 * página pedida aqui dentro - se não achar, não existe chance de
 * chamar um Controller ou incluir um arquivo que não estava
 * previsto. Isso fecha a porta pra alguém tentar manipular a URL
 * pra rodar código arbitrário (o clássico "?pagina=../../../etc/passwd"
 * simplesmente não bate com nenhuma chave daqui e cai no 404).
 *
 * auth:   true  -> precisa estar logado
 * papel:  ['automacao' => 'kpi', 'minimo' => 'usuario'] -> além de
 *         logado, precisa desse papel na automação. Se não informar,
 *         só a checagem de login vale.
 */

use Udflow\controller\LoginController;
use Udflow\controller\HomeController;
use Udflow\controller\AutomacaoController;
use Udflow\controller\CallbackController;
use Udflow\controller\AdminUsuarioController;
use Udflow\controller\AdminClienteController;
use Udflow\controller\AdminAutomacaoController;
use Udflow\controller\AdminLogController;
use Udflow\controller\AdminLogSistemaController;
use Udflow\controller\AdminCronogramaController;
use Udflow\controller\AutomacaoConfigController;
use Udflow\controller\ChatController;

return [

    // --- chamada pelo n8n, protegida por token (não por sessão) ---
    'n8n-callback' => ['auth' => false, 'metodo' => 'POST', 'controller' => CallbackController::class, 'acao' => 'atualizarStatus'],

    // --- público (sem login) ---
    'login' => ['auth' => false, 'controller' => LoginController::class, 'acao' => 'tela'],
    'login-entrar' => ['auth' => false, 'metodo' => 'POST', 'controller' => LoginController::class, 'acao' => 'entrar'],
    'sair' => ['auth' => true, 'controller' => LoginController::class, 'acao' => 'sair'],
    'esqueci-senha' => ['auth' => false, 'controller' => LoginController::class, 'acao' => 'telaEsqueciSenha'],
    'esqueci-senha-enviar' => ['auth' => false, 'metodo' => 'POST', 'controller' => LoginController::class, 'acao' => 'enviarCodigo'],
    'redefinir-senha' => ['auth' => false, 'controller' => LoginController::class, 'acao' => 'telaRedefinirSenha'],
    'redefinir-senha-confirmar' => ['auth' => false, 'metodo' => 'POST', 'controller' => LoginController::class, 'acao' => 'confirmarRedefinicao'],

    // --- troca de senha obrigatória (logado, mas ainda com a senha provisória) ---
    'trocar-senha' => ['auth' => true, 'controller' => LoginController::class, 'acao' => 'telaTrocarSenha'],
    'trocar-senha-salvar' => ['auth' => true, 'metodo' => 'POST', 'controller' => LoginController::class, 'acao' => 'trocarSenha'],

    // --- geral (logado) ---
    'home' => ['auth' => true, 'controller' => HomeController::class, 'acao' => 'tela'],

    // --- KPI ---
    'kpi' => ['auth' => true, 'papel' => ['automacao' => 'kpi', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'tela'],
    'kpi-clientes' => ['auth' => true, 'papel' => ['automacao' => 'kpi', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'buscarClientes'],
    'kpi-enviar' => ['auth' => true, 'metodo' => 'POST', 'papel' => ['automacao' => 'kpi', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'enviar'],
    'kpi-status' => ['auth' => true, 'papel' => ['automacao' => 'kpi', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'statusExecucoes'],

    // --- Programação semanal ---
    'programacao-semanal' => ['auth' => true, 'papel' => ['automacao' => 'programacao_semanal', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'tela'],
    'programacao-semanal-clientes' => ['auth' => true, 'papel' => ['automacao' => 'programacao_semanal', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'buscarClientes'],
    'programacao-semanal-enviar' => ['auth' => true, 'metodo' => 'POST', 'papel' => ['automacao' => 'programacao_semanal', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'enviar'],
    'programacao-semanal-status' => ['auth' => true, 'papel' => ['automacao' => 'programacao_semanal', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'statusExecucoes'],

    // --- Estadia ---
    'estadia' => ['auth' => true, 'papel' => ['automacao' => 'estadia', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'tela'],
    'estadia-clientes' => ['auth' => true, 'papel' => ['automacao' => 'estadia', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'buscarClientes'],
    'estadia-enviar' => ['auth' => true, 'metodo' => 'POST', 'papel' => ['automacao' => 'estadia', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'enviar'],
    'estadia-status' => ['auth' => true, 'papel' => ['automacao' => 'estadia', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'statusExecucoes'],

    // --- Relatório de Avarias ---
    'relatorio-avarias' => ['auth' => true, 'papel' => ['automacao' => 'relatorio_avarias', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'tela'],
    'relatorio-avarias-clientes' => ['auth' => true, 'papel' => ['automacao' => 'relatorio_avarias', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'buscarClientes'],
    'relatorio-avarias-enviar' => ['auth' => true, 'metodo' => 'POST', 'papel' => ['automacao' => 'relatorio_avarias', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'enviar'],
    'relatorio-avarias-status' => ['auth' => true, 'papel' => ['automacao' => 'relatorio_avarias', 'minimo' => 'usuario'], 'controller' => AutomacaoController::class, 'acao' => 'statusExecucoes'],

    // --- Administração ---
    // Logs (execuções), Clientes e Cronograma liberam pra quem é admin
    // de pelo menos uma automação (ControleAcesso::exigirAdminDeAlgumaAutomacao,
    // chamado dentro do próprio Controller). Usuários, Automações, Logs
    // de sistema e Configurar Automações são exclusivos do super_admin -
    // por isso essas rotas não levam "papel" aqui: a checagem mais
    // rígida acontece dentro do Controller.
    'admin-logs' => ['auth' => true, 'controller' => AdminLogController::class, 'acao' => 'tela'],

    // Logs de sistema (erros/exceptions/fatais) - exclusivo do super_admin,
    // checagem rígida dentro do próprio Controller (mesmo padrão de admin-usuarios)
    'admin-logs-sistema' => ['auth' => true, 'controller' => AdminLogSistemaController::class, 'acao' => 'tela'],

    'admin-clientes' => ['auth' => true, 'controller' => AdminClienteController::class, 'acao' => 'tela'],
    'admin-clientes-criar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminClienteController::class, 'acao' => 'criar'],
    'admin-clientes-atualizar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminClienteController::class, 'acao' => 'atualizar'],

    'admin-usuarios' => ['auth' => true, 'controller' => AdminUsuarioController::class, 'acao' => 'tela'],
    'admin-usuarios-criar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminUsuarioController::class, 'acao' => 'criar'],
    'admin-usuarios-ativo' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminUsuarioController::class, 'acao' => 'alternarAtivo'],
    'admin-usuarios-permissoes' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminUsuarioController::class, 'acao' => 'atualizarPermissoes'],

    'admin-automacoes' => ['auth' => true, 'controller' => AdminAutomacaoController::class, 'acao' => 'tela'],
    'admin-automacoes-criar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminAutomacaoController::class, 'acao' => 'criar'],
    'admin-automacoes-atualizar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminAutomacaoController::class, 'acao' => 'atualizar'],
    'admin-automacoes-visibilidade' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminAutomacaoController::class, 'acao' => 'alternarVisibilidade'],

    'admin-cronograma' => ['auth' => true, 'controller' => AdminCronogramaController::class, 'acao' => 'tela'],
    'admin-cronograma-criar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminCronogramaController::class, 'acao' => 'criar'],
    'admin-cronograma-editar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminCronogramaController::class, 'acao' => 'editar'],
    'admin-cronograma-ativo' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminCronogramaController::class, 'acao' => 'alternarAtivo'],
    'admin-cronograma-horario-ativo' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminCronogramaController::class, 'acao' => 'alternarHorarioIndividual'],
    'admin-cronograma-horario-atualizar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminCronogramaController::class, 'acao' => 'atualizarHorario'],
    'admin-cronograma-horario-deletar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminCronogramaController::class, 'acao' => 'deletarHorario'],
    'admin-cronograma-executar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminCronogramaController::class, 'acao' => 'executarAgora'],

    // --- Configuração de Automações (Webhooks e Payloads) ---
    'admin-automacao-config' => ['auth' => true, 'controller' => AutomacaoConfigController::class, 'acao' => 'tela'],
    'admin-automacao-config-editar' => ['auth' => true, 'controller' => AutomacaoConfigController::class, 'acao' => 'editar'],
    'admin-automacao-config-webhook' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'salvarWebhook'],
    'admin-automacao-config-campos-salvar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'salvarCamposSelecionados'],
    
    // Campos do Payload
    'admin-automacao-config-campo-criar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'criarCampo'],
    'admin-automacao-config-campo-atualizar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'atualizarCampo'],
    'admin-automacao-config-campo-deletar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'deletarCampo'],
    
    // Regras de Transformação
    'admin-automacao-config-regra-criar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'criarRegra'],
    'admin-automacao-config-regra-atualizar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'atualizarRegra'],
    'admin-automacao-config-regra-deletar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'deletarRegra'],
    
    // Headers Customizáveis
    'admin-automacao-config-header-criar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'criarHeader'],
    'admin-automacao-config-header-atualizar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'atualizarHeader'],
    'admin-automacao-config-header-deletar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AutomacaoConfigController::class, 'acao' => 'deletarHeader'],
    
    // Logs e Estatísticas
    'admin-automacao-config-logs' => ['auth' => true, 'controller' => AutomacaoConfigController::class, 'acao' => 'verLogs'],
    'admin-automacao-config-logs-erros' => ['auth' => true, 'controller' => AutomacaoConfigController::class, 'acao' => 'verLogsComErro'],

    // --- Leo (chat com IA) ---
    'chat-enviar' => ['auth' => true, 'metodo' => 'POST', 'controller' => ChatController::class, 'acao' => 'enviar'],
    'chat-limpar' => ['auth' => true, 'metodo' => 'POST', 'controller' => ChatController::class, 'acao' => 'limparHistorico'],

];