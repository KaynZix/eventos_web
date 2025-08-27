<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Promociones</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-900 text-white">
<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">Promociones (opcional)</h1>
  <form method="POST" action="{{ route('reservas.promos.store') }}" class="space-y-4">
    @csrf
    <div class="space-y-2">
      @foreach($promos as $p)
        <label class="flex items-center gap-3">
          <input type="checkbox" name="promos[]" value="{{ $p['id'] }}" class="scale-125">
          <span>{{ $p['nombre'] }}</span>
        </label>
      @endforeach
    </div>
    <div class="flex gap-3 mt-4">
      <a href="{{ route('reservas.checkout') }}" class="px-4 py-2 rounded bg-neutral-700">Omitir</a>
      <button class="px-4 py-2 rounded bg-red-600 hover:bg-red-500">Continuar</button>
    </div>
  </form>
</div>
</body>
</html>
