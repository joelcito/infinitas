<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\Plan;
use App\Models\Servicio;
use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuscripcionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function ajaxListadoSuscripcion(Request $request)
    {
        if($request->ajax()){

            $empresa_id = $request->input('empresa');

            $empresa = Empresa::find($empresa_id);

            $suscripciones = Suscripcion::where('empresa_id', $empresa_id)
                                        ->get();

            $data['text']    = 'Se proceso con exito';
            $data['estado']  = 'success';
            $data['listado'] = view('empresa.ajaxListadoSuscripcion')
                                ->with(compact('suscripciones'))
                                ->render();

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return $data;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function guardarSuscripcion(Request $request)
    {
        if($request->all()){

            $suscripcion                               = new Suscripcion();
            $suscripcion->usuario_creador_id           = Auth::user()->id;
            $suscripcion->empresa_id                   = $request->input('empresa_id_new_plan');
            $suscripcion->plan_id                      = $request->input('plan_id_new_plan');
            $suscripcion->fecha_inicio                 = $request->input('fecha_inicio_new_plan');
            $suscripcion->fecha_fin                    = $request->input('fecha_fin_new_plan');
            $suscripcion->descripcion                  = $request->input('descripcion_new_plan');
            $suscripcion->ampliacion_cantidad_facturas = $request->input('ampliacion_cantidad_facturas_new_plan');
            $suscripcion->save();

            $data['text']    = 'Se proceso con exito';
            $data['estado']  = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return $data;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function obtenerSuscripcionVigenteEmpresa(Empresa $empresa)
    {
        $empresa_id = $empresa->id;

        $fecha_actual = date('Y-m-d H:i:s');

        $suscripcion = Suscripcion::where('empresa_id', $empresa_id)
                                // ->whereBetween($fecha_actual,['fecha_inicio','fecha_fin'])
                                ->where('fecha_inicio', '<=', $fecha_actual)
                                ->where('fecha_fin', '>=', $fecha_actual)
                                ->whereNull('estado')
                                ->first();
                                // ->toSql();

        return $suscripcion;

    }

    public function verificarRegistroServicioProductoByPlan(Plan $plan, Empresa $empresa)
    {
        $cantidadServicioProducto = Servicio::where('empresa_id', $empresa->id)
                                                    ->count();

        return $cantidadServicioProducto < $plan->cantidad_producto ? true : false;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function verificarRegistroClienteByPlan(Plan $plan, Empresa $empresa)
    {
        $cantidadCliente = Cliente::where('empresa_id', $empresa->id)->count();

        return $cantidadCliente < $plan->cantidad_clientes ? true : false;
    }

    /**
     * Update the specified resource in storage.
     */
    public function verificarRegistroFacturaByPlan(Plan $plan, Empresa $empresa, Suscripcion $suscripcion)
    {

        // dd($suscripcion, $empresa, $plan);

        $cantidadFactura = Factura::where('empresa_id', $empresa->id)->count();

        return $cantidadFactura < ($plan->cantidad_factura + $suscripcion->ampliacion_cantidad_facturas) ? true : false;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Suscripcion $suscripcion)
    {
        //
    }
}