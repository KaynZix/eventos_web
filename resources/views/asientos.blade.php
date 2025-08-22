<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mapa de Mesas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen flex flex-col">

  @include('partials.header')

  <main class="flex-grow">
    <div class="container mx-auto p-4 grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- ====== MAPA ====== -->
      <section class="lg:col-span-2">
        <!-- Escenario -->
        <div class="bg-gray-900 text-white text-center font-semibold rounded-lg py-3 mb-4 shadow">
          ESCENARIO
        </div>

        <!-- Rejilla de mesas -->
        <div id="tableGrid" class="grid grid-cols-8 gap-3 bg-gray-50 p-4 rounded-xl border">
          <!-- Mesas generadas por JS -->
        </div>

        <!-- Ayuda/nota -->
        <p class="text-sm text-gray-500 mt-3">
          * Selecciona una o varias mesas para agregarlas al resumen. Las reservadas no se pueden elegir.
        </p>
      </section>

      <!-- ====== PANEL DERECHO ====== -->
      <aside class="space-y-6">
        <!-- Filtros -->
        <div class="bg-white border rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-gray-700 mb-3">Filtros</h3>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm text-gray-600 mb-1">Capacidad mín.</label>
              <select id="filterCapacity" class="w-full border rounded-lg px-3 py-2">
                <option value="0">Todas</option>
                <option value="4">4+</option>
                <option value="6">6+</option>
                <option value="8">8+</option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-gray-600 mb-1">Zona</label>
              <select id="filterZone" class="w-full border rounded-lg px-3 py-2">
                <option value="ALL">Todas</option>
                <option value="VIP">VIP</option>
                <option value="Platinum">Platinum</option>
                <option value="Gold">Gold</option>
                <option value="Silver">Silver</option>
              </select>
            </div>
          </div>
          <button id="btnClearFilters"
                  class="mt-3 text-sm underline text-gray-600 hover:text-gray-800">
            Limpiar filtros
          </button>
        </div>

        <!-- Leyenda -->
        <div class="bg-white border rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-gray-700 mb-3">Leyenda</h3>
          <ul class="space-y-1 text-sm">
            <li class="flex items-center gap-2">
              <span class="inline-block w-4 h-4 rounded bg-purple-100 border border-purple-400"></span>
              VIP (más cerca)
            </li>
            <li class="flex items-center gap-2">
              <span class="inline-block w-4 h-4 rounded bg-indigo-100 border border-indigo-400"></span>
              Platinum
            </li>
            <li class="flex items-center gap-2">
              <span class="inline-block w-4 h-4 rounded bg-amber-100 border border-amber-400"></span>
              Gold
            </li>
            <li class="flex items-center gap-2">
              <span class="inline-block w-4 h-4 rounded bg-slate-100 border border-slate-400"></span>
              Silver (más lejos)
            </li>
            <li class="flex items-center gap-2">
              <span class="inline-block w-4 h-4 rounded bg-gray-200 border border-gray-300"></span>
              Reservada
            </li>
            <li class="flex items-center gap-2">
              <span class="inline-block w-4 h-4 rounded ring-4 ring-purple-400"></span>
              Seleccionada
            </li>
          </ul>
        </div>

        <!-- Resumen -->
        <div class="bg-white border rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-gray-700 mb-3">Resumen de selección</h3>
          <div id="selectedList" class="space-y-2 text-sm text-gray-700">
            <p class="text-gray-500">Sin mesas seleccionadas.</p>
          </div>
          <div class="border-t mt-3 pt-3 flex items-center justify-between">
            <span class="font-semibold text-gray-700">Total</span>
            <span id="totalAmount" class="font-bold text-purple-700">CLP $0</span>
          </div>
          <button
            class="mt-4 w-full rounded-full px-5 py-3 font-semibold text-white bg-purple-600 hover:bg-purple-700">
            Reservar
          </button>
          <button id="btnClearSelection"
            class="mt-2 w-full rounded-full px-5 py-2 font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200">
            Limpiar selección
          </button>
        </div>
      </aside>
    </div>
  </main>

  <footer class="bg-gray-900 text-white py-6 mt-6">
    <div class="container mx-auto text-center">
      <p>&copy; 2025 Eventos. Todos los derechos reservados.</p>
    </div>
  </footer>

  <!-- ====== LÓGICA ====== -->
  <script>
    // Configuración
    const ROWS = 6;        // filas de mesas (1 = más cerca del escenario)
    const COLS = 8;        // columnas
    const capacityCycle = [4, 6, 8]; // para el ejemplo
    const reservedIds = new Set([3, 7, 15, 26, 35]); // mesas bloqueadas de ejemplo

    // Precio por persona según zona
    const zonePrice = { VIP: 25000, Platinum: 20000, Gold: 15000, Silver: 10000 };

    // Estilos por zona
    const zoneStyle = {
      VIP: "bg-purple-100 border-purple-400",
      Platinum: "bg-indigo-100 border-indigo-400",
      Gold: "bg-amber-100 border-amber-400",
      Silver: "bg-slate-100 border-slate-400",
    };

    // Determina la zona por fila (1-based)
    function zoneForRow(r) {
      if (r === 1) return "VIP";
      if (r === 2) return "Platinum";
      if (r <= 4) return "Gold";
      return "Silver";
    }

    // Formato CLP
    const fmtCLP = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });

    // Genera el dataset de mesas (id, fila, col, capacidad, zona, precios)
    const tables = [];
    let id = 1;
    for (let r = 1; r <= ROWS; r++) {
      for (let c = 1; c <= COLS; c++) {
        const zone = zoneForRow(r);
        const capacity = capacityCycle[(id - 1) % capacityCycle.length];
        const pricePerSeat = zonePrice[zone];
        const tablePrice = pricePerSeat * capacity;
        tables.push({
          id,
          row: r,
          col: c,
          zone,
          capacity,
          pricePerSeat,
          tablePrice,
          reserved: reservedIds.has(id),
        });
        id++;
      }
    }

    // Render de la grilla
    const grid = document.getElementById('tableGrid');
    function renderGrid() {
      grid.innerHTML = '';
      const minCap = parseInt(document.getElementById('filterCapacity').value, 10);
      const zoneFilter = document.getElementById('filterZone').value;

      tables.forEach(t => {
        if (minCap && t.capacity < minCap) return;
        if (zoneFilter !== 'ALL' && t.zone !== zoneFilter) return;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = [
          "table-btn relative aspect-[4/3] rounded-lg border-2 flex flex-col items-center justify-center",
          "text-sm font-semibold transition",
          t.reserved ? "bg-gray-200 border-gray-300 text-gray-500 cursor-not-allowed" : zoneStyle[t.zone] + " hover:scale-[1.02]"
        ].join(' ');

        btn.dataset.id = t.id;

        // Contenido
        btn.innerHTML = `
          <div class="absolute top-1 left-1 text-[11px] font-medium text-gray-700">#${t.id}</div>
          <div class="text-[12px]">${t.zone}</div>
          <div class="text-xs font-normal text-gray-600">Cap: ${t.capacity}</div>
          <div class="mt-1 text-[12px] font-bold">${fmtCLP.format(t.tablePrice)}</div>
        `;

        // Tooltip accesible
        btn.title = `Mesa ${t.id} • ${t.zone} • Cap: ${t.capacity} • ${fmtCLP.format(t.tablePrice)}`;

        if (!t.reserved) {
          btn.addEventListener('click', () => toggleSelect(t.id, btn));
        }

        grid.appendChild(btn);
      });

      // Restaura estado visual de selecciones
      selected.forEach(i => {
        const el = grid.querySelector(`.table-btn[data-id="${i}"]`);
        if (el) el.classList.add('ring-4', 'ring-purple-400');
      });
    }

    // Selecciones
    const selected = new Set();

    function toggleSelect(id, el) {
      if (selected.has(id)) {
        selected.delete(id);
        if (el) el.classList.remove('ring-4', 'ring-purple-400');
      } else {
        selected.add(id);
        if (el) el.classList.add('ring-4', 'ring-purple-400');
      }
      renderSummary();
    }

    function renderSummary() {
      const list = document.getElementById('selectedList');
      const total = document.getElementById('totalAmount');
      if (selected.size === 0) {
        list.innerHTML = `<p class="text-gray-500">Sin mesas seleccionadas.</p>`;
        total.textContent = fmtCLP.format(0);
        return;
      }

      const items = [];
      let sum = 0;
      selected.forEach(i => {
        const t = tables.find(x => x.id === i);
        if (!t) return;
        sum += t.tablePrice;
        items.push(`
          <div class="flex items-center justify-between">
            <div>
              <div class="font-semibold">Mesa ${t.id} <span class="text-xs text-gray-500">(${t.zone})</span></div>
              <div class="text-xs text-gray-600">Cap: ${t.capacity} • ${fmtCLP.format(t.pricePerSeat)} p/p</div>
            </div>
            <div class="text-sm font-semibold">${fmtCLP.format(t.tablePrice)}</div>
          </div>
        `);
      });

      list.innerHTML = items.join('');
      total.textContent = fmtCLP.format(sum);
    }

    // Filtros
    document.getElementById('filterCapacity').addEventListener('change', () => renderGrid());
    document.getElementById('filterZone').addEventListener('change', () => renderGrid());
    document.getElementById('btnClearFilters').addEventListener('click', () => {
      document.getElementById('filterCapacity').value = '0';
      document.getElementById('filterZone').value = 'ALL';
      renderGrid();
    });

    // Botones resumen
    document.getElementById('btnClearSelection').addEventListener('click', () => {
      selected.clear();
      renderGrid();
      renderSummary();
    });

    // Inicializa
    renderGrid();
    renderSummary();
  </script>
</body>
</html>
