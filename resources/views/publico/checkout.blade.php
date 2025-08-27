<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Confirmar reserva</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-900 text-white">
<div class="max-w-3xl mx-auto p-6 space-y-6">
  <h1 class="text-2xl font-bold">Confirmar reserva</h1>

  <div class="bg-neutral-800/60 p-4 rounded">
    <div class="text-sm text-neutral-300">
      Evento: <b>{{ $evento->nombre }}</b><br>
      Llegada: <b>{{ $reserva['llegada'] }}</b><br>
      Mesas seleccionadas: <b>{{ count($reserva['mesas']) }}</b><br>
      Promos elegidas: <b>{{ implode(', ', $reserva['promos'] ?? []) ?: '—' }}</b>
    </div>
  </div>

  <form method="POST" action="{{ route('reservas.checkout.store') }}" class="grid grid-cols-1 gap-4">
    @csrf
    <div>
      <label class="block text-sm mb-1">Nombre</label>
      <input name="nombre" class="w-full rounded bg-neutral-800 border border-white/10 px-3 py-2" required>
      @error('nombre')<div class="text-red-400 text-sm">{{ $message }}</div>@enderror
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1">Correo</label>
        <input name="correo" type="email" class="w-full rounded bg-neutral-800 border border-white/10 px-3 py-2" required>
        @error('correo')<div class="text-red-400 text-sm">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="block text-sm mb-1">WhatsApp</label>
        <input name="numero_wsp" class="w-full rounded bg-neutral-800 border border-white/10 px-3 py-2" required>
        @error('numero_wsp')<div class="text-red-400 text-sm">{{ $message }}</div>@enderror
      </div>
    </div>

    <div class="flex items-center justify-between mt-2">
      <div class="text-neutral-300">Total a pagar ahora: <b>${{ number_format($total,0,',','.') }}</b></div>
      <button class="px-4 py-2 rounded bg-green-500 text-black hover:bg-green-400">Confirmar reserva</button>
    </div>
  </form>
</div>
</body>
</html>
