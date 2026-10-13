<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.classList.add('dark');}catch(e){}})();
window.toggleTheme=function(){var d=document.documentElement.classList.toggle('dark');try{localStorage.setItem('theme',d?'dark':'light');}catch(e){}if(window.Chart){location.reload();}};
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap&subset=latin,latin-ext,cyrillic" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] }, colors: { brand: 'var(--brand)', 'brand-dark': 'var(--brand-dark)' } } } }</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>
:root{--brand: {{ $brand['color'] }}; --brand-dark: {{ $brand['dark'] }}; --brand-soft: color-mix(in srgb, var(--brand) 10%, white); --brand-ring: color-mix(in srgb, var(--brand) 22%, transparent);}
[x-cloak]{display:none !important}
body{font-family:'Inter',ui-sans-serif,system-ui,sans-serif;background:#f4f6fb}
.input:focus{border-color:var(--brand);box-shadow:0 0 0 4px var(--brand-ring);outline:none}
.nav-active{background:var(--brand);color:#fff;box-shadow:0 6px 16px -6px var(--brand)}
.text-brand-soft{color:var(--brand)} .bg-brand-soft{background:var(--brand-soft)}
.slot:hover{background:var(--brand-soft)}
.hero-grad{background:linear-gradient(135deg,var(--brand) 0%,var(--brand-dark) 100%)}

/* ---------- Qorong'i rejim ---------- */
html.dark{color-scheme:dark}
.dark body{background:#0a1020;color:#e2e8f0}
.dark .bg-white,.dark .card{background:#111a2e}
.dark .bg-white\/80,.dark .bg-white\/90{background:rgba(10,16,32,.85)}
.dark .bg-slate-50,.dark .bg-slate-50\/80{background:#0e1628}
.dark .bg-slate-100{background:#1a2540}
.dark .card{border-color:#1e2b45;box-shadow:none}
.dark .th{background:#0e1628;color:#94a3b8}
.dark .td{color:#cbd5e1;border-color:#1b2742}
.dark table tr:hover > .td{background:#16213a}
.dark .text-slate-900,.dark .text-slate-800{color:#f1f5f9}
.dark .text-slate-700{color:#cbd5e1}
.dark .text-slate-600,.dark .text-slate-500{color:#94a3b8}
.dark .text-slate-400{color:#64748b}
.dark .border-slate-100,.dark .border-slate-200,.dark .border-slate-300,.dark .border-slate-200\/70{border-color:#1e2b45}
.dark .divide-slate-100>*{border-color:#1e2b45}
.dark .btn{background:#17223a;color:#e2e8f0;border-color:#2a3a5e;box-shadow:none}
.dark .btn:hover{background:#1e2c4a}
.dark .btn-primary{background:var(--brand);color:#fff;border-color:transparent}
.dark .btn-danger{background:#dc2626;color:#fff;border-color:transparent}
.dark .input{background:#0c1426;color:#e2e8f0;border-color:#2a3a5e}
.dark .input::placeholder{color:#64748b}
.dark select.input option{background:#0c1426}
.dark .bg-brand-soft,.dark .slot:hover{background:color-mix(in srgb,var(--brand) 16%,#0a1020)}
.dark aside{background:#070c18}
.dark header.sticky{border-color:#1e2b45}
.dark .nav-active{color:#fff}
.dark details.card summary{color:#e2e8f0}
.dark .bg-gradient-to-b,.dark .bg-gradient-to-br{background-image:none;background-color:#0a1020}
.dark .shadow-xl{box-shadow:0 20px 40px -20px rgba(0,0,0,.6)}
.dark .link{color:color-mix(in srgb,var(--brand) 55%,#93c5fd)}
.dark .text-brand{color:color-mix(in srgb,var(--brand) 55%,#93c5fd)}
/* rangli yengil fonlar (badge, stat, alert, kalendar) */
.dark .bg-emerald-50,.dark .bg-green-50{background:rgba(16,185,129,.14)}
.dark .bg-red-50{background:rgba(239,68,68,.14)}
.dark .bg-amber-50,.dark .bg-yellow-50{background:rgba(245,158,11,.14)}
.dark .bg-blue-50{background:rgba(59,130,246,.15)}
.dark .bg-orange-50{background:rgba(249,115,22,.14)}
.dark .bg-violet-50,.dark .bg-purple-50{background:rgba(139,92,246,.16)}
.dark .bg-cyan-50{background:rgba(6,182,212,.14)}
.dark .text-emerald-600,.dark .text-emerald-700,.dark .text-emerald-900,.dark .text-green-800{color:#6ee7b7}
.dark .text-red-600,.dark .text-red-700,.dark .text-red-800{color:#fca5a5}
.dark .text-amber-600,.dark .text-amber-700,.dark .text-amber-900,.dark .text-yellow-800{color:#fcd34d}
.dark .text-blue-600,.dark .text-blue-700,.dark .text-blue-900{color:#93c5fd}
.dark .text-orange-700,.dark .text-orange-800{color:#fdba74}
.dark .text-violet-600,.dark .text-violet-900{color:#c4b5fd}
.dark .text-cyan-600{color:#67e8f9}
.dark .border-red-200,.dark .border-green-200,.dark .border-orange-200,.dark .border-blue-200,.dark .border-amber-300{border-color:#2a3a5e}
.dark .ring-emerald-600\/20,.dark .ring-red-600\/20,.dark .ring-amber-600\/20,.dark .ring-blue-600\/20,.dark .ring-slate-500\/20,.dark .ring-orange-600\/20{--tw-ring-color:rgba(148,163,184,.25)}
.dark .bg-slate-100.text-slate-600{background:#1a2540}
.dark .file\:bg-slate-100::file-selector-button{background:#1a2540}
@media print{aside,header,.no-print{display:none !important} main{padding:0 !important} body{background:#fff}}
</style>
<style type="text/tailwindcss">
@layer components {
  .btn{@apply inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-medium border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-300 transition cursor-pointer}
  .btn-primary{@apply bg-brand text-white border-transparent hover:bg-brand-dark hover:text-white hover:border-transparent}
  .btn-danger{@apply bg-red-600 text-white border-transparent hover:bg-red-700 hover:text-white hover:border-transparent}
  .btn-sm{@apply px-3 py-1.5 text-xs rounded-lg}
  .input{@apply w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm bg-white placeholder:text-slate-400 transition}
  .card{@apply bg-white rounded-2xl border border-slate-200/70 shadow-[0_1px_3px_rgba(15,23,42,.05)]}
  .th{@apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 bg-slate-50/80 whitespace-nowrap}
  .td{@apply px-4 py-3 text-sm text-slate-700 border-t border-slate-100 align-middle}
  tbody tr:hover .td, table tr:hover > .td{@apply bg-slate-50/60}
  .badge{@apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap}
  .link{@apply text-brand font-medium hover:underline}
  .section-title{@apply px-5 py-4 font-semibold text-slate-900 flex items-center gap-2 border-b border-slate-100}
}
</style>
