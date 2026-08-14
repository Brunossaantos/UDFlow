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
use Udflow\controller\KpiController;
use Udflow\controller\MaoObraController;
use Udflow\controller\EstadiaController;
use Udflow\controller\CallbackController;
use Udflow\controller\AdminUsuarioController;
use Udflow\controller\AdminClienteController;
use Udflow\controller\AdminAutomacaoController;
use Udflow\controller\AdminLogController;
use Udflow\controller\AdminCronogramaController;

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
    'kpi' => ['auth' => true, 'papel' => ['automacao' => 'kpi', 'minimo' => 'usuario'], 'controller' => KpiController::class, 'acao' => 'tela'],
    'kpi-clientes' => ['auth' => true, 'papel' => ['automacao' => 'kpi', 'minimo' => 'usuario'], 'controller' => KpiController::class, 'acao' => 'buscarClientes'],
    'kpi-enviar' => ['auth' => true, 'metodo' => 'POST', 'papel' => ['automacao' => 'kpi', 'minimo' => 'usuario'], 'controller' => KpiController::class, 'acao' => 'enviar'],

    // --- Mão de Obra Batida ---
    'mao-obra' => ['auth' => true, 'papel' => ['automacao' => 'mao_obra_batida', 'minimo' => 'usuario'], 'controller' => MaoObraController::class, 'acao' => 'tela'],
    'mao-obra-clientes' => ['auth' => true, 'papel' => ['automacao' => 'mao_obra_batida', 'minimo' => 'usuario'], 'controller' => MaoObraController::class, 'acao' => 'buscarClientes'],
    'mao-obra-enviar' => ['auth' => true, 'metodo' => 'POST', 'papel' => ['automacao' => 'mao_obra_batida', 'minimo' => 'usuario'], 'controller' => MaoObraController::class, 'acao' => 'enviar'],

    // --- Estadia ---
    'estadia' => ['auth' => true, 'papel' => ['automacao' => 'estadia', 'minimo' => 'usuario'], 'controller' => EstadiaController::class, 'acao' => 'tela'],
    'estadia-clientes' => ['auth' => true, 'papel' => ['automacao' => 'estadia', 'minimo' => 'usuario'], 'controller' => EstadiaController::class, 'acao' => 'buscarClientes'],
    'estadia-enviar' => ['auth' => true, 'metodo' => 'POST', 'papel' => ['automacao' => 'estadia', 'minimo' => 'usuario'], 'controller' => EstadiaController::class, 'acao' => 'enviar'],

    // --- Administração ---
    // Logs, Clientes e Cronograma liberam pra quem é admin de pelo
    // menos uma automação (ControleAcesso::exigirAdminDeAlgumaAutomacao,
    // chamado dentro do próprio Controller). Usuários e Automações são
    // exclusivos do super_admin - por isso essas duas rotas não levam
    // "papel" aqui: a checagem mais rígida acontece dentro do Controller.
    'admin-logs' => ['auth' => true, 'controller' => AdminLogController::class, 'acao' => 'tela'],

    'admin-clientes' => ['auth' => true, 'controller' => AdminClienteController::class, 'acao' => 'tela'],
    'admin-clientes-criar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminClienteController::class, 'acao' => 'criar'],
    'admin-clientes-atualizar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminClienteController::class, 'acao' => 'atualizar'],

    'admin-usuarios' => ['auth' => true, 'controller' => AdminUsuarioController::class, 'acao' => 'tela'],
    'admin-usuarios-criar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminUsuarioController::class, 'acao' => 'criar'],
    'admin-usuarios-ativo' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminUsuarioController::class, 'acao' => 'alternarAtivo'],
    'admin-usuarios-permissoes' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminUsuarioController::class, 'acao' => 'atualizarPermissoes'],

    'admin-automacoes' => ['auth' => true, 'controller' => AdminAutomacaoController::class, 'acao' => 'tela'],
    'admin-automacoes-visibilidade' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminAutomacaoController::class, 'acao' => 'alternarVisibilidade'],

    'admin-cronograma' => ['auth' => true, 'controller' => AdminCronogramaController::class, 'acao' => 'tela'],
    'admin-cronograma-ativo' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminCronogramaController::class, 'acao' => 'alternarAtivo'],
    'admin-cronograma-executar' => ['auth' => true, 'metodo' => 'POST', 'controller' => AdminCronogramaController::class, 'acao' => 'executarAgora'],

];
