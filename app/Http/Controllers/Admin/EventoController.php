<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EventoController extends Controller
{
    public function index()
    {
        $eventos = Evento::orderBy('fecha')->orderBy('hora_inicio')->get();
        return view('dashboard.eventos.index', compact('eventos'));
    }

    public function create()
    {
        return view('dashboard.eventos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:100',
            'genero'       => 'nullable|string|max:100',
            'tributo'      => 'sometimes|boolean',
            'fecha'        => 'required|date',
            'hora_inicio'  => 'required|date_format:H:i',
            'hora_termino' => 'required|date_format:H:i', // ← quitamos "after"
            'id_escenario' => 'required|integer|in:1,2',
            'imagen_file'  => 'nullable|image|max:2048',
            'imagen_url'   => 'nullable|string|max:255',
        ]);
        $data['tributo'] = $request->boolean('tributo');

        // Validación de duración permitiendo cruzar medianoche
        $start = Carbon::createFromFormat('Y-m-d H:i', $data['fecha'].' '.$data['hora_inicio']);
        $end   = Carbon::createFromFormat('Y-m-d H:i', $data['fecha'].' '.$data['hora_termino']);
        if ($end->lte($start)) {
            // si termina antes/igual que inicia, asumimos que es al día siguiente
            $end->addDay();
        }
        $mins = $start->diffInMinutes($end);
        if ($mins < 30 || $mins > 12*60) {
            return back()->withErrors([
                'hora_termino' => 'La duración debe ser entre 30 minutos y 12 horas (se permite terminar al día siguiente).',
            ])->withInput();
        }

        // Imagen: archivo > url > vacío
        $imagen = '';
        if ($request->hasFile('imagen_file')) {
            $path = $request->file('imagen_file')->store('public/images/eventos');
            $imagen = \Storage::url($path);
        } elseif (filled($data['imagen_url'] ?? null)) {
            $imagen = $data['imagen_url'];
        }
        $data['imagen'] = $imagen;
        unset($data['imagen_file'], $data['imagen_url']);

        $evento = \App\Models\Evento::create($data);

        return redirect()->route('dashboard.eventos.mapa', $evento->id)
            ->with('ok','Evento creado. Ahora configura el mapa de mesas.');
    }

    public function edit(Evento $evento)
    {
        return view('dashboard.eventos.edit', compact('evento'));
    }

    public function update(Request $request, Evento $evento)
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:100',
            'genero'       => 'nullable|string|max:100',
            'tributo'      => 'sometimes|boolean',
            'fecha'        => 'required|date',
            'hora_inicio'  => 'required|date_format:H:i',
            'hora_termino' => 'required|date_format:H:i|after:hora_inicio',
            'id_escenario' => 'required|integer|in:1,2',
            'imagen_file'  => 'nullable|image|max:2048',
            'imagen_url'   => 'nullable|string|max:255',
        ]);
        $data['tributo'] = $request->boolean('tributo');

        // Actualizar imagen sólo si viene algo nuevo
        if ($request->hasFile('imagen_file')) {
            $path = $request->file('imagen_file')->store('public/images/eventos');
            $data['imagen'] = Storage::url($path);
        } elseif (filled($data['imagen_url'] ?? null)) {
            $data['imagen'] = $data['imagen_url'];
        } else {
            unset($data['imagen']); // mantener la actual
        }

        unset($data['imagen_file'], $data['imagen_url']);

        $evento->update($data);

        return redirect()->route('dashboard.eventos.index')->with('ok', 'Evento actualizado');
    }

    public function destroy(Evento $evento)
    {
        DB::transaction(function () use ($evento) {
            DB::table('escenario')->where('id_evento', $evento->id)->delete();
            $evento->delete();
        });

        return redirect()->route('dashboard.eventos.index')->with('ok', 'Evento eliminado');
    }
}
