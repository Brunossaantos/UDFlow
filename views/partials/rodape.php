<?php
/**
 * partials/rodape.php
 *
 * Fecha as divs abertas no cabecalho.php e carrega o JS
 * compartilhado (toast e o helper de fetch com CSRF).
 */
?>
    </div>
  </main>
</div>

<div id="toast" class="hidden fixed bottom-6 right-6 bg-elevated border border-bord rounded-xl px-4 py-3 shadow-2xl z-[60] items-center gap-3 max-w-xs">
  <div id="toast-icone" class="w-7 h-7 rounded-full bg-flow/15 flex items-center justify-center shrink-0"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1FD8C4" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg></div>
  <p id="toast-msg" class="text-sm text-tprimary"></p>
</div>

<script>
  const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;

  function toast(msg, tipo = 'sucesso') {
    const t = document.getElementById('toast');
    const icone = document.getElementById('toast-icone');
    document.getElementById('toast-msg').textContent = msg;

    if (tipo === 'erro') {
      icone.className = 'w-7 h-7 rounded-full bg-danger/15 flex items-center justify-center shrink-0';
      icone.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#F87171" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>';
    } else {
      icone.className = 'w-7 h-7 rounded-full bg-flow/15 flex items-center justify-center shrink-0';
      icone.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1FD8C4" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
    }

    t.classList.remove('hidden'); t.classList.add('flex');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => { t.classList.add('hidden'); t.classList.remove('flex'); }, 3500);
  }

  /** fetch() já mandando o token CSRF certo, pros endpoints que respondem em JSON */
  async function enviarComCsrf(url, dadosExtras = {}) {
    const corpo = new URLSearchParams({ csrf_token: CSRF_TOKEN, ...dadosExtras });
    const resposta = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo,
    });
    return resposta.json();
  }
</script>
</body>
</html>
