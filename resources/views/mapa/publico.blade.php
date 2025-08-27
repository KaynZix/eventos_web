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
    #lienzo{
      background:
        linear-gradient(#ffffff12 1px, transparent 1px),
        linear-gradient(90deg, #ffffff12 1px, transparent 1px);
      background-size: 24px 24px; background-color:#0b0f19;
    }
        .mesa-el { width:56px; height:42px; background:#374151; border:1px solid #ffffff22; }
        .mesa-el.reservada{ background:#7f1d1d; border-color:#ef444433; cursor:not-allowed; }
        .mesa-el.sel{ outline:3px solid #22c55e; }
        .btn{ padding:.55rem .9rem; border-radius:.55rem; font-weight:800; letter-spacing:.04em; }
  </style>
</head>
<body class="bg-neutral-900 text-white">

<header class="sticky top-0 z-20 bg-neutral-900/80 backdrop-blur border-b border-white/10">
  <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
    <div class="font-black tracking-tight">{{ $evento->nombre }}</div>
    <a href="{{ route('eventos.index') }}" class="px-3 py-1.5 rounded bg-neutral-800 hover:bg-neutral-700">← Volver</a>
  </div>
</header>

<div class="max-w-7xl mx-auto p-4 md:p-6 grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6">
  <!-- Mapa -->
  <section class="card p-4">
    <div class="flex items-center justify-between mb-2">
      <div class="text-sm text-neutral-300">
        {{ \Illuminate\Support\Carbon::parse($evento->fecha)->translatedFormat('l d \\de F Y') }}
        • {{ substr($evento->hora_inicio,0,5) }}–{{ substr($evento->hora_termino,0,5) }}
      </div>
      <div class="text-xs text-neutral-400">Escenario {{ $evento->id_escenario }}</div>
    </div>

    <div id="lienzo" class="relative w-full h-[560px] rounded-lg overflow-hidden">
      <!-- SVG PLANO -->
      <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 1000 700" preserveAspectRatio="xMidYMid meet">
        <!-- Contorno -->
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

        <line x1="540" y1="70" x2="540" y2="90" stroke="#5b6475" stroke-width="4" />
        <line x1="580" y1="70" x2="580" y2="90" stroke="#5b6475" stroke-width="4" />

        <line x1="50" y1="300" x2="400" y2="80" stroke="#5b6475" stroke-width="4" opacity=".7" />
        <line x1="50" y1="350" x2="400" y2="130" stroke="#5b6475" stroke-width="4" opacity=".7" />
        <line x1="400" y1="130" x2="600" y2="130" stroke="#5b6475" stroke-width="4" opacity=".7" />

        <line x1="320" y1="180" x2="320" y2="500" stroke="#5b6475" stroke-width="4" opacity=".7" />
        <line x1="50" y1="500" x2="680" y2="500" stroke="#5b6475" stroke-width="6" opacity=".6"/>
        <line x1="50" y1="550" x2="680" y2="550" stroke="#5b6475" stroke-width="6" opacity=".6"/>

        <line x1="820" y1="360" x2="780" y2="360" stroke="#5b6475" stroke-width="4" />
        <line x1="820" y1="420" x2="780" y2="420" stroke="#5b6475" stroke-width="4" />
      </svg>

      <!-- Capa para mesas -->
      <div id="mesas-layer" class="absolute inset-0"></div>
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
                  class="slot btn bg-green-500/90 hover:bg-green-500 text-black">
            {{ $h }}
          </button>
        @endforeach
      </div>
      <div class="mt-2 text-xs text-neutral-300" id="slotLabel">Hora no seleccionada</div>
    </div>

    <div class="border-t border-white/10 pt-3">
      <div class="text-sm text-neutral-300">
        Mesas seleccionadas: <b id="selCount">0</b>
      </div>
      <div class="mt-3 space-y-2">
        <button id="cta-promos" class="btn w-full bg-red-600 hover:bg-red-500 text-white disabled:opacity-50" disabled>
          RESERVAR Y AGREGAR PEDIDO ANTICIPADO
        </button>
        <button id="cta-pago" class="btn w-full bg-green-500 hover:bg-green-400 text-black disabled:opacity-50" disabled>
          SOLO RESERVAR
        </button>
      </div>
      <p class="text-xs text-neutral-400 mt-2">
        * Las promociones no se cobran ahora; sólo quedan como indicativas para el local.
      </p>
    </div>
  </aside>
</div>

<!-- Plantilla de mesa -->
<template id="tpl-mesa">
  <div class="mesa-el absolute rounded-md text-xs text-center px-2 py-1 select-none cursor-pointer">
    <div class="font-bold">M##</div>
    <div class="text-[10px] text-neutral-300">##s</div>
  </div>
</template>

<script>
  const tpl   = document.getElementById('tpl-mesa');
  const layer = document.getElementById('mesas-layer');
  const token = document.querySelector('meta[name="csrf-token"]').content;
  const eventoId = {{ $evento->id }};

  // Mesas desde el backend
  const mesas = @json($mesas);

  // Render mesas
  mesas.forEach((m, idx) => {
    const el = tpl.content.firstElementChild.cloneNode(true);
    el.style.left = (m.x ?? 12) + 'px';
    el.style.top  = (m.y ?? 12) + 'px';
    el.dataset.escenarioId = m.escenario_id;
    el.dataset.sillas      = m.sillas;
    el.dataset.reservada   = String(m.reservada);
    el.querySelectorAll('div')[0].textContent = 'M' + String(idx+1).padStart(2,'0');
    el.querySelectorAll('div')[1].textContent = `${m.sillas} sillas`;
    if (m.reservada == 1) el.classList.add('reservada');
    layer.appendChild(el);
  });

  // Selección de mesas
  const selected = new Set();
  layer.addEventListener('click', (e) => {
    const el = e.target.closest('.mesa-el');
    if (!el) return;
    if (el.dataset.reservada === '1') return;

    const id = parseInt(el.dataset.escenarioId, 10);
    if (selected.has(id)) {
      selected.delete(id);
      el.classList.remove('sel');
    } else {
      selected.add(id);
      el.classList.add('sel');
    }
    document.getElementById('selCount').textContent = selected.size;
    updateCTAs();
  });

  // Slots
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

  async function postStart(flow){
    const res = await fetch("{{ route('reservas.start') }}",{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
      body: JSON.stringify({
        evento_id: eventoId,
        llegada: slotSel,
        mesas: Array.from(selected),
        flow
      })
    });
    const data = await res.json();
    if(!res.ok || !data.ok){ alert(data.msg || 'No se pudo iniciar la reserva'); return; }
    window.location.href = data.redirect;
  }

  document.getElementById('cta-promos').addEventListener('click', ()=> postStart('promos'));
  document.getElementById('cta-pago').addEventListener('click',   ()=> postStart('pago'));
</script>
</body>
</html>
