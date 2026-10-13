@once
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
window.KC = {
  palette: ['#2563eb','#0ea5e9','#14b8a6','#f59e0b','#8b5cf6','#ef4444','#64748b','#22c55e'],
  dark: () => document.documentElement.classList.contains('dark'),
  grid() { return this.dark() ? '#1e2b45' : '#eef2f7'; },
  fmt: n => Number(n || 0).toLocaleString('ru-RU'),
  base() {
    if (window.Chart) { Chart.defaults.font.family = "Inter, ui-sans-serif, system-ui, sans-serif"; Chart.defaults.color = this.dark() ? '#94a3b8' : '#64748b'; }
  },
  area(id, labels, data, label, money = true) {
    const el = document.getElementById(id); if (!el) return; this.base();
    const ctx = el.getContext('2d'); const g = ctx.createLinearGradient(0, 0, 0, 260);
    g.addColorStop(0, 'rgba(37,99,235,.25)'); g.addColorStop(1, 'rgba(37,99,235,0)');
    new Chart(el, { type: 'line', data: { labels, datasets: [{ label, data, borderColor: '#2563eb', backgroundColor: g, fill: true, tension: .35, pointRadius: 3, pointBackgroundColor: this.dark() ? '#111a2e' : '#fff', pointBorderWidth: 2, borderWidth: 2.5 }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + KC.fmt(c.parsed.y) + (money ? " so'm" : '') } } },
        scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: KC.grid() }, ticks: { callback: v => KC.fmt(v) } } } } });
  },
  bar(id, labels, data, label, horizontal = false, money = true) {
    const el = document.getElementById(id); if (!el) return; this.base();
    new Chart(el, { type: 'bar', data: { labels, datasets: [{ label, data, backgroundColor: labels.map((_, i) => this.palette[i % this.palette.length]), borderRadius: 8, maxBarThickness: 36 }] },
      options: { indexAxis: horizontal ? 'y' : 'x', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + KC.fmt(horizontal ? c.parsed.x : c.parsed.y) + (money ? " so'm" : '') } } },
        scales: horizontal ? { x: { beginAtZero: true, grid: { color: KC.grid() }, ticks: { callback: v => KC.fmt(v) } }, y: { grid: { display: false } } }
                           : { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: KC.grid() }, ticks: { callback: v => KC.fmt(v) } } } } });
  },
  donut(id, labels, data, money = true, colors = null) {
    const el = document.getElementById(id); if (!el) return; this.base();
    new Chart(el, { type: 'doughnut', data: { labels, datasets: [{ data, backgroundColor: colors || labels.map((_, i) => this.palette[i % this.palette.length]), borderWidth: 3, borderColor: this.dark() ? '#111a2e' : '#fff', hoverOffset: 6 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + KC.fmt(c.parsed) + (money ? " so'm" : '') } } } } });
  }
};
</script>
@endonce
