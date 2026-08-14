<?php
/**
 * partials/cabecalho.php
 *
 * Abre o HTML, monta a sidebar e a topbar. Todo Controller que
 * renderiza uma tela logada inclui esse partial primeiro.
 *
 * Espera (opcional) as seguintes variáveis vindas do Controller:
 *   $paginaAtiva      -> chave da rota atual, pra destacar o item certo na sidebar
 *   $tituloPagina     -> título mostrado na topbar
 *
 * Usa Udflow\util\ControleAcesso e Udflow\dao\PermissaoDao pra montar
 * a lista de automações que o usuário logado realmente enxerga.
 */

use Udflow\util\ControleAcesso;
use Udflow\util\Saida;
use Udflow\dao\PermissaoDao;

$usuarioSessao = $_SESSION['usuario'] ?? [];
$automacoesDoMenu = (new PermissaoDao())->automacoesVisiveisParaUsuario($usuarioSessao['id'] ?? 0);
$ehAdminDeAlgumaAutomacao = ControleAcesso::usuarioEhSuperAdmin()
    || (new PermissaoDao())->usuarioEhAdminDeAlgumaAutomacao($usuarioSessao['id'] ?? 0);

$paginaAtiva = $paginaAtiva ?? '';
$tituloPagina = $tituloPagina ?? 'UDFlow';

$iconesPorAutomacao = [
    'kpi' => '<path d="M4 20V10M11 20V4M18 20v-7"/>',
    'mao_obra_batida' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
    'estadia' => '<rect x="1" y="8" width="12" height="8" rx="1"/><path d="M13 11h4l3 3v2h-7z"/><circle cx="5.5" cy="17.5" r="1.6"/><circle cx="16.5" cy="17.5" r="1.6"/>',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Saida::e($tituloPagina) ?> · UDFlow</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          bg: '#0A0E1A', elevated: '#0F1526', surface: '#141B2E', surfhover: '#1A2238',
          bord: '#232B42', bordsoft: '#1B2338', tprimary: '#E8ECF6', tsecondary: '#8B93AC',
          tmuted: '#525A76', flow: '#1FD8C4', flowdark: '#0EA394', amber: '#F5A524',
          success: '#34D399', danger: '#F87171', info: '#60A5FA'
        },
        fontFamily: {
          display: ['"Space Grotesk"', 'sans-serif'],
          body: ['"Inter"', 'sans-serif'],
          mono: ['"JetBrains Mono"', 'monospace']
        }
      }
    }
  }
