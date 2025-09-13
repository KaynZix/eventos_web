<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Confirmar reserva</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-neutral-900 text-white">

    @if(isset($secondsLeft))
    @php
    $left = max(0, (int)$secondsLeft);
    $mm = str_pad((int) floor($left/60), 2, '0', STR_PAD_LEFT);
    $ss = str_pad($left % 60, 2, '0', STR_PAD_LEFT);
    @endphp 

    <div id="timer-box" data-left="{{ $left }}"
        class="bg-amber-600/20 border border-amber-500/40 text-amber-200 px-4 py-2 rounded mb-4">
        Tienes <b id="timer">{{ $mm }}:{{ $ss }}</b> para completar la reserva antes de que las mesas vuelvan a quedar
        libres.
    </div>

    <script>
    (function() {
        const box = document.getElementById('timer-box');
        if (!box) return;

        let left = parseInt(box.dataset.left, 10) || 0;
        const el = document.getElementById('timer');

        function tick() {
            if (left <= 0) {
                location.reload();
                return;
            }
            const m = Math.floor(left / 60);
            const s = left % 60;
            el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            left -= 1;
            setTimeout(tick, 1000);
        }
        tick();
    })();
    </script>
    @endif


    <div class="max-w-3xl mx-auto p-6 space-y-6">
        <h1 class="text-2xl font-bold">Confirmar reserva</h1>

        <div class="bg-neutral-800/60 p-4 rounded">
            <div class="text-sm text-neutral-300">
                Evento: <b>{{ $evento->nombre }}</b><br>
                Llegada: <b>{{ $reserva['llegada'] }}</b><br>
                Mesas seleccionadas: <b>{{ count($reserva['mesas']) }}</b><br>
                <!-- Promos elegidas: <b>{{ implode(', ', $reserva['promos'] ?? []) ?: '—' }}</b> -->
            </div>
        </div>
        @if(!empty($items))
        <div class="bg-neutral-800/40 p-3 rounded text-sm">
            <div class="font-semibold mb-2">Detalle</div>
            <ul class="list-disc pl-5 space-y-1">
            @foreach($items as $it)
                <li>
                Mesa {{ $it['escenario_id'] }}
                — {{ $it['sillas'] }} sillas
                — ${{ number_format($it['precio'], 0, ',', '.') }}
                </li>
            @endforeach
            </ul>
            <div class="mt-2 text-right">
            <span class="text-neutral-300">Subtotal:</span>
            <b>${{ number_format($total ?? 0, 0, ',', '.') }}</b>
            </div>
        </div>
        @endif

        <!-- <br>IDs: <b>{{ implode(', ', $reserva['mesas'] ?? []) }}</b> -->

        <form method="POST" action="{{ route('reservas.checkout.store') }}" class="grid grid-cols-1 gap-4">
            @csrf
            <div>
                <label class="block text-sm mb-1">Nombre</label>
                <input name="nombre" value="{{ old('nombre') }}"
                class="w-full rounded bg-neutral-800 border border-white/10 px-3 py-2" required>
                @error('nombre')<div class="text-red-400 text-sm">{{ $message }}</div>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Correo</label>
                    <input name="correo" type="email" value="{{ old('correo') }}"
                    class="w-full rounded bg-neutral-800 border border-white/10 px-3 py-2" required>
                    @error('correo')<div class="text-red-400 text-sm">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="block text-sm mb-1">WhatsApp</label>
                    <input name="numero_wsp" value="{{ old('numero_wsp') }}"
                    class="w-full rounded bg-neutral-800 border border-white/10 px-3 py-2" required>
                    @error('numero_wsp')<div class="text-red-400 text-sm">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="flex items-center justify-between mt-2">
                <div class="text-neutral-300">Total a pagar ahora: <b>${{ number_format($total,0,',','.') }}</b></div>
                <input type="hidden" name="total" value="{{ (int)($total ?? 0) }}">
                <button class="px-4 py-2 rounded bg-green-500 text-black hover:bg-green-400">Confirmar reserva</button>
            </div>
        </form>
        <form method="POST" action="{{ route('reservas.cancel') }}"
            onsubmit="return confirm('¿Cancelar la reserva en curso?');" class="inline">
            @csrf

            <button type="submit" class="px-4 py-2 rounded bg-neutral-700 hover:bg-neutral-600 text-white"
                onclick="this.disabled=true; this.form.submit();">
                Cancelar
            </button>
        </form>

    </div>
</body>

@if ($errors->any())
  <div class="bg-red-500/10 border border-red-500/30 text-red-200 px-3 py-2 rounded">
    @foreach ($errors->all() as $e)
      <div>• {{ $e }}</div>
    @endforeach
  </div>
@endif

</html>