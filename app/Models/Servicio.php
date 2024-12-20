<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Servicio extends Model
{
    use HasFactory, SoftDeletes;

    public function siatDependeActividad(){
        return $this->belongsTo('App\Models\SiatDependeActividades', 'siat_depende_actividades_id');
    }

    public function siatProductoServicio(){
        return $this->belongsTo('App\Models\SiatProductoServicio', 'siat_producto_servicios_id');
    }

    public function siatUnidadMedida(){
        return $this->belongsTo('App\Models\SiatUnidadMedida', 'siat_unidad_medidas_id');
    }

    // public function detalles()
    // {
    //     return $this->hasMany(Detalle::class, 'servicio_id');
    // }

    // public function contarDetallesConFactura()
    // {
    //     return $this->detalles()->whereNotNull('factura_id')->count();
    // }

    // Relación: un cliente tiene muchas facturas
    public function detalles($servico_id)
    {
        return $this->join('detalles', 'detalles.servicio_id', '=', 'servicios.id')
                    ->whereNotNull('detalles.factura_id')
                    ->where('detalles.servicio_id', $servico_id)
                    ->whereNull('detalles.deleted_at')
                    ->count();
    }

    public function cantidaStrockProducto($id_servico)
    {
        $stock = DB::table('movimientos')
            ->select(DB::raw('(SUM(movimientos.ingreso) - SUM(movimientos.salida)) AS cantidad_stock'))
            ->where('movimientos.servicio_id', $id_servico)
            ->groupBy('movimientos.servicio_id')
            ->first();

        return $stock;

    }
}