</script>
<style>
  * { scrollbar-width: thin; scrollbar-color: #232B42 transparent; }
  ::-webkit-scrollbar { width: 8px; height: 8px; }
  ::-webkit-scrollbar-thumb { background: #232B42; border-radius: 8px; }
  @keyframes pulse-dot { 0%,100% { opacity: .35; transform: scale(.85); } 50% { opacity: 1; transform: scale(1.15); } }
  .pulse-dot { animation: pulse-dot 1.6s ease-in-out infinite; }
  .glow-focus:focus { outline: none; box-shadow: 0 0 0 3px rgba(31,216,196,.18); border-color: #1FD8C4; }
  .grad-flow { background: linear-gradient(135deg, #1FD8C4 0%, #0EA394 100%); }
  .text-grad { background: linear-gradient(135deg, #1FD8C4 0%, #60A5FA 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
  input[type=color] { -webkit-appearance: none; border: none; padding: 0; background: none; }
  input[type=color]::-webkit-color-swatch { border-radius: 8px; border: 1px solid #232B42; }
</style>
</head>
<body class="bg-bg text-tprimary font-body antialiased">

<div class="min-h-screen flex">

  <aside id="sidebar" class="fixed lg:sticky top-0 h-screen w-64 bg-elevated border-r border-bord flex flex-col z-40 -translate-x-full lg:translate-x-0 transition-transform">
    <div class="flex items-center gap-2.5 px-5 h-16 border-b border-bord shrink-0">
      <svg width="26" height="26" viewBox="0 0 56 56">
        <circle cx="10" cy="38" r="5" fill="#1FD8C4"/>
        <circle cx="28" cy="14" r="5" fill="#1FD8C4" opacity="0.85"/>
        <circle cx="46" cy="38" r="5" fill="#0EA394"/>
        <path d="M 10 38 C 18 38, 20 14, 28 14 S 38 38, 46 38" fill="none" stroke="#1FD8C4" stroke-width="2" opacity="0.5"/>
      </svg>
      <span class="font-display font-semibold text-[15px]">UD<span class="text-flow">Flow</span></span>
      <button onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); document.getElementById('sidebar-overlay').classList.add('hidden')" class="ml-auto lg:hidden text-tsecondary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
      <a href="index.php?pagina=home" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition <?= $paginaAtiva === 'home' ? 'bg-flow/10 text-flow' : 'text-tsecondary hover:text-tprimary hover:bg-surface' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10"/></svg>
        Início
      </a>

      <?php if (!empty($automacoesDoMenu)): ?>
        <p class="px-3 pt-4 pb-1.5 text-[10px] font-semibold tracking-widest text-tmuted">AUTOMAÇÕES</p>
        <?php foreach ($automacoesDoMenu as $automacao): ?>
          <a href="index.php?pagina=<?= Saida::e(str_replace('_', '-', $automacao['chave'])) ?>"
             class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition <?= $paginaAtiva === $automacao['chave'] ? 'bg-flow/10 text-flow' : 'text-tsecondary hover:text-tprimary hover:bg-surface' ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><?= $iconesPorAutomacao[$automacao['chave']] ?? '<circle cx="12" cy="12" r="9"/>' ?></svg>
            <?= Saida::e($automacao['nome']) ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($ehAdminDeAlgumaAutomacao): ?>
        <p class="px-3 pt-5 pb-1.5 text-[10px] font-semibold tracking-widest text-tmuted flex items-center gap-1.5">ADMINISTRAÇÃO <span class="bg-amber/15 text-amber text-[9px] px-1.5 py-0.5 rounded font-bold tracking-wide">ADMIN</span></p>

        <a href="index.php?pagina=admin-logs" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition <?= $paginaAtiva === 'admin-logs' ? 'bg-flow/10 text-flow' : 'text-tsecondary hover:text-tprimary hover:bg-surface' ?>">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
          Logs e status
        </a>
        <a href="index.php?pagina=admin-cronograma" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition <?= $paginaAtiva === 'admin-cronograma' ? 'bg-flow/10 text-flow' : 'text-tsecondary hover:text-tprimary hover:bg-surface' ?>">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/><circle cx="8" cy="14" r="1.4"/><circle cx="13" cy="14" r="1.4"/></svg>
          Cronograma
        </a>
        <a href="index.php?pagina=admin-clientes" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition <?= $paginaAtiva === 'admin-clientes' ? 'bg-flow/10 text-flow' : 'text-tsecondary hover:text-tprimary hover:bg-surface' ?>">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 21V7l8-4 8 4v14M9 21v-6h6v6"/></svg>
          Clientes
        </a>
        <?php if (ControleAcesso::usuarioEhSuperAdmin()): ?>
          <a href="index.php?pagina=admin-usuarios" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition <?= $paginaAtiva === 'admin-usuarios' ? 'bg-flow/10 text-flow' : 'text-tsecondary hover:text-tprimary hover:bg-surface' ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 19c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6M16 8.2a3 3 0 110 5.8M21.5 19c0-2.9-2-5-5-5.6"/></svg>
            Usuários e permissões
          </a>
          <a href="index.php?pagina=admin-automacoes" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition <?= $paginaAtiva === 'admin-automacoes' ? 'bg-flow/10 text-flow' : 'text-tsecondary hover:text-tprimary hover:bg-surface' ?>">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
            Automações
          </a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>

    <div class="border-t border-bord p-3 shrink-0">
      <div class="flex items-center gap-2.5 px-2 py-2">
        <div class="w-8 h-8 rounded-full grad-flow flex items-center justify-center text-[11px] font-bold text-[#04342C] shrink-0">
          <?= Saida::e(mb_strtoupper(mb_substr($usuarioSessao['nome'] ?? '?', 0, 1))) ?>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-[13px] font-medium truncate"><?= Saida::e($usuarioSessao['nome'] ?? '') ?></p>
          <p class="text-[11px] text-tmuted truncate"><?= ControleAcesso::usuarioEhSuperAdmin() ? 'Super administrador' : 'Usuário' ?></p>
        </div>
        <a href="index.php?pagina=sair" title="Sair" class="text-tmuted hover:text-danger transition shrink-0">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        </a>
      </div>
    </div>
  </aside>

  <div id="sidebar-overlay" onclick="document.getElementById('sidebar').classList.add('-translate-x-full'); this.classList.add('hidden')" class="hidden fixed inset-0 bg-black/50 z-30 lg:hidden"></div>

  <main class="flex-1 min-w-0">
    <header class="h-16 border-b border-bord flex items-center gap-3 px-5 lg:px-8 sticky top-0 bg-bg/90 backdrop-blur z-20">
      <button onclick="document.getElementById('sidebar').classList.remove('-translate-x-full'); document.getElementById('sidebar-overlay').classList.remove('hidden')" class="lg:hidden text-tsecondary"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
      <h2 class="font-display font-semibold text-[15px]"><?= Saida::e($tituloPagina) ?></h2>
    </header>

    <div class="p-5 lg:p-8 max-w-6xl mx-auto">

      <?php if (!empty($_SESSION['flash_sucesso'])): ?>
        <div class="mb-5 flex items-center gap-2.5 bg-success/10 border border-success/25 text-success text-sm rounded-lg px-4 py-3">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
          <?= Saida::e($_SESSION['flash_sucesso']) ?>
        </div>
        <?php unset($_SESSION['flash_sucesso']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['flash_erro'])): ?>
        <div class="mb-5 flex items-center gap-2.5 bg-danger/10 border border-danger/25 text-danger text-sm rounded-lg px-4 py-3">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
          <?= Saida::e($_SESSION['flash_erro']) ?>
        </div>
        <?php unset($_SESSION['flash_erro']); ?>
      <?php endif; ?>
