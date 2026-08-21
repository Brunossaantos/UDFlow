<?php
/**
 * views/trocar-senha.php
 * Renderizado por LoginController::telaTrocarSenha()
 * Cai aqui logo depois do login quando trocar_senha_no_login = 1
 */

use Udflow\util\Csrf;
use Udflow\util\Saida;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trocar senha · UDFlow</title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 56 56'%3E%3Crect width='56' height='56' rx='12' fill='%230A0E1A'/%3E%3Ccircle cx='10' cy='38' r='5' fill='%231FD8C4'/%3E%3Ccircle cx='28' cy='14' r='5' fill='%231FD8C4' opacity='.85'/%3E%3Ccircle cx='46' cy='38' r='5' fill='%230EA394'/%3E%3Cpath d='M10 38C18 38 20 14 28 14S38 38 46 38' fill='none' stroke='%231FD8C4' stroke-width='2' opacity='.5'/%3E%3C/svg%3E">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script>
  tailwind.config = { theme: { extend: {
    colors: { bg:'#0A0E1A', elevated:'#0F1526', surface:'#141B2E', bord:'#232B42',
      tprimary:'#E8ECF6', tsecondary:'#8B93AC', tmuted:'#525A76', flow:'#1FD8C4',
      flowdark:'#0EA394', danger:'#F87171', amber:'#F5A524', success:'#34D399' },
    fontFamily: { display:['"Space Grotesk"','sans-serif'], body:['"Inter"','sans-serif'] }
  } } }
</script>
<style>
  .glow-focus:focus { outline:none; box-shadow:0 0 0 3px rgba(31,216,196,.18); border-color:#1FD8C4; }
  .grad-flow { background: linear-gradient(135deg, #1FD8C4 0%, #0EA394 100%); }
</style>
</head>
<body class="bg-bg text-tprimary font-body antialiased">

<div class="min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-sm">
    <div class="flex flex-col items-center mb-6 text-center">
      <div class="w-11 h-11 rounded-full bg-amber/10 flex items-center justify-center mb-3">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F5A524" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
      </div>
      <h1 class="font-display font-semibold text-xl">Troca de senha obrigatória</h1>
      <p class="text-tsecondary text-sm mt-1">Essa é sua primeira vez aqui — troca a senha provisória antes de continuar.</p>
    </div>

    <?php if (!empty($_SESSION['flash_erro'])): ?>
      <div class="mb-4 flex items-center gap-2.5 bg-danger/10 border border-danger/25 text-danger text-sm rounded-lg px-4 py-3">
        <?= Saida::e($_SESSION['flash_erro']) ?>
      </div>
      <?php unset($_SESSION['flash_erro']); ?>
    <?php endif; ?>

    <form method="POST" action="index.php?pagina=trocar-senha-salvar" class="bg-surface border border-bord rounded-2xl p-6 space-y-4">
      <?= Csrf::campoHtml() ?>
      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Senha atual (provisória)</label>
        <input type="password" name="senha_atual" required autofocus class="w-full bg-elevated border border-bord rounded-lg px-3.5 py-2.5 text-sm glow-focus">
      </div>
      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Senha nova</label>
        <input type="password" name="senha_nova" required minlength="8" placeholder="Mínimo 8 caracteres, com letra e número" class="w-full bg-elevated border border-bord rounded-lg px-3.5 py-2.5 text-sm glow-focus">
      </div>
      <div>
        <label class="block text-xs font-medium text-tsecondary mb-1.5">Confirmar senha nova</label>
        <input type="password" name="senha_confirmacao" required minlength="8" class="w-full bg-elevated border border-bord rounded-lg px-3.5 py-2.5 text-sm glow-focus">
      </div>
      <button type="submit" class="w-full grad-flow text-[#04342C] font-semibold text-sm rounded-lg py-2.5 mt-2 hover:opacity-90 transition">Salvar nova senha</button>
    </form>
  </div>
</div>

</body>
</html>
