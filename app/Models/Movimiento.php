<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movimiento extends Model
{
    use HasFactory, SoftDeletes;

    public function cantidaDisponile($sucursal_id, $servicio_id){

        $ingresos = Movimiento::where('sucursal_id', $sucursal_id)
                                    ->where('servicio_id', $servicio_id)
                                    ->where('ingreso', '>',0)
                                    ->sum('ingreso');

        $salidas = Movimiento::where('sucursal_id', $sucursal_id)
                                ->where('servicio_id', $servicio_id)
                                ->where('salida', '>',0)
                                ->sum('salida');

        return $ingresos - $salidas;

    }


}
