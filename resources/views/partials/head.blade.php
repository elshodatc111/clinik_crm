<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="{{ $brand['color'] }}">
<meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $brand['name'] }}"><meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<script>
window.__pwa = { e: null };
addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); window.__pwa.e = e; dispatchEvent(new Event('pwa-ready')); });
addEventListener('appinstalled', function () { window.__pwa.e = null; dispatchEvent(new Event('pwa-installed')); });
if ('serviceWorker' in navigator) { addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); }); }
</script>
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
/* ---------- Zamonaviy ko'rinish (polish) ---------- */
body{background:radial-gradient(1200px 500px at 90% -10%,color-mix(in srgb,var(--brand) 7%,transparent),transparent 60%),#f4f6fb;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
::selection{background:color-mix(in srgb,var(--brand) 22%,white)}
:focus-visible{outline:2px solid var(--brand);outline-offset:2px;border-radius:.5rem}
input:focus-visible,textarea:focus-visible,select:focus-visible{outline:none}
*{scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent}
.dark *{scrollbar-color:#2a3a5e transparent}
body .card{border-radius:1.1rem;box-shadow:0 1px 2px rgba(15,23,42,.04),0 6px 20px -10px rgba(15,23,42,.10);transition:box-shadow .2s,transform .2s,border-color .2s}
body a.card:hover{box-shadow:0 10px 30px -12px rgba(15,23,42,.22)}
.dark body .card,.dark body a.card:hover{box-shadow:none}
body .input{border-radius:.8rem;box-shadow:inset 0 1px 1px rgba(15,23,42,.03)}
body .btn{border-radius:.8rem;transition:background .15s,box-shadow .15s,transform .05s,border-color .15s}
body .btn:active{transform:translateY(1px)}
body .btn-primary{background-image:linear-gradient(180deg,color-mix(in srgb,var(--brand) 88%,white),var(--brand));box-shadow:0 6px 16px -8px var(--brand),inset 0 1px 0 rgba(255,255,255,.18)}
body .btn-primary:hover{background-image:linear-gradient(180deg,var(--brand),var(--brand-dark))}
body .btn-sm{border-radius:.65rem}
body .badge{letter-spacing:.01em}
body .section-title{background:linear-gradient(180deg,rgba(248,250,252,.9),rgba(255,255,255,0));font-size:.95rem}
.dark body .section-title{background:none}
header.sticky{background:rgba(255,255,255,.72)!important;backdrop-filter:saturate(180%) blur(16px);-webkit-backdrop-filter:saturate(180%) blur(16px)}
.dark header.sticky{background:rgba(10,16,32,.72)!important}
aside.bg-slate-900{background:linear-gradient(185deg,#0f172a 0%,#101a33 55%,#0c1427 100%)}
aside nav a{position:relative}
aside nav a:not(.nav-active):hover{transform:translateX(2px)}
aside nav a{transition:background .15s,color .15s,transform .15s}
.nav-active{background-image:linear-gradient(135deg,var(--brand),var(--brand-dark));box-shadow:0 8px 20px -8px var(--brand),inset 0 1px 0 rgba(255,255,255,.2)}
main{animation:kfade .35s ease both}
@keyframes kfade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
body table .td{transition:background .12s}
body .th{letter-spacing:.06em}
.hero-grad{background-image:radial-gradient(600px 220px at 85% 0%,rgba(255,255,255,.18),transparent 60%),linear-gradient(135deg,var(--brand) 0%,var(--brand-dark) 100%)}
.line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
@media (max-width:767px){main{padding-bottom:5rem}.card{border-radius:1rem}}
@media print{aside,header,.no-print,[data-html2canvas-ignore]{display:none!important} main{padding:0!important} body{background:#fff!important} .card{box-shadow:none!important;border:0!important}}
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
