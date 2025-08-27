<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>{{ $evento->nombre }} – Mapa de mesas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .card{ background: rgba(17,24,39,.6); border:1px solid rgba(255,255,255,.08); border-radius:12px; }
    .btn{ padding:.5rem .8rem; border-radius:.6rem; }
    #lienzo{
      background:
        linear-gradient(#ffffff10 1px, transparent 1px),
        linear-gradient(90deg, #ffffff10 1px, transparent 1px);
      background-size: 24px 24px; background-color:#0b0f19;
    }
  </style>
</head>
<body class="bg-neutral-950 text-neutral-100">

  @include('partials.header')

  <main class="max-w-7xl mx-auto px-4 py-6 grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6">

    <!-- Lienzo / mapa -->
    <section class="card p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h1 class="text-xl font-extrabold">{{ $evento->nombre }}</h1>
          <div class="text-sm text-neutral-300">
            {{ \Illuminate\Support\Carbon::parse($evento->fecha)->translatedFormat('l d \\d\\e F Y') }}
            • {{ substr($evento->hora_inicio,0,5) }}–{{ substr($evento->hora_termino,0,5) }}
          </div>
        </div>
        <a href="{{ route('eventos.index') }}" class="text-sm text-neutral-300 hover:text-white">← Volver</a>
      </div>

      <div id="lienzo" class="relative w-full h-[560px] rounded-lg overflow-hidden">
        <!-- Dibujo del recinto (mismo que admin, sólo lectura) -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 1000 700" preserveAspectRatio="xMidYMid meet">
          <!-- Polígono general -->
          <polygon fill="none" stroke="#5b6475" stroke-width="4"
            points="
              400,80 980,80 980,180 800,300 800,650
              50,650 50,300 140,240 100,165 240,80 280,155
            " />
          <!-- Escenario -->
          <rect x="730" y="100" width="230" height="70" fill="#b91c1c" opacity=".9" />
          <text x="845" y="145" fill="white" font-size="20" text-anchor="middle" font-weight="700">ESCENARIO</text>

          <!-- Líneas interiores (opcionales) -->
          <line x1="580" y1="70" x2="580" y2="95" stroke="#5b6475" stroke-width="4"/>
          <line x1="540" y1="70" x2="540" y2="95" stroke="#5b6475" stroke-width="4"/>
          <line x1="320" y1="200" x2="320" y2="650" stroke="#5b6475" stroke-width="4" opacity=".6"/>
          <line x1="160" y1="520" x2="760" y2="520" stroke="#5b6475" stroke-width="6" opacity=".5"/>
          <line x1="160" y1="550" x2="760" y2="550" stroke="#5b6475" stroke-width="6" opacity=".5"/>
        </svg>

        <!-- Mesas -->
        <div id="mesas-layer" class="absolute inset-0"></div>
      </div>
    </section>

    <!-- Sidebar: selección y slots -->
    <aside class="card p-4 space-y-4">
      <div>
        <div class="text-sm font-semibold">Indicar hora de llegada</div>
        <div class="grid grid-cols-3 gap-2 mt-2">
          @foreach($slots as $h)
            <button type="button"
                    data-slot="{{ $h }}"
                    class="slot btn text-sm bg-neutral-800 hover:bg-neutral-700">
              {{ $h }}
            </button>
          @endforeach
        </div>
      </div>

      <div>
        <div class="text-sm font-semibold mb-2">Tu selección</div>
        <ul id="sel-list" class="space-y-1 text-sm text-neutral-300"></ul>
        <div class="mt-2 text-sm">
          Total sillas: <span id="sel-sillas" class="font-bold">0</span>
        </div>
      </div>

      <button id="btn-reservar" class="btn w-full bg-red-600 hover:bg-red-500 text-white disabled:opacity-50" disabled>
        Continuar reserva
      </button>
      <p class="text-xs text-neutral-400">
        La selección no genera pago en línea. Tus consumos se registran y se pagan en el local.
      </p>
    </aside>

  </main>

  <template id="tpl-mesa">
    <button type="button"
            class="mesa absolute rounded-md text-xs text-center px-2 py-1"
            style="width:72px;height:42px;background:#374151;border:1px solid #ffffff22;">
      <div class="font-bold">M-##</div>
      <div class="text-[10px] text-neutral-300">##s</div>
    </button>
  </template>

  <script>
    const layer   = document.getElementById('mesas-layer');
    const tpl     = document.getElementById('tpl-mesa');
    const selList = document.getElementById('sel-list');
    const selSillas = document.getElementById('sel-sillas');
    const btnReservar = document.getElementById('btn-reservar');

    // Mesas desde el servidor
    const MESAS = @json($mesas);

    // Estado selección
    const selected = new Map(); // mesa_id -> {codigo, sillas}

    function renderSelection(){
      selList.innerHTML = '';
      let tot = 0;
      selected.forEach((v) => {
        tot += v.sillas;
        const li = document.createElement('li');
        li.textContent = `${v.codigo} — ${v.sillas} sillas`;
        selList.appendChild(li);
      });
      selSillas.textContent = tot;
      btnReservar.disabled = selected.size === 0;
    }

    function addMesaNode(m, idx){
      const node = tpl.content.firstElementChild.cloneNode(true);
      const codigo = 'M' + String(idx+1).padStart(2,'0');

      node.style.left = (m.x ?? 10) + 'px';
      node.style.top  = (m.y ?? 10) + 'px';
      node.querySelectorAll('div')[0].textContent = codigo;
      node.querySelectorAll('div')[1].textContent = `${m.sillas} sillas`;

      if (parseInt(m.reservada,10) === 1){
        node.classList.add('opacity-50','cursor-not-allowed');
        node.disabled = true;
        node.title = 'Reservada';
      } else {
        node.addEventListener('click', () => {
          if (selected.has(m.mesa_id)) {
            selected.delete(m.mesa_id);
            node.classList.remove('ring-2','ring-amber-400');
          } else {
            selected.set(m.mesa_id, { codigo, sillas: parseInt(m.sillas,10) });
            node.classList.add('ring-2','ring-amber-400');
          }
          renderSelection();
        });
      }

      layer.appendChild(node);
    }

    MESAS.forEach((m, i) => addMesaNode(m, i));

    // Continuar (por ahora sólo mostramos lo seleccionado)
    btnReservar.addEventListener('click', () => {
      const hora = document.querySelector('.slot.active')?.dataset.slot || '';
      const mesas = Array.from(selected.entries()).map(([id, v]) => ({ id, ...v }));
      alert(`Mesas seleccionadas:\n` + mesas.map(x=>`${x.codigo} (${x.sillas})`).join(', ')
            + (hora? `\nHora llegada: ${hora}` : ''));
      // Aquí rediriges a tu flujo de reserva/pago si corresponde.
    });

    // Slots UI
    document.querySelectorAll('.slot').forEach(b=>{
      b.addEventListener('click', ()=>{
        document.querySelectorAll('.slot').forEach(x=>x.classList.remove('active','bg-red-600'));
        b.classList.add('active','bg-red-600');
      });
    });
  </script>
</body>
</html>
