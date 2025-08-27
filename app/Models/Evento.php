<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $table = 'eventos';

    protected $fillable = [
        'nombre','genero','tributo','fecha',
        'hora_inicio','hora_termino','imagen','id_escenario',
    ];

    protected $casts = [
        'tributo' => 'boolean',
        'fecha'   => 'date',
    ];
}
