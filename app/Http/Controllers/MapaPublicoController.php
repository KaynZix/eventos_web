<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MapaPublicoController extends Controller
{
    public function show(Evento $evento)
    {
        // Mesas del evento + flag "reservada" (si existe relación en reserva_mesa para este evento)
        $mesas = DB::table('escenario as e')
            ->join('mesa as m', 'm.id', '=', 'e.id_mesa')
            ->leftJoin('reserva_mesa as rm', 'rm.escenario_id', '=', 'e.id')
            ->leftJoin('reserva as r', function ($q) use ($evento) {
                $q->on('r.id', '=', 'rm.reserva_id')
                  ->where('r.id_evento', '=', $evento->id);
            })
            ->where('e.id_evento', $evento->id)
            ->select([
                'e.id as escenario_id',
                'm.id as mesa_id',
                'm.sillas',
                'e.x',
                'e.y',
                DB::raw('CASE WHEN rm.id IS NULL THEN 0 ELSE 1 END as reservada'),
            ])
            ->orderBy('e.y')
            ->orderBy('e.x')
            ->get();

        // Construcción segura de datetimes (fecha + hora) y manejo de cruce a día siguiente
        $base   = Carbon::parse($evento->fecha)->startOfDay();
        $inicio = $base->copy()->setTimeFromTimeString($evento->hora_inicio);
        $fin    = $base->copy()->setTimeFromTimeString($evento->hora_termino);

        if ($fin->lessThanOrEqualTo($inicio)) {
            $fin->addDay();
        }

        // Slots cada 30 minutos
        $slots = [];
        for ($t = $inicio->copy(); $t <= $fin; $t->addMinutes(30)) {
            $slots[] = $t->format('H:i');
        }

        return view('mapa.publico', compact('evento', 'mesas', 'slots'));
    }
}
