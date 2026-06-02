</div><!-- /main-content -->
</div><!-- /layout -->

<script>
(function () {
  const btnMenu = document.getElementById('btn-menu');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sb-overlay');

  function abrirSb()  { sidebar.classList.add('aberta');    overlay.classList.add('visivel'); }
  function fecharSb() { sidebar.classList.remove('aberta'); overlay.classList.remove('visivel'); }

  if (btnMenu) btnMenu.addEventListener('click', abrirSb);
  if (overlay)  overlay.addEventListener('click', fecharSb);

  // Fecha alertas flash
  document.querySelectorAll('[data-fechar-alerta]').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.alerta').remove());
  });
})();
</script>
</body>
</html>
