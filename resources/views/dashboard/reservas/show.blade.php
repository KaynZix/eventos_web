<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reserva #{{ $reserva->id }}</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-900 text-white">
<div class="max-w-4xl mx-auto p-6 space-y-6">

  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Reserva #{{ $reserva->id }}</h1>
    <a href="{{ route('dasboard.reservas.index') }}" class="px-3 py-2 rounded bg-neutral-700 hover:bg-neutral-600">← Volver</a>
  </div>

  <div class="grid md:grid-cols-2 gap-4">
    <div class="bg-neutral-800/50 p-4 rounded">
      <div class="text-sm text-neutral-300 space-y-1">
        <div>Evento: <b>{{ $reserva->evento }}</b></div>
        <div>Fecha:  <b>{{ $reserva->fecha }}</b></div>
        <div>Llegada: <b>{{ \Illuminate\Support\Str::of($reserva->hora_reserva)->substr(0,5) }}</b></div>
        <div>Creada: <b>{{ $reserva->created_at }}</b></div>
      </div>
    </div>
    <div class="bg-neutral-800/50 p-4 rounded">
      <div class="text-sm text-neutral-300 space-y-1">
        <div>Cliente: <b>{{ $reserva->nombre_cliente }}</b></div>
        <div>Correo:  <b>{{ $reserva->correo }}</b></div>
        <div>WhatsApp: <b>{{ $reserva->numero_wsp }}</b></div>
        <div>Total registrado: <b>${{ number_format($reserva->total, 0, ',', '.') }}</b></div>
        <div class="text-neutral-400">
          (Verificado por suma de mesas: ${{ number_format($sum, 0, ',', '.') }})
        </div>
      </div>
    </div>
  </div>

  <div class="bg-neutral-800/40 p-4 rounded">
    <div class="font-semibold mb-2">Mesas</div>
    <ul class="list-disc pl-5 space-y-1 text-sm">
      @forelse($mesas as $m)
        <li>
          Escenario {{ $m->escenario_id }} — Mesa {{ $m->mesa_id }} — {{ $m->sillas }} sillas —
          ${{ number_format($m->precio, 0, ',', '.') }}
        </li>
      @empty
        <li class="text-neutral-400">Sin mesas asociadas.</li>
      @endforelse
    </ul>
  </div>

</div>
</body>
</html>
