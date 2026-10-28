</main></div>
<script src="assets/js/app.js"></script>
<script>
/* Cuenta regresiva: cualquier elemento con data-countdown="YYYY-MM-DD HH:MM:SS" (hora de Guatemala, UTC-6) */
(function () {
  var els = document.querySelectorAll('[data-countdown]');
  if (!els.length) return;
  function fmt(ms) {
    if (ms <= 0) return 'Finalizada';
    var s = Math.floor(ms / 1000), d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600),
        m = Math.floor(s % 3600 / 60), x = s % 60;
    if (d > 0) return d + 'd ' + h + 'h ' + m + 'm';
    if (h > 0) return h + 'h ' + m + 'm ' + x + 's';
    return m + 'm ' + x + 's';
  }
  function tick() {
    var now = Date.now();
    els.forEach(function (el) {
      var fin = new Date(el.dataset.countdown.replace(' ', 'T') + '-06:00').getTime();
      if (isNaN(fin)) return;
      var left = fin - now;
      el.textContent = fmt(left);
      el.classList.toggle('is-urgent', left > 0 && left < 3600000);
      el.classList.toggle('is-done', left <= 0);
    });
  }
  tick(); setInterval(tick, 1000);
})();
</script>
</body></html>