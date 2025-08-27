<?php

namespace App\Http\Controllers;

use App\Models\Evento;

class EventosPublicController extends Controller
{
    public function index()
    {
        $eventos = Evento::orderBy('fecha')->orderBy('hora_inicio')->get();
        return view('eventos.index', compact('eventos'));
    }
}
