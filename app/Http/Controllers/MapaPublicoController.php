<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MapaPublicoController extends Controller
{
    public function show(Evento $evento)
    {
        $mesas = DB::table('escenario as e')
            ->join('mesa as m', 'm.id', '=', 'e.id_mesa')
            ->leftJoin('reserva_mesa as rm', 'rm.escenario_id', '=', 'e.id')
            ->leftJoin('reserva as r', function ($q) use ($evento) {
                $q->on('r.id', '=', 'rm.reserva_id')
                  ->where('r.id_evento', '=', $evento->id);
            })
            ->leftJoin('mesas_hold as h', function ($q) use ($evento) {
                $q->on('h.escenario_id', '=', 'e.id')
                  ->where('h.evento_id', '=', $evento->id)
                  ->where('h.expires_at', '>', now());
            })
            ->where('e.id_evento', $evento->id)
            ->select([
                'e.id as escenario_id',
                'm.id as mesa_id',
                'm.sillas',
                'e.x','e.y',
                'e.rot',  
                DB::raw('CASE WHEN r.id IS NULL THEN 0 ELSE 1 END as reservada'),
                DB::raw('CASE WHEN h.id IS NULL THEN 0 ELSE 1 END as bloqueada'),
                DB::raw('COALESCE(TIMESTAMPDIFF(SECOND, NOW(), h.expires_at),0) as hold_left'),
                'm.precio',
            ])
            ->orderBy('y')->orderBy('x')
            ->get();

        // ---- Slots cada 30 min (maneja fin al día siguiente) ----
        // Normaliza HH:MM -> HH:MM:SS
        $hIni = preg_match('/^\d{2}:\d{2}:\d{2}$/', $evento->hora_inicio) ? $evento->hora_inicio : ($evento->hora_inicio . ':00');
        $hFin = preg_match('/^\d{2}:\d{2}:\d{2}$/', $evento->hora_termino) ? $evento->hora_termino : ($evento->hora_termino . ':00');

        // Toma solo la parte de fecha y luego setea la hora
        $baseFecha = Carbon::parse($evento->fecha)->startOfDay();

        $inicio = $baseFecha->copy()->setTimeFromTimeString($hIni);
        $fin    = $baseFecha->copy()->setTimeFromTimeString($hFin);
        if ($fin->lessThanOrEqualTo($inicio)) {
            $fin->addDay(); // termina al día siguiente
        }

        $slots = [];
        for ($t = $inicio->copy(); $t <= $fin; $t->addMinutes(30)) {
            $slots[] = $t->format('H:i');
        }

        return view('mapa.publico', compact('evento','mesas','slots'));
    }

    // Devuelve sólo el estado de cada mesa (para polling en la UI)
    // app/Http/Controllers/MapaPublicoController.php

    public function status(Evento $evento)
    {
        $rows = DB::table('escenario as e')
            ->leftJoin('reserva_mesa as rm', 'rm.escenario_id', '=', 'e.id')
            ->leftJoin('reserva as r', function ($q) use ($evento) {
                $q->on('r.id', '=', 'rm.reserva_id')
                ->where('r.id_evento', $evento->id);
            })
            ->leftJoin('mesas_hold as h', function ($q) use ($evento) {
                $q->on('h.escenario_id', '=', 'e.id')
                ->where('h.evento_id', '=', $evento->id)
                ->where('h.expires_at', '>', DB::raw('UTC_TIMESTAMP()'));
            })
            ->where('e.id_evento', $evento->id)
            ->select([
                'e.id as escenario_id',
                DB::raw('CASE WHEN r.id IS NULL THEN 0 ELSE 1 END as reservada'),
                DB::raw('CASE WHEN h.id IS NULL THEN 0 ELSE 1 END as bloqueada'),   
                DB::raw('COALESCE(TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), h.expires_at),0) as hold_left'),
            ])
            ->get();

        return response()->json($rows);
    }

}