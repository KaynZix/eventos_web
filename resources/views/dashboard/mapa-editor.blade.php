<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editor de mapa · {{ $evento->nombre ?? 'Evento' }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .card{ background: rgba(17,24,39,.6); border:1px solid rgba(255,255,255,.08); border-radius:12px; }
    .btn{ padding:.45rem .7rem; border-radius:.5rem; }
    #lienzo{
      background:
        linear-gradient(#ffffff10 1px, transparent 1px),
        linear-gradient(90deg, #ffffff10 1px, transparent 1px);
      background-size: 24px 24px; background-color:#0b0f19;
    }
  </style>
</head>
<body class="bg-neutral-950 text-neutral-100">

<header class="sticky top-0 z-20 bg-neutral-900/80 backdrop-blur border-b border-white/10">
  <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
    <div class="font-black tracking-tight">Editor de mapa</div>
    <div class="flex items-center gap-2 text-sm">
      <a id="btn-volver" href="{{ route('dashboard.eventos.index') }}" class="px-3 py-1.5 rounded bg-neutral-800 hover:bg-neutral-700">← Volver</a>
      <form method="POST" action="{{ route('logout') }}">@csrf
        <button id="btn-salir" class="px-3 py-1.5 rounded bg-red-600 hover:bg-red-500 text-white">Salir</button>
      </form>
    </div>
  </div>
</header>

<div class="max-w-7xl mx-auto p-4 md:p-6 grid grid-cols-1 md:grid-cols-[260px_1fr] gap-6">

  <!-- Lateral -->
  <aside class="card p-4 space-y-4">
    <div>
      <div class="text-xs text-neutral-400">Evento</div>
      <div class="font-bold">{{ $evento->nombre }}</div>
      <div class="text-xs text-neutral-400 mt-1">
        {{ \Illuminate\Support\Carbon::parse($evento->fecha)->format('d/m/Y') }}
        {{ substr($evento->hora_inicio,0,5) }}–{{ substr($evento->hora_termino,0,5) }}
        · Esc {{ $evento->id_escenario }}
      </div>
    </div>

    <div class="space-y-2">
      <div class="text-sm font-semibold">Agregar mesas</div>
      <div class="grid grid-cols-4 gap-2">
        @for($i=1;$i<=8;$i++)
          <button type="button" class="btn bg-neutral-800 hover:bg-neutral-700" data-add-mesa="{{ $i }}">{{ $i }}</button>
        @endfor
      </div>
      <p class="text-xs text-neutral-400">Crea mesas y arrástralas al lugar deseado.</p>
    </div>

    <div class="space-y-2">
      <div class="text-sm font-semibold">Acciones</div>
      <button id="guardar" type="button" class="btn w-full bg-emerald-600 hover:bg-emerald-500 text-white">Guardar cambios</button>
      <button id="limpiar" type="button" class="btn w-full bg-neutral-800 hover:bg-neutral-700">Limpiar</button>
    </div>
  </aside>

  <!-- Lienzo -->
  <section class="card p-3">
    <div class="flex items-center justify-between mb-2">
      <h2 class="font-semibold">Mapa del evento</h2>
      <div class="text-xs text-neutral-400">Escenario {{ $evento->id_escenario }}</div>
    </div>

    <div id="lienzo" class="relative w-full h-[560px] rounded-lg overflow-hidden">
      <!-- SVG de sala (viewBox 1000x700) -->
      <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 1000 700" preserveAspectRatio="xMidYMid meet">
        <polygon id="poly-sala" fill="none" stroke="#5b6475" stroke-width="4"
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

      <!-- Capa de mesas -->
      <div id="mesas-layer" class="absolute inset-0"></div>
    </div>

    <template id="tpl-mesa">
      <div class="mesa absolute select-none cursor-move rounded-md text-xs text-center px-2 py-1"
           style="width:50px;height:42px;background:#374151;border:1px solid #ffffff22;">
        <div class="font-bold">M-##</div>
        <div class="text-[10px] text-neutral-300">##s</div>
      </div>
    </template>
  </section>
</div>

<script>
  const lienzo = document.getElementById('lienzo');
  const layer  = document.getElementById('mesas-layer');
  const tpl    = document.getElementById('tpl-mesa');
  const token  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // ===== Control de cambios sin guardar =====
  let isDirty = false;
  const markDirty  = () => { isDirty = true; };
  const clearDirty = () => { isDirty = false; };

  // Diálogo al salir sólo si hay cambios
  window.addEventListener('beforeunload', (e) => {
    if (!isDirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  // Si tienes estos botones en el header, desactiva el guard al usarlos:
  document.getElementById('btn-volver')?.addEventListener('click', clearDirty);
  document.getElementById('btn-salir')?.addEventListener('click',  clearDirty);

  // ===== Polígono de la sala (coordenadas viewBox 1000x700) =====
  const polySala = [
    [400,80], [980,80], [980,180], [800,300], [800,650],
    [50,650], [50,300], [140,240], [100,165], [240,80], [280,155]
  ];

  // Conversión px ↔ viewBox
  function toViewBox(xPx, yPx) {
    const rect = lienzo.getBoundingClientRect();
    return [ xPx / rect.width * 1000, yPx / rect.height * 700 ];
  }
  function fromViewBox(x, y) {
    const rect = lienzo.getBoundingClientRect();
    return [ x / 1000 * rect.width, y / 700 * rect.height ];
  }

  // Punto en polígono (ray casting)
  function pointInPoly([x,y], poly) {
    let inside = false;
    for (let i=0, j=poly.length-1; i<poly.length; j=i++) {
      const [xi,yi] = poly[i], [xj,yj] = poly[j];
      const intersect = ((yi>y)!==(yj>y)) && (x < (xj-xi)*(y-yi)/(yj-yi)+xi);
      if (intersect) inside = !inside;
    }
    return inside;
  }

  // Un punto aleatorio válido dentro del polígono
  function randomPointInside() {
    for (let t=0; t<200; t++) {
      const vx = 120 + Math.random() * 760;
      const vy = 120 + Math.random() * 460;
      if (pointInPoly([vx,vy], polySala)) return fromViewBox(vx,vy);
    }
    return [20,20];
  }

  // ===== Mesas (UI) =====
  function addMesaNode({sillas, x, y, codigo}) {
    const node = tpl.content.firstElementChild.cloneNode(true);
    node.dataset.sillas = String(sillas);
    node.dataset.codigo = String(codigo || 'M?');

    if (x == null || y == null) [x, y] = randomPointInside();
    node.style.left = (x ?? 12) + 'px';
    node.style.top  = (y ?? 12) + 'px';

    const [lblCod, lblSillas] = node.querySelectorAll('div');
    lblCod.textContent   = node.dataset.codigo;
    lblSillas.textContent = `${sillas} sillas`;

    makeDraggable(node);
    layer.appendChild(node);
    markDirty(); // agregar mesa = cambio
    return node;
  }

  function makeDraggable(el) {
    let offX=0, offY=0, dragging=false, prevX=0, prevY=0;

    el.addEventListener('mousedown', (e) => {
      dragging = true;
      prevX = el.offsetLeft; prevY = el.offsetTop;
      offX = e.clientX - el.offsetLeft;
      offY = e.clientY - el.offsetTop;
      el.style.zIndex = 10;
    });

    window.addEventListener('mousemove', (e) => {
      if (!dragging) return;
      const rect = lienzo.getBoundingClientRect();
      const W = el.offsetWidth, H = el.offsetHeight;
      const x = Math.min(rect.width  - W, Math.max(0, e.clientX - offX));
      const y = Math.min(rect.height - H, Math.max(0, e.clientY - offY));
      el.style.left = x + 'px';
      el.style.top  = y + 'px';
    });

    window.addEventListener('mouseup', () => {
      if (!dragging) return;
      dragging = false; el.style.zIndex = 1;

      const W = el.offsetWidth, H = el.offsetHeight;
      const cx = el.offsetLeft + W/2;
      const cy = el.offsetTop  + H/2;
      const [vx, vy] = toViewBox(cx, cy);

      if (!pointInPoly([vx,vy], polySala)) {
        // vuelve a su posición previa
        el.style.left = prevX + 'px';
        el.style.top  = prevY + 'px';
        return;
      }
      // Sólo marcar sucio si realmente cambió de sitio
      if (el.offsetLeft !== prevX || el.offsetTop !== prevY) markDirty();
    });
  }

  // ===== Cargar existentes desde BD =====
  const existentes = @json($mesas ?? []);
  existentes.forEach((m, idx) => addMesaNode({
    sillas: parseInt(m.sillas, 10),
    x: m.x, y: m.y,
    codigo: 'M' + String(idx + 1).padStart(2, '0')
  }));

  // ===== Botones 1..8 → crear mesa local =====
  document.querySelectorAll('[data-add-mesa]').forEach(btn => {
    btn.addEventListener('click', () => {
      const sillas = parseInt(btn.dataset.addMesa, 10);
      const [rx, ry] = randomPointInside();
      const n = layer.querySelectorAll('.mesa').length + 1;
      addMesaNode({ sillas, x: rx, y: ry, codigo: 'M' + String(n).padStart(2,'0') });
    });
  });

  // ===== Guardar (envía todo el layout) =====
  document.getElementById('guardar').addEventListener('click', async () => {
    const mesas = Array.from(document.querySelectorAll('#mesas-layer .mesa')).map(el => ({
      sillas: parseInt(el.dataset.sillas, 10),
      x: Math.round(el.offsetLeft),
      y: Math.round(el.offsetTop),
    }));

    try {
      const res = await fetch("{{ route('dashboard.eventos.mapa.mesas.save', $evento->id) }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ mesas })
      });

      if (!res.ok) {
        const text = await res.text();
        alert('Error ' + res.status + ': ' + text);
        return;
      }

      let data = {};
      try { data = await res.json(); } catch(_) {}

      if (data.ok) {
        clearDirty();
        alert('Guardado');
      } else {
        alert('No se pudo guardar' + (data.msg ? (': ' + data.msg) : ''));
      }
    } catch (e) {
      console.error(e);
      alert('No se pudo guardar. Revisa la consola.');
    }
  });

  // Limpiar
  document.getElementById('limpiar').addEventListener('click', () => {
    layer.innerHTML = '';
    markDirty();
  });
</script>

</body>
</html>
