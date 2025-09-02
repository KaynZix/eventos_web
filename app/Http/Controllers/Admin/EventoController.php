<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EventoController extends Controller
{
    public function index()
    {
        // Capacidad de sillas por evento (suma de m.sillas en el layout de ese evento)
        $qCap = \DB::table('escenario as e')
            ->join('mesa as m', 'm.id', '=', 'e.id_mesa')
            ->select('e.id_evento', \DB::raw('SUM(m.sillas) as sillas_total'))
            ->groupBy('e.id_evento');

        // Cantidad de reservas por evento
        $qRes = \DB::table('reserva as r')
            ->select('r.id_evento', \DB::raw('COUNT(*) as reservas'))
            ->groupBy('r.id_evento');

        // Traemos eventos + agregados
        $eventos = \DB::table('eventos as ev')
            ->leftJoinSub($qCap, 'cap', function ($j) {
                $j->on('cap.id_evento', '=', 'ev.id');
            })
            ->leftJoinSub($qRes, 'res', function ($j) {
                $j->on('res.id_evento', '=', 'ev.id');
            })
            ->orderBy('ev.fecha')
            ->orderBy('ev.hora_inicio')
            ->get([
                'ev.id',
                'ev.nombre',
                'ev.genero',
                'ev.tributo',
                'ev.fecha',
                'ev.hora_inicio',
                'ev.hora_termino',
                'ev.id_escenario',
                'ev.precio_silla_base',
                \DB::raw('COALESCE(cap.sillas_total,0) as sillas_total'),
                \DB::raw('COALESCE(res.reservas,0) as reservas'),
            ]);

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
            'hora_termino' => 'required|date_format:H:i', // se permite cruzar medianoche (validamos abajo)
            'id_escenario' => 'required|integer|in:1,2',
            'imagen_file'  => 'nullable|image|max:4096',
            'imagen_url'   => 'nullable|string|max:255',
            'precio_silla_base' => 'required|integer|min:0',

        ]);
        $data['tributo'] = $request->boolean('tributo');

        // Validación de duración (permite terminar al día siguiente)
        $start = Carbon::createFromFormat('Y-m-d H:i', $data['fecha'].' '.$data['hora_inicio']);
        $end   = Carbon::createFromFormat('Y-m-d H:i', $data['fecha'].' '.$data['hora_termino']);
        if ($end->lte($start)) { $end->addDay(); }
        $mins = $start->diffInMinutes($end);

        // tope 24h
        if ($mins < 30 || $mins > 24*60) {
            return back()->withErrors([
                'hora_termino' => 'La duración debe ser entre 30 minutos y 24 horas (se permite terminar al día siguiente).',
            ])->withInput();
        }


        // ---------- IMAGEN EN public/images/eventos ----------
        // Guardamos el archivo en /public/images/eventos y en DB queda /images/eventos/archivo.ext
        $imagen = '';
        if ($request->hasFile('imagen_file')) {
            $file = $request->file('imagen_file');
            $dest = public_path('images/eventos');
            if (!is_dir($dest)) {
                @mkdir($dest, 0755, true);
            }
            $filename = time().'_'.Str::random(12).'.'.$file->getClientOriginalExtension();
            $file->move($dest, $filename);
            $imagen = '/images/eventos/'.$filename; // ruta pública
        } elseif (filled($data['imagen_url'] ?? null)) {
            $imagen = $data['imagen_url'];
        }
        $data['imagen'] = $imagen;
        unset($data['imagen_file'], $data['imagen_url']);
        // -----------------------------------------------------

        $evento = Evento::create($data);

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
            // SIN "after": permitimos cruzar medianoche
            'hora_termino' => 'required|date_format:H:i',
            'id_escenario' => 'required|integer|in:1,2',
            'imagen_file'  => 'nullable|image|max:2048',
            'imagen_url'   => 'nullable|url|max:255',
            'precio_silla_base' => 'required|integer|min:0',
        ]);
        $data['tributo'] = $request->boolean('tributo');

        // Duración mínima/máxima permitiendo terminar al día siguiente
        $start = Carbon::createFromFormat('Y-m-d H:i', $data['fecha'].' '.$data['hora_inicio']);
        $end   = Carbon::createFromFormat('Y-m-d H:i', $data['fecha'].' '.$data['hora_termino']);
        if ($end->lte($start)) $end->addDay();
        $mins = $start->diffInMinutes($end);
        if ($mins < 30 || $mins > 12*60) {
            return back()->withErrors([
                'hora_termino' => 'La duración debe ser entre 30 minutos y 12 horas (se permite terminar al día siguiente).',
            ])->withInput();
        }

        // Imagen: archivo > URL > mantener actual
        if ($request->hasFile('imagen_file')) {
            $dir = public_path('images/eventos');
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

            $ext  = $request->file('imagen_file')->getClientOriginalExtension();
            $name = uniqid('ev_').'.'.$ext;
            $request->file('imagen_file')->move($dir, $name);

            // (Opcional) borrar la anterior si era local
            if ($evento->imagen && Str::startsWith($evento->imagen, '/images/')) {
                @unlink(public_path(ltrim($evento->imagen, '/')));
            }

            $data['imagen'] = '/images/eventos/'.$name;
        } elseif (filled($data['imagen_url'] ?? null)) {
            $data['imagen'] = $data['imagen_url'];
        } else {
            unset($data['imagen']); // deja la actual
        }
        unset($data['imagen_file'], $data['imagen_url']);

        $evento->update($data);

        return redirect()->route('dashboard.eventos.index')
            ->with('ok', 'Evento actualizado');
    }



    public function destroy(Evento $evento)
    {
        DB::transaction(function () use ($evento) {
            // (Opcional) borrar imagen si estaba en /images/eventos/
            if (!empty($evento->imagen) && str_starts_with($evento->imagen, '/images/eventos/')) {
                $old = public_path(ltrim($evento->imagen, '/'));
                if (is_file($old)) @unlink($old);
            }

            DB::table('escenario')->where('id_evento', $evento->id)->delete();
            $evento->delete();
        });

        return redirect()->route('dashboard.eventos.index')->with('ok', 'Evento eliminado');
    }
}
