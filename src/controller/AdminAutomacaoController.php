<?php

namespace Udflow\controller;

use Udflow\rn\AutomacaoRn;
use Udflow\util\Csrf;
use Udflow\util\Saida;
use Udflow\util\ControleAcesso;

class AdminAutomacaoController
{
    public function tela(): void
    {
        ControleAcesso::exigirSuperAdmin();

        $automacoes = (new AutomacaoRn())->listar();

        require __DIR__ . '/../../views/admin/automacoes.php';
    }

    public function alternarVisibilidade(): void
    {
        ControleAcesso::exigirSuperAdmin();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $id = (int) ($_POST['automacao_id'] ?? 0);
        $visivel = ($_POST['visivel'] ?? '') === '1';

        if ($id <= 0) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Automação inválida.'], 422);
        }

        (new AutomacaoRn())->alternarVisibilidade($id, $visivel, ControleAcesso::usuarioLogadoId());

        Saida::json(['sucesso' => true]);
    }

    public function criar(): void
    {
        ControleAcesso::exigirSuperAdmin();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $nome = trim($_POST['nome'] ?? '');
        $chave = trim($_POST['chave'] ?? '');
        $rota = trim($_POST['rota'] ?? '');
        $iconSvg = trim($_POST['icon_svg'] ?? '') ?: null;
        $webhookUrl = trim($_POST['webhook_url'] ?? '');
        $webhookMetodo = trim($_POST['webhook_metodo'] ?? 'POST');
        $posicao = (int) ($_POST['posicao'] ?? 1);
        $possuiAgendamento = ($_POST['possui_agendamento'] ?? '') === '1';
        $visivelParaUsuarios = ($_POST['visivel_para_usuarios'] ?? '') === '1';

        if (empty($nome) || empty($chave) || empty($rota)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Preencha nome, chave e rota.'], 422);
        }

        if (!preg_match('/^[a-z_]+$/', $chave)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Chave deve conter apenas letras minúsculas e underscore.'], 422);
        }

        if (!preg_match('/^[a-z-]+$/', $rota)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Rota deve conter apenas letras minúsculas e hífen.'], 422);
        }

        if (!in_array($webhookMetodo, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Método HTTP inválido.'], 422);
        }

        $resultado = (new AutomacaoRn())->criar(
            nome: $nome,
            chave: $chave,
            rota: $rota,
            iconSvg: $iconSvg,
            webhookUrl: $webhookUrl,
            webhookMetodo: $webhookMetodo,
            posicao: $posicao,
            possuiAgendamento: $possuiAgendamento,
            visivelParaUsuarios: $visivelParaUsuarios,
            executorId: ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            Saida::json(['sucesso' => false, 'mensagem' => $resultado['mensagem']], 422);
        }

        Saida::json(['sucesso' => true, 'id' => $resultado['id']]);
    }

    public function atualizar(): void
    {
        ControleAcesso::exigirSuperAdmin();

        if (!Csrf::validarToken($_POST['csrf_token'] ?? null)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Sessão expirada.'], 419);
        }

        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $iconSvg = trim($_POST['icon_svg'] ?? '') ?: null;
        $webhookUrl = trim($_POST['webhook_url'] ?? '');
        $webhookMetodo = trim($_POST['webhook_metodo'] ?? 'POST');
        $posicao = (int) ($_POST['posicao'] ?? 1);
        $possuiAgendamento = ($_POST['possui_agendamento'] ?? '') === '1';
        $visivelParaUsuarios = ($_POST['visivel_para_usuarios'] ?? '') === '1';
        $ativo = ($_POST['ativo'] ?? '') === '1';

        if ($id <= 0 || empty($nome)) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Dados inválidos.'], 422);
        }

        if (!in_array($webhookMetodo, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            Saida::json(['sucesso' => false, 'mensagem' => 'Método HTTP inválido.'], 422);
        }

        $resultado = (new AutomacaoRn())->atualizar(
            id: $id,
            nome: $nome,
            iconSvg: $iconSvg,
            webhookUrl: $webhookUrl,
            webhookMetodo: $webhookMetodo,
            posicao: $posicao,
            possuiAgendamento: $possuiAgendamento,
            visivelParaUsuarios: $visivelParaUsuarios,
            ativo: $ativo,
            executorId: ControleAcesso::usuarioLogadoId()
        );

        if (!$resultado['sucesso']) {
            Saida::json(['sucesso' => false, 'mensagem' => $resultado['mensagem']], 422);
        }

        Saida::json(['sucesso' => true]);
    }
}