<?php
/**
 * views/esqueci-senha.php
 * Renderizado por LoginController::telaEsqueciSenha()
 */

use Udflow\util\Csrf;
use Udflow\util\Saida;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Redefinir senha · UDFlow</title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 56 56'%3E%3Crect width='56' height='56' rx='12' fill='%230A0E1A'/%3E%3Ccircle cx='10' cy='38' r='5' fill='%231FD8C4'/%3E%3Ccircle cx='28' cy='14' r='5' fill='%231FD8C4' opacity='.85'/%3E%3Ccircle cx='46' cy='38' r='5' fill='%230EA394'/%3E%3Cpath d='M10 38C18 38 20 14 28 14S38 38 46 38' fill='none' stroke='%231FD8C4' stroke-width='2' opacity='.5'/%3E%3C/svg%3E">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script>
  tailwind.config = { theme: { extend: {
    colors: { bg:'#0A0E1A', elevated:'#0F1526', surface:'#141B2E', bord:'#232B42',
      tprimary:'#E8ECF6', tsecondary:'#8B93AC', tmuted:'#525A76', flow:'#1FD8C4',
      flowdark:'#0EA394', danger:'#F87171', success:'#34D399' },
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
    <div class="flex items-center justify-between mb-1">
      <h1 class="font-display font-semibold text-xl">Redefinir senha</h1>
      <a href="index.php?pagina=login" class="text-tmuted hover:text-tprimary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg></a>
    </div>

    <?php if (!empty($_SESSION['flash_erro'])): ?>
      <div class="mt-4 flex items-center gap-2.5 bg-danger/10 border border-danger/25 text-danger text-sm rounded-lg px-4 py-3">
        <?= Saida::e($_SESSION['flash_erro']) ?>
      </div>
      <?php unset($_SESSION['flash_erro']); ?>
    <?php endif; ?>

    <form method="POST" action="index.php?pagina=esqueci-senha-enviar" class="bg-surface border border-bord rounded-2xl p-6 mt-5">
      <?= Csrf::campoHtml() ?>
      <p class="text-tsecondary text-xs mb-5">Informe seu e-mail cadastrado. Vamos enviar um código de verificação pra você redefinir sua senha.</p>
      <label class="block text-xs font-medium text-tsecondary mb-1.5">E-mail</label>
      <input type="email" name="email" required autofocus placeholder="seuemail@gmail.com" class="w-full bg-elevated border border-bord rounded-lg px-3.5 py-2.5 text-sm glow-focus mb-5">
      <button type="submit" class="w-full grad-flow text-[#04342C] font-semibold text-sm rounded-lg py-2.5 hover:opacity-90 transition">Enviar código</button>
    </form>
  </div>
</div>

</body>
</html>
