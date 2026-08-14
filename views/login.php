<?php
/**
 * views/login.php
 * Renderizado por LoginController::tela()
 */

use Udflow\util\Csrf;
use Udflow\util\Saida;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar · UDFlow</title>
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
  @keyframes pulse-dot { 0%,100% { opacity:.35; transform:scale(.85); } 50% { opacity:1; transform:scale(1.15); } }
  .pulse-dot { animation: pulse-dot 1.6s ease-in-out infinite; }
  .glow-focus:focus { outline:none; box-shadow:0 0 0 3px rgba(31,216,196,.18); border-color:#1FD8C4; }
  .grad-flow { background: linear-gradient(135deg, #1FD8C4 0%, #0EA394 100%); }
  .text-grad { background: linear-gradient(135deg, #1FD8C4 0%, #60A5FA 100%); -webkit-background-clip:text; background-clip:text; color:transparent; }
</style>
</head>
<body class="bg-bg text-tprimary font-body antialiased">

<div class="min-h-screen flex items-center justify-center relative overflow-hidden px-4">
  <div class="absolute inset-0">
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[900px] rounded-full opacity-[0.06]" style="background: radial-gradient(circle, #1FD8C4 0%, transparent 70%);"></div>
  </div>

  <div class="relative z-10 w-full max-w-sm">
    <div class="flex flex-col items-center mb-8">
      <svg width="56" height="56" viewBox="0 0 56 56" class="mb-4">
        <circle cx="10" cy="38" r="4.5" fill="#1FD8C4"/>
        <circle cx="28" cy="14" r="4.5" fill="#1FD8C4" opacity="0.85"/>
        <circle cx="46" cy="38" r="4.5" fill="#0EA394"/>
        <path d="M 10 38 C 18 38, 20 14, 28 14 S 38 38, 46 38" fill="none" stroke="#1FD8C4" stroke-width="1.5" opacity="0.5"/>
        <circle class="pulse-dot" cx="28" cy="14" r="2" fill="#E8ECF6"/>
      </svg>
      <h1 class="font-display font-semibold text-2xl tracking-tight">UD<span class="text-grad">Flow</span></h1>
      <p class="text-tsecondary text-sm mt-1">Central de automações UDLOG</p>
    </div>

    <?php if (!empty($_SESSION['flash_erro'])): ?>
      <div class="mb-4 flex items-center gap-2.5 bg-danger/10 border border-danger/25 text-danger text-sm rounded-lg px-4 py-3">
        <?= Saida::e($_SESSION['flash_erro']) ?>
      </div>
      <?php unset($_SESSION['flash_erro']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_sucesso'])): ?>
      <div class="mb-4 flex items-center gap-2.5 bg-success/10 border border-success/25 text-success text-sm rounded-lg px-4 py-3">
        <?= Saida::e($_SESSION['flash_sucesso']) ?>
      </div>
      <?php unset($_SESSION['flash_sucesso']); ?>
    <?php endif; ?>

    <form method="POST" action="index.php?pagina=login-entrar" class="bg-surface border border-bord rounded-2xl p-7">
      <?= Csrf::campoHtml() ?>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Usuário</label>
          <input type="text" name="usuario" required autofocus class="w-full bg-elevated border border-bord rounded-lg px-3.5 py-2.5 text-sm text-tprimary glow-focus transition" placeholder="seu.usuario">
        </div>
        <div>
          <label class="block text-xs font-medium text-tsecondary mb-1.5">Senha</label>
          <input type="password" name="senha" required class="w-full bg-elevated border border-bord rounded-lg px-3.5 py-2.5 text-sm text-tprimary glow-focus transition" placeholder="Sua senha">
        </div>
        <button type="submit" class="w-full grad-flow text-[#04342C] font-semibold text-sm rounded-lg py-2.5 mt-2 hover:opacity-90 transition">Entrar</button>
      </div>
      <p class="text-center text-xs text-tmuted mt-5">Esqueceu sua senha? <a href="index.php?pagina=esqueci-senha" class="text-flow font-medium hover:underline">Redefinir agora</a></p>
    </form>
    <p class="text-center text-xs text-tmuted mt-6">UDLOG © <?= date('Y') ?></p>
  </div>
</div>

</body>
</html>
