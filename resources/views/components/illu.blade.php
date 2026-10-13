@props(['name' => 'empty', 'class' => 'w-full h-full'])
@once
<style>
.il-b{fill:var(--brand)} .il-bd{fill:var(--brand-dark)} .il-bs{fill:var(--brand-soft)} .il-bl{fill:color-mix(in srgb,var(--brand) 22%,white)} .il-bm{fill:color-mix(in srgb,var(--brand) 45%,white)}
.il-bst{stroke:var(--brand)} .il-w{fill:#fff} .il-skin{fill:#f6c8a4} .il-skin2{fill:#d9a07a} .il-hair{fill:#3b2a20} .il-hair2{fill:#e2b36b} .il-g{fill:#10b981} .il-gl{fill:#d1fae5} .il-y{fill:#f59e0b} .il-yl{fill:#fef3c7} .il-r{fill:#ef4444} .il-rl{fill:#fee2e2}
.il-s1{fill:#e2e8f0} .il-s2{fill:#cbd5e1} .il-s3{fill:#94a3b8} .il-s4{fill:#475569} .il-s5{fill:#1e293b} .il-v{fill:#8b5cf6} .il-vl{fill:#ede9fe}
.dark .il-w{fill:#e8eefc} .dark .il-s1{fill:#1b2742} .dark .il-s2{fill:#2a3a5e} .dark .il-bs{fill:#14213d} .dark .il-bl{fill:#1a2c52}
</style>
@endonce
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 240" class="{{ $class }}" role="img" aria-hidden="true">
@switch($name)
@case('hero')
    <ellipse cx="160" cy="214" rx="130" ry="12" class="il-s1"/>
    <circle cx="70" cy="70" r="46" class="il-bs"/><circle cx="262" cy="52" r="26" class="il-bl"/>
    {{-- bino --}}
    <rect x="86" y="60" width="148" height="150" rx="10" class="il-w"/><rect x="86" y="60" width="148" height="150" rx="10" fill="none" class="il-s2" style="stroke:#cbd5e1;stroke-width:2"/>
    <rect x="86" y="60" width="148" height="26" rx="10" class="il-b"/><rect x="86" y="74" width="148" height="12" class="il-b"/>
    <rect x="148" y="30" width="24" height="52" rx="4" class="il-bd"/><rect x="153" y="38" width="14" height="4" class="il-w"/><rect x="158" y="33" width="4" height="14" class="il-w" style="display:none"/>
    <rect x="152" y="36" width="16" height="16" rx="3" class="il-w"/><rect x="158" y="38" width="4" height="12" class="il-b"/><rect x="154" y="42" width="12" height="4" class="il-b"/>
    @foreach([0,1,2] as $r)@foreach([0,1,2] as $c)<rect x="{{ 102 + $c*40 }}" y="{{ 98 + $r*30 }}" width="26" height="20" rx="3" class="{{ ($r+$c)%2 ? 'il-bl' : 'il-bs' }}"/>@endforeach @endforeach
    <rect x="141" y="168" width="38" height="42" rx="4" class="il-b"/><rect x="146" y="173" width="13" height="37" class="il-bm"/><rect x="161" y="173" width="13" height="37" class="il-bm"/>
    {{-- doctor --}}
    <g transform="translate(236 118)"><rect x="-4" y="60" width="16" height="30" rx="4" class="il-s4"/><rect x="18" y="60" width="16" height="30" rx="4" class="il-s4"/>
      <rect x="-12" y="16" width="56" height="52" rx="14" class="il-w" style="stroke:#cbd5e1;stroke-width:2"/><rect x="12" y="20" width="8" height="44" class="il-bl"/>
      <circle cx="16" cy="-2" r="16" class="il-skin"/><path d="M0 -4c0-14 12-18 22-14 8 3 10 10 10 14-6-8-18-10-32 0z" class="il-hair"/>
      <path d="M2 24c-6 10-4 22 8 24" fill="none" style="stroke:#475569;stroke-width:3;stroke-linecap:round"/><circle cx="12" cy="50" r="4" class="il-b"/></g>
    {{-- floating cards --}}
    <g><rect x="20" y="120" width="84" height="44" rx="10" class="il-w" style="stroke:#e2e8f0;stroke-width:2"/><circle cx="40" cy="142" r="11" class="il-gl"/><path d="M34 142l4 4 8-8" fill="none" style="stroke:#10b981;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round"/><rect x="58" y="132" width="36" height="6" rx="3" class="il-s2"/><rect x="58" y="145" width="26" height="6" rx="3" class="il-s1"/></g>
    <g><rect x="240" y="40" width="70" height="40" rx="10" class="il-w" style="stroke:#e2e8f0;stroke-width:2"/><path d="M250 68l10-10 8 6 12-14 20 10" fill="none" class="il-bst" style="stroke-width:3;stroke-linecap:round;stroke-linejoin:round"/></g>
@break
@case('doctor')
    <ellipse cx="160" cy="214" rx="110" ry="10" class="il-s1"/><circle cx="160" cy="112" r="90" class="il-bs"/>
    <rect x="110" y="150" width="100" height="64" rx="24" class="il-w" style="stroke:#cbd5e1;stroke-width:2"/><rect x="152" y="152" width="16" height="62" class="il-bl"/>
    <rect x="146" y="128" width="28" height="30" rx="8" class="il-skin"/><circle cx="160" cy="104" r="32" class="il-skin"/>
    <path d="M128 100c-2-30 22-40 42-34 14 4 22 16 22 34-8-14-22-18-40-16-10 2-18 6-24 16z" class="il-hair"/>
    <circle cx="148" cy="108" r="3" class="il-s5"/><circle cx="172" cy="108" r="3" class="il-s5"/><path d="M152 122q8 7 16 0" fill="none" style="stroke:#8a4b2d;stroke-width:2.5;stroke-linecap:round"/>
    <path d="M132 162c-14 10-14 34 6 38" fill="none" style="stroke:#475569;stroke-width:4;stroke-linecap:round"/><circle cx="141" cy="202" r="6" class="il-b"/>
    <rect x="218" y="132" width="52" height="68" rx="8" class="il-w" style="stroke:#cbd5e1;stroke-width:2"/><rect x="232" y="126" width="24" height="12" rx="4" class="il-b"/>
    <rect x="228" y="152" width="32" height="5" rx="2.5" class="il-s2"/><rect x="228" y="164" width="24" height="5" rx="2.5" class="il-s1"/><path d="M230 184l6 6 12-12" fill="none" style="stroke:#10b981;stroke-width:3;stroke-linecap:round;stroke-linejoin:round"/>
    <g transform="translate(52 78)"><rect width="46" height="46" rx="12" class="il-w" style="stroke:#e2e8f0;stroke-width:2"/><rect x="19" y="10" width="8" height="26" rx="2" class="il-r"/><rect x="10" y="19" width="26" height="8" rx="2" class="il-r"/></g>
@break
@case('reception')
    <ellipse cx="160" cy="214" rx="120" ry="10" class="il-s1"/><circle cx="170" cy="110" r="88" class="il-bs"/>
    <rect x="36" y="150" width="248" height="14" rx="6" class="il-b"/><rect x="48" y="164" width="224" height="50" rx="6" class="il-bd"/><rect x="62" y="176" width="60" height="6" rx="3" class="il-bm"/>
    <rect x="186" y="96" width="82" height="54" rx="6" class="il-s4"/><rect x="192" y="102" width="70" height="42" rx="3" class="il-w"/><rect x="198" y="110" width="30" height="5" rx="2.5" class="il-bm"/><rect x="198" y="120" width="52" height="5" rx="2.5" class="il-s1"/><rect x="198" y="130" width="40" height="5" rx="2.5" class="il-s1"/><rect x="216" y="150" width="14" height="6" class="il-s3" style="display:none"/>
    <circle cx="110" cy="92" r="24" class="il-skin"/><path d="M86 90c-2-24 22-32 38-24 10 5 12 14 10 24-8-10-22-12-48 0z" class="il-hair2"/><circle cx="102" cy="94" r="2.5" class="il-s5"/><circle cx="118" cy="94" r="2.5" class="il-s5"/><path d="M104 104q6 5 12 0" fill="none" style="stroke:#8a4b2d;stroke-width:2.5;stroke-linecap:round"/>
    <path d="M70 150c0-26 18-40 40-40s40 14 40 40z" class="il-b"/><path d="M96 112l14 18 14-18" class="il-w"/>
    <g transform="translate(44 58)"><rect width="64" height="34" rx="9" class="il-w" style="stroke:#e2e8f0;stroke-width:2"/><circle cx="17" cy="17" r="8" class="il-gl"/><rect x="31" y="11" width="25" height="5" rx="2.5" class="il-s2"/><rect x="31" y="20" width="17" height="5" rx="2.5" class="il-s1"/></g>
@break
@case('cashier')
    <ellipse cx="160" cy="214" rx="120" ry="10" class="il-s1"/><circle cx="160" cy="112" r="90" class="il-bs"/>
    <rect x="80" y="110" width="160" height="96" rx="12" class="il-s4"/><rect x="92" y="122" width="136" height="38" rx="6" class="il-w"/><text x="160" y="148" text-anchor="middle" font-family="Inter,Arial" font-weight="700" font-size="20" class="il-b">125 000</text>
    @foreach([0,1,2,3,4] as $c)<rect x="{{ 94 + $c*28 }}" y="170" width="22" height="14" rx="3" style="fill:{{ $c == 4 ? '#10b981' : '#64748b' }}"/><rect x="{{ 94 + $c*28 }}" y="188" width="22" height="12" rx="3" style="fill:#64748b"/>@endforeach
    <g transform="translate(222 52) rotate(10)"><rect width="76" height="48" rx="8" class="il-b"/><rect y="10" width="76" height="9" class="il-bd"/><rect x="8" y="30" width="26" height="6" rx="3" class="il-bm"/></g>
    <g transform="translate(34 66)"><circle cx="20" cy="20" r="18" class="il-y"/><circle cx="20" cy="20" r="12" class="il-yl"/><text x="20" y="25" text-anchor="middle" font-size="14" font-weight="700" font-family="Arial" style="fill:#f59e0b">$</text></g>
    <g transform="translate(60 110)"><circle cx="16" cy="16" r="14" class="il-y"/><circle cx="16" cy="16" r="9" class="il-yl"/></g>
@break
@case('lab')
    <ellipse cx="160" cy="214" rx="120" ry="10" class="il-s1"/><circle cx="160" cy="112" r="90" class="il-bs"/>
    <rect x="52" y="196" width="216" height="10" rx="5" class="il-s2"/>
    @foreach([['x'=>82,'c'=>'il-r','h'=>46],['x'=>112,'c'=>'il-b','h'=>60],['x'=>142,'c'=>'il-g','h'=>36],['x'=>172,'c'=>'il-y','h'=>52]] as $t)
        <rect x="{{ $t['x'] }}" y="{{ 80 }}" width="22" height="116" rx="11" class="il-w" style="stroke:#94a3b8;stroke-width:2.5"/><rect x="{{ $t['x']+3 }}" y="{{ 196-$t['h']-6 }}" width="16" height="{{ $t['h'] }}" rx="8" class="{{ $t['c'] }}" opacity=".85"/><rect x="{{ $t['x']-3 }}" y="76" width="28" height="8" rx="4" class="il-s3"/>
    @endforeach
    <g transform="translate(212 84)"><rect x="30" y="86" width="46" height="26" rx="6" class="il-s4"/><path d="M40 86V60a22 22 0 0 1 22-22" fill="none" style="stroke:#475569;stroke-width:10;stroke-linecap:round"/><rect x="48" y="14" width="14" height="44" rx="5" class="il-b" transform="rotate(-24 55 36)"/><circle cx="26" cy="72" r="7" class="il-b" style="display:none"/></g>
    <g transform="translate(48 56)"><rect width="46" height="30" rx="9" class="il-w" style="stroke:#e2e8f0;stroke-width:2"/><path d="M8 18h8l4-8 6 14 4-6h8" fill="none" class="il-bst" style="stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round"/></g>
@break
@case('director')
    <ellipse cx="160" cy="214" rx="120" ry="10" class="il-s1"/><circle cx="160" cy="110" r="90" class="il-bs"/>
    <rect x="58" y="52" width="204" height="130" rx="12" class="il-s4"/><rect x="66" y="60" width="188" height="114" rx="6" class="il-w"/>
    <rect x="148" y="182" width="24" height="18" class="il-s3"/><rect x="120" y="198" width="80" height="8" rx="4" class="il-s2"/>
    @foreach([30,52,40,70,58,86] as $i=>$h)<rect x="{{ 80 + $i*16 }}" y="{{ 150-$h }}" width="10" height="{{ $h }}" rx="3" class="{{ $i%2 ? 'il-bm' : 'il-b' }}"/>@endforeach
    <circle cx="214" cy="104" r="30" class="il-bl"/><path d="M214 104V74a30 30 0 0 1 26 44z" class="il-b"/><path d="M214 104l26 14a30 30 0 0 1-26 16z" class="il-y"/>
    <g transform="translate(30 40)"><rect width="56" height="34" rx="9" class="il-w" style="stroke:#e2e8f0;stroke-width:2"/><path d="M10 24l9-9 7 5 14-12" fill="none" style="stroke:#10b981;stroke-width:3;stroke-linecap:round;stroke-linejoin:round"/></g>
@break
@case('admin')
    <ellipse cx="160" cy="214" rx="120" ry="10" class="il-s1"/><circle cx="160" cy="110" r="90" class="il-bs"/>
    <path d="M160 40l64 22v52c0 40-28 66-64 80-36-14-64-40-64-80V62z" class="il-b"/><path d="M160 54l50 17v43c0 32-22 54-50 65-28-11-50-33-50-65V71z" class="il-bd"/>
    <circle cx="160" cy="112" r="20" class="il-w"/><circle cx="160" cy="112" r="8" class="il-b"/>
    @foreach([0,45,90,135,180,225,270,315] as $a)<rect x="157" y="84" width="6" height="10" rx="2" class="il-w" transform="rotate({{ $a }} 160 112)"/>@endforeach
    <g transform="translate(34 150)"><circle cx="16" cy="12" r="10" class="il-skin"/><path d="M0 40c0-14 7-20 16-20s16 6 16 20z" class="il-v"/></g>
    <g transform="translate(250 60)"><circle cx="16" cy="12" r="10" class="il-skin2"/><path d="M0 40c0-14 7-20 16-20s16 6 16 20z" class="il-g"/></g>
@break
@case('booking')
    <ellipse cx="160" cy="214" rx="100" ry="10" class="il-s1"/><circle cx="160" cy="112" r="92" class="il-bs"/>
    <rect x="106" y="26" width="108" height="188" rx="18" class="il-s5"/><rect x="113" y="38" width="94" height="164" rx="8" class="il-w"/><rect x="146" y="30" width="28" height="4" rx="2" class="il-s3"/>
    <rect x="113" y="38" width="94" height="30" class="il-b"/><rect x="123" y="49" width="44" height="8" rx="4" class="il-w"/>
    @foreach([0,1,2] as $r)@foreach([0,1,2,3] as $c)<rect x="{{ 121 + $c*20 }}" y="{{ 78 + $r*20 }}" width="16" height="14" rx="3" class="{{ ($r==1 && $c==2) ? 'il-b' : 'il-s1' }}"/>@endforeach @endforeach
    <rect x="121" y="146" width="78" height="22" rx="8" class="il-g"/><path d="M148 157l5 5 9-9" fill="none" style="stroke:#fff;stroke-width:3;stroke-linecap:round;stroke-linejoin:round"/><rect x="121" y="174" width="50" height="6" rx="3" class="il-s2"/>
    <g transform="translate(222 70)"><rect width="74" height="40" rx="10" class="il-w" style="stroke:#e2e8f0;stroke-width:2"/><rect x="10" y="10" width="20" height="20" rx="6" class="il-gl"/><rect x="38" y="12" width="26" height="5" rx="2.5" class="il-s2"/><rect x="38" y="22" width="18" height="5" rx="2.5" class="il-s1"/></g>
@break
@case('team')
    <ellipse cx="160" cy="214" rx="120" ry="10" class="il-s1"/><circle cx="160" cy="112" r="90" class="il-bs"/>
    @foreach([['x'=>76,'s'=>'il-skin','c'=>'il-b','h'=>'il-hair'],['x'=>160,'s'=>'il-skin2','c'=>'il-g','h'=>'il-hair'],['x'=>244,'s'=>'il-skin','c'=>'il-v','h'=>'il-hair2']] as $i=>$p)
        <g transform="translate({{ $p['x'] }} {{ $i==1 ? 84 : 104 }})"><circle cx="0" cy="0" r="24" class="{{ $p['s'] }}"/><path d="M-24 -2c0-20 14-26 26-24 12 2 22 10 22 24-8-10-20-12-48 0z" class="{{ $p['h'] }}"/><path d="M-38 {{ $i==1 ? 100 : 80 }}c0-30 14-46 38-46s38 16 38 46z" class="{{ $p['c'] }}"/><circle cx="-8" cy="2" r="2.5" class="il-s5"/><circle cx="8" cy="2" r="2.5" class="il-s5"/></g>
    @endforeach
@break
@default
    <ellipse cx="160" cy="206" rx="90" ry="10" class="il-s1"/><circle cx="160" cy="106" r="82" class="il-bs"/>
    <rect x="110" y="52" width="100" height="130" rx="10" class="il-w" style="stroke:#cbd5e1;stroke-width:2"/><rect x="138" y="44" width="44" height="18" rx="6" class="il-b"/>
    <rect x="124" y="82" width="72" height="7" rx="3.5" class="il-s2"/><rect x="124" y="98" width="52" height="7" rx="3.5" class="il-s1"/><rect x="124" y="114" width="62" height="7" rx="3.5" class="il-s1"/>
    <circle cx="198" cy="160" r="22" class="il-b"/><path d="M189 160l7 7 12-14" fill="none" style="stroke:#fff;stroke-width:3.5;stroke-linecap:round;stroke-linejoin:round"/>
@endswitch
</svg>
