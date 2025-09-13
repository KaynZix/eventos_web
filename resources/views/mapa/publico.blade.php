<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mapa — {{ $evento->nombre }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    .card{ background: rgba(0,0,0,.55); border:1px solid #ffffff22; border-radius:12px; }
    .map-shell{ overflow:auto; }

    /* Lienzo fijo como en admin (coordenadas 1000x700) */
    #lienzo{
      width:1000px; height:700px; position:relative; border-radius:12px; overflow:hidden;
      background:
        linear-gradient(#ffffff12 1px, transparent 1px),
        linear-gradient(90deg, #ffffff12 1px, transparent 1px);
      background-size: 24px 24px; background-color:#0b0f19;
    }

    #mesas-layer{ position:absolute; inset:0; }

    /* ===================== MESAS (mismo look del admin) ===================== */
    /* //CODIGO_MESAS – Colores / tamaños */
    :root{
      --mesa-stroke: #e5e7eb;
      --mesa-fill:   #0ea5e9;   /* color tablero mesas 1–4 */
      --chair-fill:  #1f2937;   /* color sillas 1–4 */

      --mesa-label-bg: #ffffff; /* mesas 5–8 */
      --mesa-label-fg: #111827;
      --mesa-label-bd: #111827;

      --t12-w: 55px; --t12-h: 30px;   /* 1–2 (horizontal) */
      --t34-w: 45px;  --t34-h: 45px;   /* 3–4 (cuadrada)   */
      --t58-w: 90px; --t58-h: 45px;   /* 5–8 (horizontal) */
    }

    .mesa{
      position:absolute; user-select:none; cursor:pointer;
      display:flex; align-items:center; justify-content:center;
      transform-origin:center center;
      outline:0;
    }
    .mesa.sel{ outline:3px solid #22c55e; }
    .mesa.no-click{ cursor:not-allowed; }

    .mesa .icon{ width:100%; height:100%; display:block; }
    .mesa.t12{ width:var(--t12-w); height:var(--t12-h); }
    .mesa.t34{ width:var(--t34-w); height:var(--t34-h); }
    .mesa.t58{ width:var(--t58-w); height:var(--t58-h); }

    /* Label 5–8 */
    .label-58{
      width:100%; height:100%;
      background: var(--mesa-label-bg);
      color: var(--mesa-label-fg);
      display:flex; align-items:center; justify-content:center;
      border:3px solid var(--mesa-label-bd);
      font-weight:700; font-size:12px;
    }

    /* Estados públicos (solo visual) */
    .mesa.reservada{ --mesa-fill:#7f1d1d; --chair-fill:#4b1d1d; }
    .mesa.hold     { --mesa-fill:#92400e; --chair-fill:#4b2a0e; }

    .badge{
      position:absolute; top:-12px; right:-12px;
      font-size:10px; padding:2px 6px; border-radius:9999px;
      background:#00000090; color:#fff;
      border:1px solid #ffffff30;
    }
  </style>
</head>
<body class="bg-neutral-900 text-white">

<header class="sticky top-0 z-20 bg-neutral-900/80 backdrop-blur border-b border-white/10">
  <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
    <div class="font-black tracking-tight">{{ $evento->nombre }}</div>
    <a href="{{ route('eventos.index') }}" class="px-3 py-1.5 rounded bg-neutral-800 hover:bg-neutral-700">← Volver</a>
  </div>
</header>

<div class="w-full px-4 md:px-6 grid grid-cols-1 lg:grid-cols-[1100px_320px] gap-6">
  <!-- Mapa -->
  <section class="card p-3">
    <div class="flex items-center justify-between mb-2">
      <div class="text-sm text-neutral-300">
        {{ \Illuminate\Support\Carbon::parse($evento->fecha)->translatedFormat('l d \\de F Y') }}
        • {{ substr($evento->hora_inicio,0,5) }}–{{ substr($evento->hora_termino,0,5) }}
      </div>
      <div class="text-xs text-neutral-400">Escenario {{ $evento->id_escenario }}</div>
    </div>

    <div class="map-shell rounded-lg -mx-1 -mb-1">
      <div id="lienzo">
        <!-- PLANO SVG (1000x700) -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 1000 700" preserveAspectRatio="xMidYMid meet">
          <polygon fill="none" stroke="#A3A8B5" stroke-width="4"
            points="400,80 980,80 980,180 800,300 800,650 50,650 50,300 140,240 100,165 240,80 280,155" />
          @if($evento->id_escenario == 1)
            <rect x="600" y="60" width="320" height="80" fill="#b91c1c" opacity="1" />
            <text x="760" y="110" fill="white" font-size="22" text-anchor="middle" font-weight="700">ESCENARIO 1</text>
            <rect x="-90" y="150" width="120" height="400" fill="#5b6475" opacity=".7" />
          @else
            <rect x="-90" y="150" width="120" height="400" fill="#7f1d1d" opacity=".7" />
            <text x="150" y="205" transform="rotate(-90,140,360)" fill="white" font-size="22" text-anchor="middle" font-weight="700">ESCENARIO 2</text>
            <rect x="600" y="60" width="320" height="80" fill="#5b6475" opacity="1" />
          @endif

          <line x1="540" y1="70"  x2="540" y2="90"  stroke="#5b6475" stroke-width="4" />
          <line x1="580" y1="70"  x2="580" y2="90"  stroke="#5b6475" stroke-width="4" />
          <line x1="50"  y1="300" x2="400" y2="80"  stroke="#5b6475" stroke-width="4" opacity=".7" />
          <line x1="50"  y1="350" x2="400" y2="130" stroke="#5b6475" stroke-width="4" opacity=".7" />
          <line x1="400" y1="130" x2="600" y2="130" stroke="#5b6475" stroke-width="4" opacity=".7" />
          <line x1="320" y1="180" x2="320" y2="500" stroke="#5b6475" stroke-width="4" opacity=".7" />
          <line x1="50"  y1="500" x2="680" y2="500" stroke="#5b6475" stroke-width="4" opacity=".6"/>
          <line x1="50"  y1="550" x2="680" y2="550" stroke="#5b6475" stroke-width="4" opacity=".6"/>
 
          <line x1="790" y1="470" x2="680" y2="470" stroke="#027000ff" stroke-width="150" />
          <line x1="740" y1="525" x2="640" y2="525" stroke="#027000ff" stroke-width="40" />
          <line x1="580" y1="525" x2="480" y2="525" stroke="#027000ff" stroke-width="40" />
          <line x1="420" y1="525" x2="320" y2="525" stroke="#027000ff" stroke-width="40" />

          <circle cx="750" cy="250" r="25" fill="green" opacity="1" />
          <circle cx="600" cy="300" r="20" fill="green" opacity="1" />       
          <circle cx="500" cy="300" r="20" fill="green" opacity="1" />       
          <circle cx="400" cy="300" r="20" fill="green" opacity="1" />
          <line x1="820" y1="335" x2="780" y2="335" stroke="#5b6475" stroke-width="4" />
          <line x1="820" y1="385" x2="780" y2="385" stroke="#5b6475" stroke-width="4" />
          
        </svg>

        <!-- Capa para mesas -->
        <div id="mesas-layer"></div>
      </div>
    </div>
  </section>

  <!-- Panel derecho -->
  <aside class="card p-4 space-y-4">
    <div>
      <div class="text-sm font-bold">Indicar hora de llegada</div>
      <div id="slots" class="grid grid-cols-2 gap-2 mt-2">
        @foreach($slots as $h)
          <button type="button"
                  data-slot="{{ $h }}"
                  class="slot px-2 py-1.5 rounded bg-green-500/90 hover:bg-green-500 text-black">
            {{ $h }}
          </button>
        @endforeach
      </div>
      <div class="mt-2 text-xs text-neutral-300" id="slotLabel">Hora no seleccionada</div>
    </div>

    <div class="border-t border-white/10 pt-3">
      <!-- <div class="text-sm text-neutral-300">
        Mesas seleccionadas: <b id="selCount">0</b>
      </div> -->
      <div class="mt-2 text-sm">
        <span class="font-semibold">Mesas seleccionadas: </span>
        <span id="selCount">0</span>

        <span class="ml-4 font-semibold">Valor total: </span>
        <span id="valorTotal">$0</span>

        <span class="ml-4 text-neutral-400">[ <span id="selList"></span> ]</span>
      </div>

      <!-- Tooltip flotante para el hover de precio -->
      <div id="hoverPrecioBox"
          class="hidden fixed z-50 rounded-md border border-white/10 bg-black/70 px-2 py-1 text-xs pointer-events-none">
        <span>Mesa <b id="hoverMesaId"></b> · <b id="hoverMesaPrecio"></b></span>
      </div>

      <div class="mt-3 space-y-2">
        <a href="" id="cta-promos"></a>
        <!-- <button id="cta-promos" class="w-full px-3 py-2 rounded bg-red-600 hover:bg-red-500 text-white disabled:opacity-50" disabled>
          RESERVAR Y AGREGAR PEDIDO ANTICIPADO
        </button> -->
        <button id="cta-pago" class="w-full px-3 py-2 rounded bg-green-500 hover:bg-green-400 text-black disabled:opacity-50" disabled>
          RESERVAR
        </button>
      </div>
      <p class="text-xs text-neutral-400 mt-2">
        * Las promociones no se cobran ahora; sólo quedan como indicativas para el local.
      </p>
    </div>
  </aside>
</div>

<script>
  const token     = document.querySelector('meta[name="csrf-token"]').content;
  const layer     = document.getElementById('mesas-layer');
  const eventoId  = {{ $evento->id }};
  const statusUrl = "{{ route('mapa.publico.status', $evento->id) }}";
  const fmtCLP = new Intl.NumberFormat('es-CL', { style:'currency', currency:'CLP', maximumFractionDigits: 0 });

  // Mesas desde backend (deben traer: escenario_id, sillas, x, y, rot, reservada, bloqueada, hold_left)
  const mesas = @json($mesas ?? []);

  /* ====== helpers visuales para las sillas (igual que admin) ====== */
  function svg12(n){ //1–2
    return `
      <svg class="icon" viewBox="0 0 120 70">
        <rect x="30" y="10" rx="12" ry="12" width="60" height="50"
              fill="var(--mesa-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>
        ${n>=1 ? `<rect x="5"  y="27" width="18" height="16" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
        ${n>=2 ? `<rect x="97" y="27" width="18" height="16" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
      </svg>`;
  }
  function svg34(n){ //3–4 cuadrada
    return `
      <svg class="icon" viewBox="0 0 90 90">
        <rect x="20" y="20" rx="10" ry="10" width="50" height="50"
              fill="var(--mesa-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>
        ${n>=1 ? `<rect x="38" y="4"  width="14" height="12" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
        ${n>=2 ? `<rect x="38" y="74" width="14" height="12" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
        ${n>=3 ? `<rect x="4"  y="38" width="12" height="14" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
        ${n>=4 ? `<rect x="74" y="38" width="12" height="14" fill="var(--chair-fill)" stroke="var(--mesa-stroke)" stroke-width="3"/>` : ``}
      </svg>`;
  }
  function label58(n){ return `<div class="label-58">Mesa de ${n}</div>`; }

  function sizeClassFor(n){ return (n<=2) ? 't12' : (n<=4) ? 't34' : 't58'; }
  function buildMesaContent(sillas){
    if (sillas<=2) return svg12(sillas);
    if (sillas<=4) return svg34(sillas);
    return label58(sillas);
  }

  /* ====== estados ====== */
  function fmtMMSS(sec){
    sec = Math.max(0, parseInt(sec||0,10));
    const m = Math.floor(sec/60), s = sec%60;
    return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
  }
  function applyState(el, {reservada, bloqueada, hold_left}){
    el.classList.remove('reservada','hold','no-click');
    let badge = el.querySelector('.badge');
    if (!badge) {
      badge = document.createElement('div');
      badge.className = 'badge';
      el.appendChild(badge);
    }
    badge.textContent = '';
    badge.style.display = 'none';

    if (Number(reservada) === 1){
      el.classList.add('reservada','no-click');
      badge.textContent = 'Reservada';
      badge.style.background = '#ef4444';
      badge.style.display = 'block';
      return;
    }
    if (Number(bloqueada) === 1 && Number(hold_left) > 0){
      el.classList.add('hold','no-click');
      badge.textContent = fmtMMSS(hold_left);
      badge.style.background = '#f59e0b';
      badge.style.display = 'block';
      return;
    }
  }

  /* ====== render inicial ====== */
  const mesasMap = new Map();                 // key: escenario_id → element
  mesas.forEach(m=>{
    const el  = document.createElement('div');
    el.className = `mesa ${sizeClassFor(+m.sillas)}`;
    el.dataset.id      = String(m.escenario_id);
    el.dataset.sillas  = String(m.sillas);
    el.dataset.precio  = String(m.precio ?? 0);   // <--- NUEVO
    el.style.left      = (m.x|0)+'px';
    el.style.top       = (m.y|0)+'px';
    el.style.transform = `rotate(${(m.rot|0)}deg)`;
    el.innerHTML = buildMesaContent(+m.sillas);

    applyState(el, {
      reservada: Number(m.reservada||0),
      bloqueada: Number(m.bloqueada||0),
      hold_left: Number(m.hold_left||0)
    });

    layer.appendChild(el);
    mesasMap.set(String(m.escenario_id), el);
  });

  /* ====== selección ====== */
  const selected = new Set();
  const elTotal    = document.getElementById('valorTotal');
  const elSelList  = document.getElementById('selList');
    
  function computeTotal() {
    let sum = 0;
    selected.forEach(id => {
      const el = mesasMap.get(String(id));
      sum += Number(el?.dataset.precio || 0);
    });
    return sum;
  }

  function updateSelectedUI() {
    elTotal.textContent = fmtCLP.format(computeTotal());
    elSelList.textContent = [...selected].join(', ');
  }

  function updateCount(){ document.getElementById('selCount').textContent = selected.size; updateSelectedUI(); updateCTAs(); }

  layer.addEventListener('click', (e)=>{
    const el = e.target.closest('.mesa'); if(!el) return;
    if (el.classList.contains('no-click')) return;

    const id = el.dataset.id;
    if (selected.has(id)){ selected.delete(id); el.classList.remove('sel'); }
    else { selected.add(id); el.classList.add('sel'); }
    updateCount();
  });

  function ensureSelectionConsistency(el){
    if (!el.classList.contains('sel')) return;
    if (el.classList.contains('no-click')) {
      selected.delete(el.dataset.id);
      el.classList.remove('sel');
      updateCount();
    }
  }

  /* ====== slots & CTAs ====== */
  let slotSel = null;
  document.querySelectorAll('.slot').forEach(b=>{
    b.addEventListener('click', ()=>{
      document.querySelectorAll('.slot').forEach(x=>x.classList.remove('ring','ring-4','ring-white'));
      b.classList.add('ring','ring-4','ring-white');
      slotSel = b.dataset.slot;
      document.getElementById('slotLabel').textContent = `Llegada: ${slotSel} hrs`;
      updateCTAs();
    });
  });
  function updateCTAs(){
    const ok = (selected.size>0 && !!slotSel);
    document.getElementById('cta-promos').disabled = !ok;
    document.getElementById('cta-pago').disabled   = !ok;
  }

  /* ====== polling estado en vivo ====== */
  async function pollStatus(){
    try{
      const res = await fetch(statusUrl, {headers:{'Accept':'application/json'}});
      if (!res.ok) return;
      const rows = await res.json();
      rows.forEach(r=>{
        const el = mesasMap.get(String(r.escenario_id));
        if (!el) return;
        applyState(el, {
          reservada: Number(r.reservada),
          bloqueada: Number(r.bloqueada),
          hold_left: Number(r.hold_left||0),
        });
        ensureSelectionConsistency(el);
      });
    }catch(_){}
  }
  setInterval(pollStatus, 5000);

  const hoverBox    = document.getElementById('hoverPrecioBox');
  const hoverMesaId = document.getElementById('hoverMesaId');
  const hoverPrecio = document.getElementById('hoverMesaPrecio');

  document.querySelectorAll('.mesa').forEach(el => {
    el.addEventListener('mouseenter', () => {
      hoverMesaId.textContent = el.dataset.id;
      hoverPrecio.textContent = fmtCLP.format(Number(el.dataset.precio || 0));
      hoverBox.classList.remove('hidden');
    });
    el.addEventListener('mousemove', (e) => {
      hoverBox.style.left = (e.pageX + 12) + 'px';
      hoverBox.style.top  = (e.pageY + 12) + 'px';
    });
    el.addEventListener('mouseleave', () => {
      hoverBox.classList.add('hidden');
    });
  });

  /* ====== iniciar reserva ====== */
  async function postStart(flow){
  const total = computeTotal();

    const res = await fetch("{{ route('reservas.start') }}",{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
      body: JSON.stringify({
        evento_id: eventoId,
        llegada: slotSel,
        mesas: Array.from(selected).map(Number),
        total,
        flow
      })
    });
    let data = {};
    try { data = await res.json(); } catch(_){}
    if(!res.ok || !data.ok){
      alert(data.msg || 'No se pudo iniciar la reserva');
      pollStatus();
      return;
    }
    window.location.href = data.redirect;
  }
  document.getElementById('cta-promos').addEventListener('click', ()=> postStart('promos'));
  document.getElementById('cta-pago').addEventListener('click',   ()=> postStart('pago'));
</script>
</body>
</html>
