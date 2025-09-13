<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use App\Models\Evento;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservaCreada;
use App\Mail\ReservaRecordatorio;

class ReservasPublicController extends Controller
{
    /**
     * Inicia la reserva: valida mesas, crea holds (8 min),
     * guarda en sesión y deja un cookie para reanudar.
     * Recibe: evento_id, llegada (HH:MM), mesas[escenario_id...], flow(promos|pago)
     */
    public function start(Request $r)
    {
        $data = $r->validate([
            'evento_id' => 'required|integer',
            'llegada'   => 'required|string',
            'mesas'     => 'required|array|min:1',
            'mesas.*'   => 'integer',
            'flow'      => 'required|in:promos,pago',
            'total'     => 'nullable|integer|min:0',
        ]);

        $evento = Evento::findOrFail($data['evento_id']);

        // Limpia holds vencidos (higiene)
        DB::table('mesas_hold')->where('expires_at', '<=', DB::raw('UTC_TIMESTAMP()'))->delete();

        // Validar que las mesas existen, pertenecen al evento y no están reservadas
        $esc = DB::table('escenario as e')
            ->leftJoin('reserva_mesa as rm', 'rm.escenario_id', '=', 'e.id')
            ->leftJoin('reserva as r', function($q) use ($evento){
                $q->on('r.id','=','rm.reserva_id')->where('r.id_evento',$evento->id);
            })
            ->where('e.id_evento',$evento->id)
            ->whereIn('e.id',$data['mesas'])
            ->select('e.id', DB::raw('CASE WHEN r.id IS NULL THEN 0 ELSE 1 END as reservada'))
            ->get();

        if ($esc->count() !== count($data['mesas'])) {
            return response()->json(['ok'=>false,'msg'=>'Mesas inválidas.'], 422);
        }
        if ($esc->firstWhere('reservada',1)) {
            return response()->json(['ok'=>false,'msg'=>'Alguna mesa ya está reservada.'], 422);
        }

        // Crear HOLDs (8 minutos) de forma atómica
        $token     = Str::uuid()->toString();
        $sessionId = $r->session()->getId();
        $expiresAt = Carbon::now()->addMinutes(8);

        DB::beginTransaction();
        try {
            foreach ($data['mesas'] as $escId) {
                // Impide hold si ya hay uno activo para esa mesa
                $inserted = DB::insert(
                    "INSERT INTO mesas_hold (evento_id, escenario_id, session_id, hold_token, expires_at, created_at, updated_at)
                    SELECT ?, ?, ?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 8 MINUTE), UTC_TIMESTAMP(), UTC_TIMESTAMP()
                    FROM DUAL
                    WHERE NOT EXISTS (
                      SELECT 1 FROM mesas_hold h
                      WHERE h.evento_id = ? AND h.escenario_id = ? AND h.expires_at > UTC_TIMESTAMP()
                    )
                    ", [$evento->id, $escId, $sessionId, $token, $evento->id, $escId]);

                if (!$inserted) {
                    DB::rollBack();
                    return response()->json(['ok'=>false,'msg'=>'Una mesa fue tomada por otro usuario.'], 409);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['ok'=>false,'msg'=>'No se pudo bloquear las mesas.'], 500);
        }

        // Cookie para poder reanudar (vigencia igual/menor al hold)
        Cookie::queue(cookie(
            'reserva_hold',
            json_encode([
                'token'     => $token,
                'evento_id' => $evento->id,
                'llegada'   => $data['llegada'],
                'flow'      => $data['flow'], // promos|pago
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            10 // minutos
        ));

        // Guardar “reserva en curso” + info del hold en sesión
        session([
            'reserva' => [
                'evento_id' => $evento->id,
                'llegada'   => $data['llegada'],
                'mesas'     => array_values($data['mesas']),
                'promos'    => [],
                'client_total' => (int)($data['total'] ?? 0),
                'hold'      => [
                    'token'      => $token,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ]
        ]);

        return response()->json([
            'ok'       => true,
            'redirect' => $data['flow']==='promos'
                ? route('reservas.promos')
                : route('reservas.checkout'),
        ]);
        $sum = DB::table('escenario as e')
        ->join('mesa as m','m.id','=','e.id_mesa')
        ->where('e.id_evento', $v['evento_id'])
        ->whereIn('e.id', $v['mesas'])
        ->sum('m.precio');

    }

    /** Segundos restantes del hold; <=0 si expiró */
    protected function remainingSecondsOrFail(): int
    {
        $hold = session('reserva.hold');
        if (!$hold) return 0;
        return Carbon::now()->diffInSeconds(Carbon::parse($hold['expires_at']), false);
    }

    /** Página de selección de promos (no suma al total) */
    public function promos()
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $left = $this->remainingSecondsOrFail();
        if ($left <= 0) {
            return redirect()->route('mapa.publico', $reserva['evento_id'])
                ->with('err','Tu tiempo para completar la reserva expiró.');
        }

        $promos = [
            ['id'=>1,'nombre'=>'Promo 2 schop + papas'],
            ['id'=>2,'nombre'=>'Promo Nachos + 2 bebestibles'],
            ['id'=>3,'nombre'=>'Cerveza 1L + papas'],
        ];

        return view('publico.promos', [
            'reserva'     => $reserva,
            'promos'      => $promos,
            'secondsLeft' => $left,
        ]);
    }

    public function promosStore(Request $r)
    {
        $reserva = session('reserva');
        if (!$reserva) return redirect()->route('welcome');

        $promos = $r->validate(['promos'=>'array'])['promos'] ?? [];
        $reserva['promos'] = array_values($promos);
        session(['reserva'=>$reserva]);

        return redirect()->route('reservas.checkout');
    }

    /** Checkout: datos del cliente y confirmación SIN pago (por ahora) */
    public function checkout()
    {
        $reserva = session('reserva');
        if (!$reserva || empty($reserva['mesas'])) {
            return redirect()->route('welcome');
        }

        $left = $this->remainingSecondsOrFail();
        if ($left <= 0) {
            return redirect()->route('mapa.publico', $reserva['evento_id'])
                ->with('err','Tu tiempo para completar la reserva expiró.');
        }

        $evento = Evento::findOrFail($reserva['evento_id']);

        // Asegúrate de tener ints en la sesión
        $escenarioIds = array_map('intval', $reserva['mesas']);

        // Desglose y total (siempre recalculado en servidor)
        $rows = DB::table('escenario as e')
            ->join('mesa as m', 'm.id', '=', 'e.id_mesa')
            ->where('e.id_evento', $evento->id)
            ->whereIn('e.id', $escenarioIds)
            ->select('e.id as escenario_id', 'm.sillas', 'm.precio')
            ->get();

        $items = $rows->map(fn($r) => [
            'escenario_id' => (int)$r->escenario_id,
            'sillas'       => (int)$r->sillas,
            'precio'       => (int)$r->precio,   // ajusta a float si tu columna es decimal
        ])->all();

        $total = (int)$rows->sum('precio');

        return view('publico.checkout', [
            'reserva'     => $reserva,
            'evento'      => $evento,
            'items'       => $items,     // <— pásalo a la vista
            'total'       => $total,     // <— ya no es 0
            'secondsLeft' => $left,
        ]);
    }

    /** Crea la reserva y asocia mesas en pivote reserva_mesa */
    public function checkoutStore(Request $r)
    {
        $reserva = session('reserva');
        if (!$reserva || empty($reserva['mesas'])) return redirect()->route('eventos.index')->withErrors('No hay una reserva activa.');

        // Hold vigente
        $left = $this->remainingSecondsOrFail();
        if ($left <= 0) {
            return redirect()->route('mapa.publico', $reserva['evento_id'])
                ->withErrors('El tiempo expiró, vuelve a elegir las mesas.');
        }

        $data = $r->validate([
            'nombre'     => 'required|string|max:80',
            'correo'     => 'required|email|max:120',
            'numero_wsp' => 'required|string|max:20',
            'total'      => 'nullable|integer|min:0',
        ]);

        $eventoId     = (int)$reserva['evento_id'];
        $escenarioIds = array_map('intval', $reserva['mesas']);
        $evento       = Evento::findOrFail($eventoId);  // + NUEVO (lo usas para el asunto del mail) 

        // === Traer precios reales por mesa y sumar (servidor) ===
        $rows = DB::table('escenario as e')
            ->join('mesa as m', 'm.id', '=', 'e.id_mesa')
            ->where('e.id_evento', $eventoId)
            ->whereIn('e.id', $escenarioIds)
            ->select('e.id as escenario_id', 'm.id as mesa_id', 'm.sillas', 'm.precio')
            ->get();

        if ($rows->count() !== count($escenarioIds)) {
            return back()->withErrors('Hay mesas inválidas para este evento.')->withInput();
        }

        $serverTotal = (int)$rows->sum('precio');              // total real
        $clientStart = (int)($reserva['client_total'] ?? 0);   // total enviado desde el mapa
        $clientNow   = (int)$data['total'];                    // total que mostró el checkout
        $clientTotal = $clientNow ?: $clientStart;

        // === Comparación estricta (puedes cambiar la política si quieres) ===
        if ($clientTotal > 0 && $clientTotal !== $serverTotal) {
            return back()->withErrors(
                'El total cambió (front: $'.number_format($clientTotal,0,',','.').
                ' / servidor: $'.number_format($serverTotal,0,',','.').'). '.
                'Actualiza la página y vuelve a intentar.'
            )->withInput();
        }

        // (opcional) también verifica que el HOLD siga en BD para esas mesas
        $holdsOk = DB::table('mesas_hold')
            ->where('hold_token', $reserva['hold']['token'] ?? '')
            ->where('evento_id', $eventoId)
            ->whereIn('escenario_id', $escenarioIds)
            ->where('expires_at', '>', now())
            ->count() === count($escenarioIds);

        if (!$holdsOk) {
            return back()->withErrors('Alguna mesa ya no está bloqueada. Refresca y vuelve a intentar.');
        }

        $hora = strlen($reserva['llegada']) === 5 ? $reserva['llegada'].':00' : $reserva['llegada'];

        DB::beginTransaction();
            try {
                $reservaId = DB::table('reserva')->insertGetId([
                    'nombre_cliente' => $data['nombre'],
                    'correo'         => $data['correo'],
                    'numero_wsp'     => $data['numero_wsp'],
                    'hora_reserva'   => $hora,
                    'total'          => $serverTotal,  // guarda SIEMPRE el del servidor
                    'id_evento'      => $eventoId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

            $now = now();
            $pivot = [];
            foreach ($escenarioIds as $eid) {
                $pivot[] = ['reserva_id'=>$reservaId, 'escenario_id'=>$eid, 'created_at'=>$now, 'updated_at'=>$now];
            }
            DB::table('reserva_mesa')->insert($pivot);

            // libera holds
            DB::table('mesas_hold')->where('hold_token', $reserva['hold']['token'] ?? '')->delete();

            DB::commit();

            // Datos para el correo
            $items = $rows->map(fn($r) => [
                'escenario_id' => (int)$r->escenario_id,
                'sillas'       => (int)$r->sillas,
                'precio'       => (int)$r->precio,
            ])->all();

            $eventoArr = ['id'=>$eventoId, 'nombre'=>$evento->nombre];
            $cliente   = ['nombre'=>$data['nombre'], 'correo'=>$data['correo'], 'numero_wsp'=>$data['numero_wsp']];

            try {
                Mail::to($data['correo'])
                    // ->bcc('admin@tusitio.cl') // opcional copia oculta
                    ->send(new ReservaCreada($eventoArr, $cliente, $items, $serverTotal, $reservaId, $hora));
            } catch (\Throwable $e) {
                \Log::error('Email reserva falló', ['err'=>$e->getMessage(), 'reserva_id'=>$reservaId]);
                // No abortamos la reserva si el email falla.
            }

            
            // Programar recordatorio 1 día antes del evento
            // 1) Normaliza la FECHA a solo Y-m-d
            $eventoFecha = $evento->fecha instanceof \Carbon\Carbon
                ? $evento->fecha->toDateString()
                : \Carbon\Carbon::parse($evento->fecha)->toDateString();

            // 2) Toma la HORA preferente del evento; si viene con fecha, extrae solo la hora
            $horaRaw = $evento->hora_inicio ?? $hora; // $hora ya es HH:MM:SS de tu flujo
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaRaw)) {
                $horaEvento = $horaRaw;
            } elseif (preg_match('/^\d{2}:\d{2}$/', $horaRaw)) {
                $horaEvento = $horaRaw . ':00';
            } else {
                // Si viene como datetime ("2025-08-25 18:00:00") o cualquier otro formato, parsea y deja HH:MM:SS
                try {
                    $horaEvento = \Carbon\Carbon::parse($horaRaw)->format('H:i:s');
                } catch (\Throwable $e) {
                    $horaEvento = '00:00:00'; // fallback seguro
                }
            }

            // 3) Construye el instante y réstale un día
            $sendAt = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $eventoFecha.' '.$horaEvento)->subDay();

            // Si ya pasó (reservas hechas con <24h), envíalo en 1 minuto
            if ($sendAt->isPast()) {
                $sendAt = now()->addMinute();
            }

            Mail::to($data['correo'])->queue(
                (new \App\Mail\ReservaRecordatorio(
                    evento:  ['id'=>$eventoId,'nombre'=>$evento->nombre,'fecha'=>$eventoFecha,'hora'=>$horaEvento],
                    cliente: ['nombre'=>$data['nombre'],'correo'=>$data['correo'],'numero_wsp'=>$data['numero_wsp']],
                    items:   $items,
                    total:   $serverTotal,
                    reservaId: $reservaId
                ))->delay($sendAt)
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('No se pudo guardar la reserva: '.$e->getMessage())->withInput();
        }
        $mesaIds = DB::table('escenario')->whereIn('id', $reserva['mesas'])->pluck('id_mesa');
        DB::table('mesa')->whereIn('id', $mesaIds)->update(['id_reserva' => $reservaId, 'updated_at' => now()]);

        // Limpia sesión y cookie
        session()->forget('reserva');
        Cookie::queue(Cookie::forget('reserva_hold'));

        return redirect()->route('reservas.ok')->with('ok', 'Reserva creada #'.$reservaId);
    }

    public function ok()
    {
        return view('publico.ok');
    }

    /** Cancelar: libera holds, borra cookie y limpia sesión */
    public function cancel(Request $r)
    {
        $reserva = session('reserva');

        if ($reserva && isset($reserva['hold']['token'])) {
            DB::table('mesas_hold')->where('hold_token', $reserva['hold']['token'])->delete();
        } else {
            // Si no hay sesión, intenta por cookie
            $cookie = $r->cookie('reserva_hold');
            if ($cookie) {
                $meta = json_decode($cookie, true);
                if (!empty($meta['token'])) {
                    DB::table('mesas_hold')->where('hold_token', $meta['token'])->delete();
                }
            }
        }

        session()->forget('reserva');
        Cookie::queue(Cookie::forget('reserva_hold'));

        return redirect()->route('eventos.index')->with('ok', 'Reserva cancelada.');
    }

    /** Reanudar reserva a partir del cookie si los holds siguen vigentes */
    public function resume(Request $r)
    {
        // 1) Si ya hay reserva en sesión y hold vigente, continuar
        $reserva = session('reserva');
        if ($reserva && isset($reserva['hold']['token'])) {
            $left = $this->remainingSecondsOrFail();
            if ($left > 0) {
                return redirect()->route('reservas.checkout');
            }
        }

        // 2) Intentar por cookie
        $cookie = $r->cookie('reserva_hold');
        if (!$cookie) {
            return redirect()->route('eventos.index')->with('err','No hay una reserva activa para reanudar.');
        }

        $meta = json_decode($cookie, true);
        if (!$meta || empty($meta['token']) || empty($meta['evento_id'])) {
            return redirect()->route('eventos.index')->with('err','No hay una reserva activa para reanudar.');
        }

        // 3) Cargar holds vigentes
        $holds = DB::table('mesas_hold')
            ->where('hold_token', $meta['token'])
            ->where('evento_id', $meta['evento_id'])
            ->where('expires_at', '>', now())
            ->get(['escenario_id','expires_at']);

        if ($holds->isEmpty()) {
            Cookie::queue(Cookie::forget('reserva_hold'));
            return redirect()->route('mapa.publico', $meta['evento_id'])
                ->with('err', 'El tiempo de tu reserva expiró. Vuelve a elegir las mesas.');
        }

        // 4) Reconstruir sesión "reserva"
        $expiresAt = Carbon::parse($holds->min('expires_at'));
        session([
            'reserva' => [
                'evento_id' => (int)$meta['evento_id'],
                'llegada'   => $meta['llegada'] ?? '22:00',
                'mesas'     => $holds->pluck('escenario_id')->map(fn($v)=>(int)$v)->values()->all(),
                'promos'    => [],
                'hold'      => [
                    'token'      => $meta['token'],
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ]
        ]);

        // 5) Redirigir según flow original
        if (($meta['flow'] ?? 'pago') === 'promos') {
            return redirect()->route('reservas.promos')->with('ok','Reanudaste tu reserva.');
        }
        return redirect()->route('reservas.checkout')->with('ok','Reanudaste tu reserva.');
    }
}
