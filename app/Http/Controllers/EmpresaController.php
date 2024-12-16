<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cuis;
use App\Models\Empresa;
use App\Models\EmpresaDocumentoSector;
use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\Plan;
use App\Models\PuntoVenta;
use App\Models\Rol;
use App\Models\Servicio;
use App\Models\SiatDependeActividades;
use App\Models\SiatDocumentoSector;
use App\Models\SiatProductoServicio;
use App\Models\SiatTipoDocumentoSector;
use App\Models\SiatTipoPuntoVenta;
use App\Models\SiatUnidadMedida;
use App\Models\Sucursal;
use App\Models\Suscripcion;
use App\Models\UrlApiServicioSiat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Ui\Presets\React;
use PhpOffice\PhpSpreadsheet\Calculation\Web\Service;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpParser\Node\Expr\FuncCall;
use Maatwebsite\Excel\Facades\Excel;

class EmpresaController extends Controller
{

    public function listado(Request $request){

        $documentosSectores = SiatTipoDocumentoSector::all();

        return view('empresa.listado')->with(compact('documentosSectores', ));
    }


    /**
     * Display a listing of the resource.
     */
    public function guarda(Request $request){
        if($request->ajax()){
            // dd($request->all());
            $empresa_id = $request->input('empresa_id');

            if($empresa_id === "0"){
                $empresa                                            = new Empresa();
                $empresa->usuario_creador_id                        = Auth::user()->id;
            }else{
                $empresa                         = Empresa::find($empresa_id);
                $empresa->usuario_modificador_id = Auth::user()->id;
            }

            $empresa->nombre                                = $request->input('nombre_empresa');
            $empresa->nit                                   = $request->input('nit_empresa');
            $empresa->razon_social                          = $request->input('razon_social');
            $empresa->codigo_ambiente                       = $request->input('codigo_ambiente');
            $empresa->codigo_modalidad                      = $request->input('codigo_modalidad');
            $empresa->codigo_sistema                        = $request->input('codigo_sistema');
            $empresa->codigo_documento_sector               = $request->input('documento_sectores');
            $empresa->api_token                             = $request->input('api_token');
            $empresa->url_facturacionCodigos                = $request->input('url_fac_codigos');
            $empresa->url_facturacionSincronizacion         = $request->input('url_fac_sincronizacion');
            $empresa->url_servicio_facturacion_compra_venta = $request->input('url_fac_servicios');
            $empresa->url_facturacion_operaciones           = $request->input('url_fac_operaciones');
            $empresa->municipio                             = $request->input('municipio');
            $empresa->celular                               = $request->input('celular');
            $empresa->cafc                                  = $request->input('codigo_cafc');

            if($request->has('fila_archivo_p12')){
                // Obtén el archivo de la solicitud
                $file = $request->file('fila_archivo_p12');

                // Define el nombre del archivo y el directorio de almacenamiento
                $originalName = $file->getClientOriginalName();
                $filename     = time() . '_'. str_replace(' ', '_', $originalName);
                $directory    = 'assets/docs/certificate';

                // Guarda el archivo en el directorio especificado
                $file->move(public_path($directory), $filename);

                // Obtén la ruta completa del archivo
                $filePath = $directory . '/' . $filename;

                // Guarda la ruta del archivo en la base de datos
                $empresa->archivop12 = $filePath;
                $empresa->contrasenia = $request->input('contrasenia_archivo_p12');
            }

            if($request->has('logo_empresa')){
                $foto = $request->file('logo_empresa');

                // Define el nombre del archivo y el directorio de almacenamiento
                $originalName = $foto->getClientOriginalName();
                $filename     = time() . '_'. str_replace(' ', '_', $originalName);
                $directory    = 'assets/img';

                // Guarda el archivo en el directorio especificado
                $foto->move(public_path($directory), $filename);

                // Obtén la ruta completa del archivo
                // $filePath = $directory . '/' . $filename;
                $filePath = $filename;

                // Guarda la ruta del archivo en la base de datos
                $empresa->logo = $filePath;
                // $empresa->contrasenia = $request->input('contrasenia_archivo_p12');

            }

            if($empresa->save()){
                $data['estado'] = 'success';
                $data['text']   = 'Se creo con exito';
            }else{
                $data['text']   = 'Erro al crear';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function ajaxListado(Request $request){
        if($request->ajax()){
            $data['estado'] = 'success';
            $data['listado'] = $this->listadoArrayEmpresa();
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    protected function listadoArrayEmpresa(){
//        $empresas = Empresa::all();
        $empresas = Empresa::withCount('facturas')->get();
        return view('empresa.ajaxListado')->with(compact('empresas'))->render();
    }

    public function detalle(Request $request, $empresa_id){

        $empresa            = Empresa::find($empresa_id);

        $documentosSectores = SiatTipoDocumentoSector::all();
        $siat_tipo_ventas   = SiatTipoPuntoVenta::all();
        $roles              = Rol::all();
        $sucursales         = Sucursal::where('empresa_id', $empresa_id)->get();

        $activiadesEconomica = SiatDependeActividades::where('empresa_id', $empresa_id)->get();
        $productoServicio    = SiatProductoServicio::where('empresa_id', $empresa_id)->get();
        $unidadMedida        = SiatUnidadMedida::all();

        $planes = Plan::all();



        // $punto_ventas = PuntoVenta::where('empre')->get();


        return view('empresa.detalle')->with(compact(
            'empresa',
            'documentosSectores',
            'siat_tipo_ventas',
            'roles',
            'sucursales',
            'activiadesEconomica',
            'productoServicio',
            'unidadMedida',
            'planes'
        ));
    }

    public function ajaxListadoSucursal(Request $request){
        if($request->ajax()){

            $empresa_id = $request->input('empresa');

            $data['estado']  = 'success';
            // $sucursales      = Sucursal::all();
            $sucursales      = Sucursal::where('empresa_id', $empresa_id)
                                        ->get();
            $data['listado'] = view('empresa.ajaxListadoSucursal')->with(compact('sucursales'))->render();
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function guardaSucursal(Request $request){
        if($request->ajax()){

            $sucursal_id = $request->input('sucursal_id_sucursal');
            $usuario     = Auth::user();

            if($sucursal_id == "0"){
                $sucursal                     = new Sucursal();
                $sucursal->usuario_creador_id = $usuario->id;
            }else{
                $sucursal                         = Sucursal::find($sucursal_id);
                $sucursal->usuario_modificador_id = $usuario->id;
            }

            $sucursal->nombre             = $request->input('nombre_sucursal');
            $sucursal->codigo_sucursal    = $request->input('codigo_sucursal');
            $sucursal->direccion          = $request->input('direccion_sucursal');
            $sucursal->empresa_id         = $request->input('empresa_id_sucursal');

            if($sucursal->save()){

                $punto_venta                     = new PuntoVenta();
                $punto_venta->usuario_creador_id = Auth::user()->id;
                $punto_venta->sucursal_id        = $sucursal->id;
                $punto_venta->codigoPuntoVenta   = 0;
                $punto_venta->nombrePuntoVenta   = "PRIMER PUNTO VENTA POR DEFECTO";
                $punto_venta->tipoPuntoVenta     = "VENTANILLA INICIAL POR DEFECTO";
                $punto_venta->codigo_ambiente    = 2;
                $punto_venta->save();

                $data['estado'] = 'success';
                $data['text']   = 'Se creo con exito';
            }else{
                $data['text']   = 'Erro al crear';
                $data['estado'] = 'error';
            }

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function ajaxListadoPuntoVenta(Request $request){
        if($request->ajax()){

            $sucursal_id  = $request->input('sucursal');

            $punto_ventas = PuntoVenta::where('sucursal_id', $sucursal_id)
                                        ->get();

            $data['estado']  = 'success';
            $data['listado'] = view('empresa.ajaxListadoPuntoVenta')->with(compact('punto_ventas', 'sucursal_id'))->render();
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function crearCuis(Request $request){
        if($request->ajax()){
            $punto_venta_id = $request->input('codigo_punto_venta_id_cuis');
            $sucursal_id    = $request->input('codigo_sucursal_id_cuis');

            $punto_venta = PuntoVenta::find($punto_venta_id);
            $sucursal    = Sucursal::find($sucursal_id);
            $empresa     = Empresa::find($sucursal->empresa_id);

             // Obtener la instancia del modelo
            $urlApiServicioSiat = new UrlApiServicioSiat();
            $UrlCodigos         = $urlApiServicioSiat->getUrlCodigos($empresa->codigo_ambiente, $empresa->codigo_modalidad);

            if($UrlCodigos){

                $siat = app(SiatController::class);

                $codigoCuis = json_decode($siat->cuis(
                    $empresa->api_token,
                    $UrlCodigos->url_servicio,
                    $empresa->codigo_ambiente,
                    $empresa->codigo_modalidad,
                    $punto_venta->codigoPuntoVenta,
                    $empresa->codigo_sistema,
                    $sucursal->codigo_sucursal,
                    $empresa->nit
                ));

                if($codigoCuis->estado === "success"){
                    // dd($codigoCuis);
                    // session(['scuis'                => $codigoCuis->resultado->RespuestaCuis->codigo]);
                    // session(['sfechaVigenciaCuis'   => $codigoCuis->resultado->RespuestaCuis->fechaVigencia]);

                    if($codigoCuis->resultado->RespuestaCuis->transaccion){
                        $codigoCuisGenerado    = $codigoCuis->resultado->RespuestaCuis->codigo;
                        $fechaVigenciaGenerado = $codigoCuis->resultado->RespuestaCuis->fechaVigencia;

                        $cuisSacado = Cuis::where('punto_venta_id', $punto_venta->id)
                                            ->where('sucursal_id', $sucursal->id)
                                            ->where('codigo', $codigoCuisGenerado)
                                            ->first();

                        if(is_null($cuisSacado)){
                            $cuis                     = new Cuis();
                            $cuis->usuario_creador_id = Auth::user()->id;
                            $cuis->punto_venta_id     = $punto_venta->id;
                            $cuis->sucursal_id        = $sucursal->id;
                            $cuis->codigo             = $codigoCuisGenerado;
                            // $cuis->fechaVigencia      = $fechaVigenciaGenerado;
                            $cuis->fechaVigencia     = Carbon::parse($fechaVigenciaGenerado)->format('Y-m-d H:i:s');

                            $cuis->codigo_ambiente    = $empresa->codigo_ambiente;
                            if($cuis->save()){
                                $data['text']   = 'Se creo el CUIS con exito';
                                $data['estado'] = 'success';
                            }else{
                                $data['text']   = 'Error al crear el CUIS';
                                $data['estado'] = 'error';
                            }
                        }else{
                            $data['text']   = 'Ya existe un CUIS del punto de Venta y Sucursal';
                            $data['estado'] = 'warnig';
                        }
                    }else{
                        if(isset($codigoCuis->resultado->RespuestaCuis->codigo)){
                            if($codigoCuis->resultado->RespuestaCuis->mensajesList->codigo == 980){
                                $codigoCuisGenerado    = $codigoCuis->resultado->RespuestaCuis->codigo;
                                $fechaVigenciaGenerado = $codigoCuis->resultado->RespuestaCuis->fechaVigencia;

                                $cuisSacado = Cuis::where('punto_venta_id', $punto_venta->id)
                                                    ->where('sucursal_id', $sucursal->id)
                                                    ->where('codigo', $codigoCuisGenerado)
                                                    ->first();

                                if(is_null($cuisSacado)){
                                    $cuis                     = new Cuis();
                                    $cuis->usuario_creador_id = Auth::user()->id;
                                    $cuis->punto_venta_id     = $punto_venta->id;
                                    $cuis->sucursal_id        = $sucursal->id;
                                    $cuis->codigo             = $codigoCuisGenerado;
                                    // $cuis->fechaVigencia      = $fechaVigenciaGenerado;
                                    $cuis->fechaVigencia     = Carbon::parse($fechaVigenciaGenerado)->format('Y-m-d H:i:s');
                                    $cuis->codigo_ambiente    = $empresa->codigo_ambiente;
                                    if($cuis->save()){
                                        $data['text']   = 'Se creo el CUIS con exito';
                                        $data['estado'] = 'success';
                                    }else{
                                        $data['text']   = 'Error al crear el CUIS';
                                        $data['estado'] = 'error';
                                    }
                                }else{
                                    $data['text']   = 'Ya existe un CUIS del punto de Venta y Sucursal';
                                    $data['estado'] = 'warnig';
                                }
                            }else{
                                $data['text']   = 'Error al crear el CUIS';
                                $data['msg']    = $codigoCuis->resultado->RespuestaCuis;
                                $data['estado'] = 'error';
                            }
                        }else{
                            $data['text']   = 'Error al crear el CUIS';
                            $data['msg']    = $codigoCuis->resultado->RespuestaCuis;
                            $data['estado'] = 'error';
                        }
                    }
                }else{
                    $data['text']   = 'Error en la consulta';
                    $data['msg']    = $codigoCuis;
                    $data['estado'] = 'error';
                }
            }else{
                $data['msg']   = 'No existe el servico para la generacion el CUIS';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function guardaPuntoVenta(Request $request){
        if($request->ajax()){

            // dd($request->all());

            $empresa_id                      = $request->input('empresa_id_punto_venta');
            $sucursal_id                     = $request->input('sucursal_id_punto_venta');
            $codigo_clasificador_punto_venta = $request->input('codigo_tipo_punto_id_punto_venta');

            $empresa    = Empresa::find($empresa_id);
            $sucursal   = Sucursal::find($sucursal_id);

            $puntoVenta = PuntoVenta::where('sucursal_id', $sucursal->id)
                                    ->first();

            $cuis       = Cuis::where('punto_venta_id', $puntoVenta->id)
                              ->where('sucursal_id', $sucursal->id)
                              ->where('codigo_ambiente', $empresa->codigo_ambiente)
                              ->first();

            $tipo_punto_venta = SiatTipoPuntoVenta::where('codigo_clasificador', $codigo_clasificador_punto_venta)
                                                    ->first();

             // Obtener la instancia del modelo
            $urlApiServicioSiat = new UrlApiServicioSiat();
            $UrlOperaciones         = $urlApiServicioSiat->getUrlOperaciones($empresa->codigo_ambiente, $empresa->codigo_modalidad);
            if($UrlOperaciones){

                $descripcionPuntoVenta = $request->input('descripcion_punto_venta');
                $nombrePuntoVenta      = $request->input('nombre_punto_venta');
                $header                = $empresa->api_token;
                $url4                  = $UrlOperaciones->url_servicio;
                $codigoAmbiente        = $empresa->codigo_ambiente;
                $codigoModalidad       = $empresa->cogigo_modalidad;
                $codigoSistema         = $empresa->codigo_sistema;
                $codigoSucursal        = $sucursal->codigo_sucursal;
                $codigoTipoPuntoVenta  = $codigo_clasificador_punto_venta;
                $scuis                 = $cuis->codigo;
                $nit                   = $empresa->nit;

                $siat = app(SiatController::class);

                $puntoVentaGenerado = json_decode($siat->registroPuntoVenta(
                    $descripcionPuntoVenta,
                    $nombrePuntoVenta,
                    $header,
                    $url4,
                    $codigoAmbiente,
                    $codigoModalidad,
                    $codigoSistema,
                    $codigoSucursal,
                    $codigoTipoPuntoVenta,
                    $scuis,
                    $nit
                ));

                if($puntoVentaGenerado->estado === "success"){
                    if($puntoVentaGenerado->resultado->RespuestaRegistroPuntoVenta->transaccion){

                        $codigoPuntoVentaDevuelto        = $puntoVentaGenerado->resultado->RespuestaRegistroPuntoVenta->codigoPuntoVenta;

                        $punto_venta                     = new PuntoVenta();
                        $punto_venta->usuario_creador_id = Auth::user()->id;
                        $punto_venta->sucursal_id        = $sucursal->id;
                        $punto_venta->codigoPuntoVenta   = $codigoPuntoVentaDevuelto;
                        $punto_venta->nombrePuntoVenta   = $nombrePuntoVenta;
                        $punto_venta->tipoPuntoVenta     = $tipo_punto_venta->descripcion;
                        $punto_venta->codigo_ambiente    = $codigoAmbiente;

                        if($punto_venta->save()){
                            $data['text']   = 'Se creo el PUNTO DE VENTA con exito';
                            $data['estado'] = 'success';

                            $punto_ventas = PuntoVenta::where('sucursal_id', $sucursal->id)
                                                        ->get();
                            $data['listado'] = view('empresa.ajaxListadoPuntoVenta')->with(compact('punto_ventas','sucursal_id'))->render();

                        }else{
                            $data['text']   = 'Error al crear el PUNTO DE VENTA';
                            $data['estado'] = 'error';
                        }
                    }else{
                        $data['text']   = 'Error al crear el CUIS';
                        $data['msg']    = $puntoVentaGenerado->resultado;
                        $data['estado'] = 'error';
                    }
                }else{
                    $data['text']   = 'Error en la consulta';
                    $data['msg']    = $puntoVentaGenerado;
                    $data['estado'] = 'error';
                }
            }else{
                $data['text']   = 'No existe el servico para la generacion el CUIS';
                $data['msg']    = 'No existe el servico para la generacion el CUIS';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return $data;
    }

    // public function ajaxListadoUsuarioEmpresa(Request $request, $empresa_id){
    public function ajaxListadoUsuarioEmpresa(Request $request){
        if($request->ajax()){
            // dd($request->all(), $empresa_id);
            $empresa_id = $request->input('empresa');

            $usuarios = User::where('empresa_id', $empresa_id)
                            ->get();


            $data['listado']   = view('empresa.ajaxListadoUsuarioEmpresa')->with(compact('usuarios'))->render();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function guardarUsuarioEmpresa(Request $request){
        if($request->ajax()){

            $usuario_id = $request->input('usuario_id_new_usuario_empresa');

            if($usuario_id == "0"){
                $usuario                     = new User();
                $usuario->usuario_creador_id = Auth::user()->id;
            }else{
                $usuario                         = User::find($usuario_id);
                $usuario->usuario_modificador_id = Auth::user()->id;
            }

            $usuario->nombres            = $request->input('nombres_new_usuaio_empresa');
            $usuario->ap_paterno         = $request->input('ap_paterno_new_usuaio_empresa');
            $usuario->ap_materno         = $request->input('ap_materno_new_usuaio_empresa');
            $usuario->name               = $usuario->nombres." ".$usuario->ap_paterno." ".$usuario->ap_materno;
            $usuario->email              = $request->input('usuario_new_usuaio_empresa');

            if($request->input('contrasenia_new_usuaio_empresa') != null){
                $usuario->password           = Hash::make($request->input('contrasenia_new_usuaio_empresa'));
            }

            $usuario->empresa_id         = $request->input('empresa_id_new_usuario_empresa');
            $usuario->punto_venta_id     = $request->input('punto_venta_id_new_usuaio_empresa');
            $usuario->sucursal_id        = $request->input('sucursal_id_new_usuaio_empresa');
            $usuario->rol_id             = $request->input('rol_id_new_usuaio_empresa');
            $usuario->numero_celular     = $request->input('num_ceular_new_usuaio_empresa');

            $usuario->save();

            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return  $data;
    }

    public function ajaxListadoServicios(Request $request){
        if($request->ajax()){

            $empresa_id = $request->input('empresa');

            $servicios = Servicio::where('empresa_id', $empresa_id)
                                    ->get();

            $data['listado']   = view('empresa.ajaxListadoServicios')->with(compact('servicios'))->render();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function ajaxListadoDependeActividades(Request $request){
        if($request->ajax()){

            $empresa_id  = $request->input('empresa');
            $actividades = SiatDependeActividades::where('empresa_id', $empresa_id)
                                                ->get();
            $data['listado']   = view('empresa.ajaxListadoDependeActividades')->with(compact('actividades'))->render();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function  sincronizarActividades(Request $request){
        if($request->ajax()){

            $empresa_id     = $request->input('empresa_id_sincronizar_actividad');
            $punto_venta_id = $request->input('punto_venta_id_sincronizar_actividad');
            $sucursal_id   = $request->input('sucuarsal_id_sincronizar_actividad');

            $empresa    = Empresa::find($empresa_id);
            $puntoVenta = PuntoVenta::find($punto_venta_id);
            $sucursal  = Sucursal::find($sucursal_id);

            $cuis       = Cuis::where('punto_venta_id', $puntoVenta->id)
                              ->where('sucursal_id', $sucursal->id)
                              ->where('codigo_ambiente', $empresa->codigo_ambiente)
                              ->first();

            $urlApiServicioSiat = new UrlApiServicioSiat();
            $UrlSincronizacion  = $urlApiServicioSiat->getUrlSincronizacion($empresa->codigo_ambiente, $empresa->codigo_modalidad);

            $siat = app(SiatController::class);

            $header           = $empresa->api_token;
            $url2             = $UrlSincronizacion->url_servicio;
            $codigoAmbiente   = $empresa->codigo_ambiente;
            $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            $codigoSistema    = $empresa->codigo_sistema;
            $codigoSucursal   = $sucursal->codigo_sucursal;
            $scuis            = $cuis->codigo;
            $nit              = $empresa->nit;

            $sincronizarActiviades = json_decode($siat->sincronizarActividades(
                $header,
                $url2,
                $codigoAmbiente,
                $codigoPuntoVenta,
                $codigoSistema,
                $codigoSucursal,
                $scuis,
                $nit
            ));

            // dd(
            //     $sincronizarActiviades,
            //     $header,
            //     $url2,
            //     $codigoAmbiente,
            //     $codigoPuntoVenta,
            //     $codigoSistema,
            //     $codigoSucursal,
            //     $scuis,
            //     $nit
            // );

            if($sincronizarActiviades->estado === "success"){
                if($sincronizarActiviades->resultado->RespuestaListaActividades->transaccion){


                    $listaActividades = $sincronizarActiviades->resultado->RespuestaListaActividades->listaActividades;
                    if(is_array($listaActividades)){
                        // dd($sincronizarActiviades->resultado->RespuestaListaActividades->listaActividades);

                        foreach ($listaActividades as $key => $actividad) {
                            $codigoCaeb       = $actividad->codigoCaeb;
                            $descripcion      = $actividad->descripcion;
                            $tipoActividad    = $actividad->tipoActividad;

                            $activiadesEconomica = SiatDependeActividades::where('empresa_id',$empresa_id )
                                                                        ->where('sucursal_id',$sucursal_id )
                                                                        ->where('punto_venta_id', $punto_venta_id)
                                                                        ->where('codigo_caeb', $codigoCaeb)
                                                                        // ->where('descripcion', $descripcion)
                                                                        ->where('codigo_ambiente', $empresa->codigo_ambiente)
                                                                        ->where('tipo_actividad', $tipoActividad)
                                                                        // ->get();
                                                                        ->first();

                            if(is_null($activiadesEconomica)){

                                $activiadesEconomica                  = new SiatDependeActividades();
                                $activiadesEconomica->empresa_id      = $empresa_id;
                                $activiadesEconomica->sucursal_id     = $sucursal_id;
                                $activiadesEconomica->punto_venta_id  = $punto_venta_id;
                                $activiadesEconomica->codigo_ambiente = $empresa->codigo_ambiente;
                                $activiadesEconomica->codigo_caeb     = $codigoCaeb;
                                $activiadesEconomica->descripcion     = $descripcion;
                                $activiadesEconomica->tipo_actividad  = $tipoActividad;

                            }else{
                                $activiadesEconomica->descripcion = $descripcion;
                            }

                            $activiadesEconomica->save();
                        }

                    }else{
                        $codigoCaeb       = $listaActividades->codigoCaeb;
                        $descripcion      = $listaActividades->descripcion;
                        $tipoActividad    = $listaActividades->tipoActividad;

                        $activiadesEconomica = SiatDependeActividades::where('empresa_id',$empresa_id )
                                                                        ->where('sucursal_id',$sucursal_id )
                                                                        ->where('punto_venta_id', $punto_venta_id)
                                                                        ->where('codigo_caeb', $codigoCaeb)
                                                                        // ->where('descripcion', $descripcion)
                                                                        ->where('codigo_ambiente', $empresa->codigo_ambiente)
                                                                        ->where('tipo_actividad', $tipoActividad)
                                                                        // ->get();
                                                                        ->first();

                        if(is_null($activiadesEconomica)){

                            $activiadesEconomica                  = new SiatDependeActividades();
                            $activiadesEconomica->empresa_id      = $empresa_id;
                            $activiadesEconomica->sucursal_id     = $sucursal_id;
                            $activiadesEconomica->punto_venta_id  = $punto_venta_id;
                            $activiadesEconomica->codigo_ambiente = $empresa->codigo_ambiente;
                            $activiadesEconomica->codigo_caeb     = $codigoCaeb;
                            $activiadesEconomica->descripcion     = $descripcion;
                            $activiadesEconomica->tipo_actividad  = $tipoActividad;

                        }else{
                            $activiadesEconomica->descripcion = $descripcion;
                        }

                        $activiadesEconomica->save();
                    }

                    $actividades = SiatDependeActividades::where('empresa_id', $empresa_id)
                                                        ->where('sucursal_id', $sucursal_id)
                                                        ->where('punto_venta_id',$punto_venta_id)
                                                        ->get();

                    $data['listado']   = view('empresa.ajaxListadoActiviadesEconomicas')->with(compact('actividades', 'sucursal_id', 'punto_venta_id'))->render();
                    $data['estado'] = 'success';

                }else{
                    $data['text']    = $sincronizarActiviades->resultado->RespuestaListaActividades->mensajesList;
                    $data['estado'] = 'error';
                }
            }else{
                $data['text']   = 'Error con la funcion';
                $data['estado'] = 'error';
            }

            // dd(
            //     $sincronizarActiviades,
            //     $header,
            //     $url2,
            //     $codigoAmbiente,
            //     $codigoPuntoVenta,
            //     $codigoSistema,
            //     $codigoSucursal,
            //     $scuis,
            //     $nit
            // );

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function ajaxListadoActiviadesEconomicas(Request $request){
        if($request->ajax()){

            $empresa_id     = $request->input('empresa');
            $sucursal_id    = $request->input('sucursal_id');
            $punto_venta_id = $request->input('punto_venta_id');

            $actividades = SiatDependeActividades::where('empresa_id', $empresa_id)
                                                ->where('sucursal_id', $sucursal_id)
                                                ->where('punto_venta_id',$punto_venta_id)
                                                ->get();

            $data['listado']   = view('empresa.ajaxListadoActiviadesEconomicas')->with(compact('actividades', 'sucursal_id', 'punto_venta_id'))->render();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    function ajaxRecuperarPuntosVentasSelect(Request $request){
        if($request->ajax()){

            $sucursal_id  = $request->input('sucursal_id');
            $punto_ventas = PuntoVenta::where('sucursal_id', $sucursal_id)
                                    ->get();
            $select = '<select data-control="select2" data-placeholder="Seleccione" data-hide-search="true" class="form-select form-select-solid fw-bold" name="new_servicio_sucursal_id" id="new_servicio_sucursal_id" data-dropdown-parent="#modal_new_servicio" onchange="ajaxRecupraActividadesSelect(this)">';
            $option = '<option></option>';
            foreach ($punto_ventas as $key => $value) {
                $option = $option.'<option value="'.$value->id.'">'.$value->nombrePuntoVenta.'</option>';
            }
            $select = $select.$option.'</select>';
            $data['estado'] = 'success';
            $data['select'] = $select;
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    function  ajaxRecupraActividadesSelect(Request $request){
        if($request->ajax()){

            $punto_venta_id = $request->input('punto_venta_id');
            $empresa_id     = $request->input('empresa_id');
            $sucursal_id    = $request->input('sucursal_id');

            $activiadesEconomica = SiatDependeActividades::where('empresa_id',$empresa_id)
                                                        ->where('sucursal_id', $sucursal_id)
                                                        ->where('punto_venta_id', $punto_venta_id)
                                                        ->get();

            $select = '<select data-control="select2" data-placeholder="Seleccione" data-hide-search="true" class="form-select form-select-solid fw-bold" name="new_servicio_codigo_actividad_economica" id="new_servicio_codigo_actividad_economica" data-dropdown-parent="#modal_new_servicio">';
            $option = '<option></option>';
            foreach ($activiadesEconomica as $key => $value) {
                $option = $option.'<option value="'.$value->codigo_caeb.'">'.$value->descripcion.'</option>';
            }
            $select = $select.$option.'</select>';
            $data['estado'] = 'success';
            $data['select'] = $select;

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    function ajaxListadoSiatProductosServicios(Request $request){
        if($request->ajax()){

            $empresa_id  = $request->input('empresa_id');
            $punto_venta = $request->input('punto_venta');
            $sucursal    = $request->input('sucursal');

            $siatProductosServicios = SiatProductoServicio::where('empresa_id', $empresa_id)
                                                            ->where('punto_venta_id',$punto_venta)
                                                            ->where('sucursal_id', $sucursal)
                                                            ->get();

            $data['listado']   = view('empresa.ajaxListadoSiatProductosServicios')->with(compact('siatProductosServicios', 'punto_venta', 'sucursal'))->render();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return $data;
    }

    function sincronizarSiatProductoServicios(Request $request){
        if($request->ajax()){

            $empresa_id     = $request->input('empresa_id');
            $punto_venta_id = $request->input('punto_venta');
            $sucursal_id    = $request->input('sucursal');

            $empresa    = Empresa::find($empresa_id);
            $puntoVenta = PuntoVenta::find($punto_venta_id);
            $sucursal   = Sucursal::find($sucursal_id);

            $cuis = $empresa->cuisVigente($sucursal_id, $punto_venta_id, $empresa->codigo_ambiente);

            $urlApiServicioSiat = new UrlApiServicioSiat();
            $UrlSincronizacion  = $urlApiServicioSiat->getUrlSincronizacion($empresa->codigo_ambiente, $empresa->codigo_modalidad);

            $header           = $empresa->api_token;
            $url2             = $UrlSincronizacion->url_servicio;
            $codigoAmbiente   = $empresa->codigo_ambiente;
            $codigoPuntoVenta = $puntoVenta->codigoPuntoVenta;
            $codigoSistema    = $empresa->codigo_sistema;
            $codigoSucursal   = $sucursal->codigo_sucursal;
            $cuis             = $cuis->codigo;
            $nit              = $empresa->nit;

            $siat = app(SiatController::class);

            $sincronizarListaProductosServicios = json_decode($siat->sincronizarListaProductosServicios(
                $header,
                $url2,
                $codigoAmbiente,
                $codigoPuntoVenta,
                $codigoSistema,
                $codigoSucursal,
                $cuis,
                $nit
            ));

            // dd($sincronizarListaProductosServicios,$sincronizarListaProductosServicios->resultado->RespuestaListaProductos);

            if($sincronizarListaProductosServicios->estado === "success"){
                if($sincronizarListaProductosServicios->resultado->RespuestaListaProductos->transaccion){
                    $listaCodigo = $sincronizarListaProductosServicios->resultado->RespuestaListaProductos->listaCodigos;
                    if(is_array($listaCodigo)){

                        foreach ($listaCodigo as $key => $value) {

                            $listadoProductoServicio = SiatProductoServicio::where('empresa_id',$empresa_id)
                                                                            ->where('punto_venta_id',$punto_venta_id)
                                                                            ->where('sucursal_id',$sucursal_id)
                                                                            ->where('codigo_ambiente',$empresa->codigo_ambiente)
                                                                            ->where('codigo_actividad',$value->codigoActividad)
                                                                            ->where('codigo_producto',$value->codigoProducto)
                                                                            ->first();

                            if(is_null($listadoProductoServicio)){
                                $listadoProductoServicio                       = new SiatProductoServicio();
                                $listadoProductoServicio->usuario_creador_id   = Auth::user()->id;
                                $listadoProductoServicio->empresa_id           = $empresa_id;
                                $listadoProductoServicio->punto_venta_id       = $punto_venta_id;
                                $listadoProductoServicio->sucursal_id          = $sucursal_id;
                                $listadoProductoServicio->codigo_ambiente      = $empresa->codigo_ambiente;
                                $listadoProductoServicio->codigo_actividad     = $value->codigoActividad;
                                $listadoProductoServicio->codigo_producto      = $value->codigoProducto;
                                $listadoProductoServicio->codigo_producto      = $value->codigoProducto;
                                $listadoProductoServicio->descripcion_producto = $value->descripcionProducto;

                            }else{
                                $listadoProductoServicio->usuario_modificador_id = Auth::user()->id;
                                $listadoProductoServicio->codigo_actividad       = $value->codigoActividad;
                                $listadoProductoServicio->codigo_producto        = $value->codigoProducto;
                                $listadoProductoServicio->codigo_producto        = $value->codigoProducto;
                                $listadoProductoServicio->descripcion_producto   = $value->descripcionProducto;
                            }
                            $listadoProductoServicio->save();
                        }
                    }else{

                        $listadoProductoServicio = SiatProductoServicio::where('empresa_id',$empresa_id)
                                                                            ->where('punto_venta_id',$punto_venta_id)
                                                                            ->where('sucursal_id',$sucursal_id)
                                                                            ->where('codigo_ambiente',$empresa->codigo_ambiente)
                                                                            ->where('codigo_actividad',$listaCodigo->codigoActividad)
                                                                            ->where('codigo_producto',$listaCodigo->codigoActividad)
                                                                            ->first();

                        if(is_null($listadoProductoServicio)){
                            $listadoProductoServicio                       = new SiatProductoServicio();
                            $listadoProductoServicio->usuario_creador_id   = Auth::user()->id;
                            $listadoProductoServicio->empresa_id           = $empresa_id;
                            $listadoProductoServicio->punto_venta_id       = $punto_venta_id;
                            $listadoProductoServicio->sucursal_id          = $sucursal_id;
                            $listadoProductoServicio->codigo_ambiente      = $empresa->codigo_ambiente;
                            $listadoProductoServicio->codigo_actividad     = $listaCodigo->codigoActividad;
                            $listadoProductoServicio->codigo_producto      = $listaCodigo->codigoProducto;
                            $listadoProductoServicio->codigo_producto      = $listaCodigo->codigoProducto;
                            $listadoProductoServicio->descripcion_producto = $listaCodigo->descripcionProducto;

                        }else{
                            $listadoProductoServicio->usuario_modificador_id = Auth::user()->id;
                            $listadoProductoServicio->codigo_actividad       = $listaCodigo->codigoActividad;
                            $listadoProductoServicio->codigo_producto        = $listaCodigo->codigoProducto;
                            $listadoProductoServicio->codigo_producto        = $listaCodigo->codigoProducto;
                            $listadoProductoServicio->descripcion_producto   = $listaCodigo->descripcionProducto;
                        }
                        $listadoProductoServicio->save();
                    }

                    $siatProductosServicios = SiatProductoServicio::where('empresa_id', $empresa_id)
                                                                    ->where('punto_venta_id',$punto_venta_id)
                                                                    ->where('sucursal_id', $sucursal_id)
                                                                    ->get();

                    $punto_venta = $punto_venta_id;
                    $sucursal    = $sucursal_id;

                    $data['listado'] = view('empresa.ajaxListadoSiatProductosServicios')->with(compact('siatProductosServicios', 'punto_venta', 'sucursal'))->render();
                    $data['estado']  = 'success';
                    $data['text']    = 'Se Sincronizo con exito!';

                }else{
                    $data['text']    = $sincronizarListaProductosServicios->resultado->RespuestaListaActividades->mensajesList;
                    $data['estado'] = 'error';
                }
            }else{
                $data['text']    = $sincronizarListaProductosServicios;
                $data['estado'] = 'error';
            }

            // dd($sincronizarListaProductosServicios);

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function sincronizarPuntosVentas(Request $request){
        if($request->ajax()){

            $empresa_id  = $request->input('empresa_id');
            $sucursal_id = $request->input('sucursal');

            $empresa     = Empresa::find($empresa_id);
            $sucursal    = Sucursal::find($sucursal_id);
            $punto_venta = PuntoVenta::where('sucursal_id', $sucursal->id)
                                        ->where('codigoPuntoVenta',0)
                                        ->first();

            $urlApiServicioSiat = new UrlApiServicioSiat();
            $UrlSincronizacion  = $urlApiServicioSiat->getUrlOperaciones($empresa->codigo_ambiente, $empresa->codigo_modalidad);

            $header         = $empresa->api_token;
            $url4           = $UrlSincronizacion->url_servicio;
            $codigoAmbiente = $empresa->codigo_ambiente;
            $codigoSistema  = $empresa->codigo_sistema;
            $codigoSucursal = $sucursal->codigo_sucursal;

            $cuis  = $empresa->cuisVigente($sucursal->id, $punto_venta->id, $empresa->codigo_ambiente);

            if($cuis){
                $scuis = $cuis->codigo;
                $nit   = $empresa->nit;
                $siat = app(SiatController::class);
                $consultaPuntoVenta = json_decode($siat->consultaPuntoVenta(
                    $header,
                    $url4,
                    $codigoAmbiente,
                    $codigoSistema,
                    $codigoSucursal,
                    $scuis,
                    $nit
                ));
                if($consultaPuntoVenta->estado === "success"){
                    if($consultaPuntoVenta->resultado->RespuestaConsultaPuntoVenta->transaccion){
                        $listaPuntosVentas = $consultaPuntoVenta->resultado->RespuestaConsultaPuntoVenta->listaPuntosVentas;
                        foreach ($listaPuntosVentas as $key => $value) {

                            $puntoVenta = PuntoVenta::where('sucursal_id', $sucursal->id)
                                                    ->where('codigoPuntoVenta', $value->codigoPuntoVenta)
                                                    ->where('codigo_ambiente', $empresa->codigo_ambiente)
                                                    ->first();

                            if(is_null($puntoVenta)){
                                $puntoVenta                     = new PuntoVenta();
                                $puntoVenta->usuario_creador_id = Auth::user()->id;
                                $puntoVenta->sucursal_id        = $sucursal->id;
                                $puntoVenta->codigoPuntoVenta   = $value->codigoPuntoVenta;
                                $puntoVenta->nombrePuntoVenta   = $value->nombrePuntoVenta;
                                $puntoVenta->tipoPuntoVenta     = $value->tipoPuntoVenta;
                                $puntoVenta->codigo_ambiente    = $empresa->codigo_ambiente;
                                $puntoVenta->save();
                            }
                        }
                        $data['estado'] = 'success';
                        $sucursal_id  = $sucursal->id;
                        $punto_ventas = PuntoVenta::where('sucursal_id', $sucursal->id)
                                                    ->get();
                        $data['listado'] = view('empresa.ajaxListadoPuntoVenta')->with(compact('punto_ventas', 'sucursal_id'))->render();
                    }else{
                        $data['text']    = $consultaPuntoVenta->resultado;
                        $data['estado'] = 'error';
                    }
                }else{
                    $data['text']   = $consultaPuntoVenta;
                    $data['estado'] = 'error';
                }
            }else{
                $data['text']   = "Cuis no encontrado.";
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function guardarNewServioEmpresa(Request $request){
        if($request->ajax()){

            // dd($request->all());

            $servicio_id_new_servicio = $request->input('servicio_producto_id_new_servicio');

            if($servicio_id_new_servicio == "0"){
                $servicio                     = new Servicio();
                $servicio->usuario_creador_id = Auth::user()->id;
            }else{
                $servicio                         = Servicio::find($servicio_id_new_servicio);
                $servicio->usuario_modificador_id = Auth::user()->id;
            }

            $servicio->empresa_id                  = $request->input('empresa_id_new_servicio');
            $servicio->siat_depende_actividades_id = $request->input('actividad_economica_siat_id_new_servicio');
            $servicio->siat_producto_servicios_id  = $request->input('producto_servicio_siat_id_new_servicio');
            $servicio->siat_unidad_medidas_id      = $request->input('unidad_medida_siat_id_new_servicio');
            $servicio->descripcion                 = $request->input('descrpcion_new_servicio');
            $servicio->precio                      = $request->input('precio_new_servicio');
            $servicio->numero_serie                = $request->input('numero_serie');
            $servicio->codigo_imei                 = $request->input('codigo_imei');
            $servicio->save();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function  ajaxBuscarPuntoVentaNewUsuarioSelect(Request $request) {
        if($request->ajax()){
            $sucursal_id   = $request->input('sucursal_id');
            $puntos_ventas = PuntoVenta::where('sucursal_id', $sucursal_id)
                                        ->get();

            $select = '<select data-control="select2" data-placeholder="Seleccione" data-hide-search="true" class="form-select form-select-solid fw-bold" name="punto_venta_id_new_usuaio_empresa" id="punto_venta_id_new_usuaio_empresa" data-dropdown-parent="#modal_new_usuario">';
            $option = '<option></option>';
            foreach ($puntos_ventas as $key => $value) {
                $option = $option.'<option value="'.$value->id.'">'.$value->nombrePuntoVenta.'</option>';
            }
            $select = $select.$option.'</select>';
            $data['estado'] = 'success';
            $data['select'] = $select;
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function  ajaxListadoClientes(Request $request) {
        if($request->ajax()){

            $empresa_id = $request->input('empresa');

            $clientes = Cliente::where('empresa_id', $empresa_id)
                                ->get();

            $data['listado'] = view('empresa.ajaxListadoClientes')->with(compact('clientes'))->render();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function  guardarClienteEmpresa(Request $request){
        if($request->ajax()){

            $cliente_id = $request->input('cliente_id_cliente_new_usuaio_empresa');

            if($cliente_id == "0"){
                $cliente                     = new Cliente();
                $cliente->usuario_creador_id = Auth::user()->id;
            }else{
                $cliente                         = Cliente::find($cliente_id);
                $cliente->usuario_modificador_id = Auth::user()->id;
            }

            $cliente->empresa_id         = $request->input('empresa_id_cliente_new_usuario_empresa');
            $cliente->nombres            = $request->input('nombres_cliente_new_usuaio_empresa');
            $cliente->ap_paterno         = $request->input('ap_paterno_cliente_new_usuaio_empresa');
            $cliente->ap_materno         = $request->input('ap_materno_cliente_new_usuaio_empresa');
            $cliente->cedula             = $request->input('cedula_cliente_new_usuaio_empresa');
            $cliente->complemento        = $request->input('complemento_cliente_new_usuaio_empresa');
            $cliente->nit                = $request->input('nit_cliente_new_usuaio_empresa');
            $cliente->razon_social       = $request->input('razon_social_cliente_new_usuaio_empresa');
            $cliente->correo             = $request->input('correo_cliente_new_usuaio_empresa');
            $cliente->numero_celular     = $request->input('num_ceular_cliente_new_usuaio_empresa');
            $cliente->save();

            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function listadoClientes(Request $request){
        return view('empresa.listadoClientes');
    }

    public function ajaxListadoClientesEmpresa(Request $request){
        if($request->ajax()){

            $usuario = Auth::user();
            $empresa_id = $usuario->empresa_id;

            $clientes = Cliente::where('empresa_id', $empresa_id)->get();

            $data['listado'] = view('empresa.ajaxListadoClientesEmpresa')->with(compact('clientes'))->render();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return $data;
    }

    public function guardarClienteEmpresaEmpresa(Request $request){
        if($request->ajax()){

            $suscripcion = app(SuscripcionController::class);
            $usuario     = Auth::user();
            $empresa     = $usuario->empresa;
            $empresa_id  = $usuario->empresa_id;

            $obtenerSuscripcionVigenteEmpresa = $suscripcion->obtenerSuscripcionVigenteEmpresa($empresa);

            if($obtenerSuscripcionVigenteEmpresa){

                $empresa_id = $usuario->empresa_id;
                $plan       = $obtenerSuscripcionVigenteEmpresa->plan;

                $cliente_id                  = $request->input('cliente_id_cliente_new_usuaio_empresa') ;

                if($suscripcion->verificarRegistroClienteByPlan($plan, $empresa) || $cliente_id != "0"){

                    // $cliente                     = $cliente_id == "0" ? new Cliente() : Cliente::find($cliente_id);
                    if($cliente_id == "0"){
                        $cliente                     = new Cliente();
                        $cliente->usuario_creador_id = $usuario->id;
                    }else{
                        $cliente                         = Cliente::find($cliente_id);
                        $cliente->usuario_modificador_id = $usuario->id;
                    }

                    $cliente->empresa_id         = $empresa_id;
                    $cliente->nombres            = $request->input('nombres_cliente_new_usuaio_empresa');
                    $cliente->ap_paterno         = $request->input('ap_paterno_cliente_new_usuaio_empresa');
                    $cliente->ap_materno         = $request->input('ap_materno_cliente_new_usuaio_empresa');
                    $cliente->cedula             = $request->input('cedula_cliente_new_usuaio_empresa');
                    $cliente->complemento        = $request->input('complemento_cliente_new_usuaio_empresa');
                    $cliente->nit                = $request->input('nit_cliente_new_usuaio_empresa');
                    $cliente->razon_social       = $request->input('razon_social_cliente_new_usuaio_empresa');
                    $cliente->correo             = $request->input('correo_cliente_new_usuaio_empresa');
                    $cliente->numero_celular     = $request->input('num_ceular_cliente_new_usuaio_empresa');
                    $cliente->save();

                    $data['estado'] = 'success';
                    $data['cliente']   = $cliente->id;
                }else{
                    $data['text']   = 'Alcanzo la cantidad maxima registros de clientes, solicite un plan superior.';
                    $data['estado'] = 'error';
                }
            }else{
                $data['text']   = 'No existe suscripciones activas!, , solicite una suscripcion a un plan vigente.';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function listadoProductoServicioEmpresa(Request $request){
        $usuario    = Auth::user();
        $empresa_id = $usuario->empresa_id;
        $empresa    = $usuario->empresa;

        $documentos_sectores_asignados = $empresa->empresasDocumentos;

        $activiadesEconomica = SiatDependeActividades::where('empresa_id', $empresa_id)->get();
        $productoServicio    = SiatProductoServicio::where('empresa_id', $empresa_id)->get();
        $unidadMedida        = SiatUnidadMedida::all();

        return view('empresa.listadoProductoServicioEmpresa')->with(compact('activiadesEconomica', 'productoServicio','unidadMedida','documentos_sectores_asignados'));
    }

    public function ajaxListadoProductoServicioEmpresa(Request $request){
        if($request->ajax()){

            $usuario = Auth::user();
            $empresa_id = $usuario->empresa_id;

            $servicios = Servicio::where('empresa_id', $empresa_id)
                                    ->orderBy('id', 'desc')
                                    ->get();

            $data['listado'] = view('empresa.ajaxListadoProductoServicioEmpresa')->with(compact('servicios'))->render();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return $data;
    }

    public function guardarProductoServicioEmpresa(Request $request){
        if($request->ajax()){

            // dd($request->all());

            $suscripcion = app(SuscripcionController::class);
            $usuario     = Auth::user();
            $empresa     = $usuario->empresa;

            $obtenerSuscripcionVigenteEmpresa = $suscripcion->obtenerSuscripcionVigenteEmpresa($empresa);

            if($obtenerSuscripcionVigenteEmpresa){
                $empresa_id = $usuario->empresa_id;
                $plan       = $obtenerSuscripcionVigenteEmpresa->plan;

                $guardarProductoServicioEmpresa = $request->input('servicio_producto_id_new_servicio');

                if($suscripcion->verificarRegistroServicioProductoByPlan($plan, $empresa) || $guardarProductoServicioEmpresa != "0"){

                    if($guardarProductoServicioEmpresa == "0"){
                        $servicio                     = new Servicio();
                        $servicio->usuario_creador_id = $usuario->id;
                    }else{
                        $servicio                         = Servicio::find($guardarProductoServicioEmpresa);
                        $servicio->usuario_modificador_id = $usuario->id;
                    }
                    $servicio->empresa_id                  = $empresa_id;
                    $servicio->siat_depende_actividades_id = $request->input('actividad_economica_siat_id_new_servicio');
                    $servicio->siat_documento_sector_id    = $request->input('documento_sector_siat_id_new_servicio');
                    $servicio->siat_producto_servicios_id  = $request->input('producto_servicio_siat_id_new_servicio');
                    $servicio->siat_unidad_medidas_id      = $request->input('unidad_medida_siat_id_new_servicio');
                    $servicio->numero_serie                = $request->input('numero_serie');
                    $servicio->codigo_imei                 = $request->input('codigo_imei');
                    $servicio->descripcion                 = $request->input('descrpcion_new_servicio');
                    $servicio->precio                      = $request->input('precio_new_servicio');
                    $servicio->tipo                        = $request->input('tipo_producto_servicio');
                    $servicio->save();

                    $data['estado'] = 'success';
                }else{
                    $data['text']   = 'Alcanzo la cantidad maxima registros de producto / servicio, solicite un plan superior.';
                    $data['estado'] = 'error';
                }

            }else{
                $data['text']   = 'No existe suscripciones activas!, , solicite una suscripcion a un plan vigente.';
                $data['estado'] = 'error';
            }

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function ajaxListadoAsignacionDocumentosSectores(Request $request){

        if($request->ajax()){

            $usuario                       = Auth::user();
            $empresa_id                    = $request->input('empresa');
            $documentos_sectores_asignados = EmpresaDocumentoSector::where('empresa_id', $empresa_id)
                                                                    ->get();

            $isAdmin = $usuario->isAdmin();

            $data['listado'] = view('empresa.ajaxListadoAsignacionDocumentosSectores')->with(compact('documentos_sectores_asignados', 'isAdmin'))->render();
            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function guardarAsignacionDocumentoSector(Request $request){
        if($request->ajax()){

            $empresaDocumentoSEctor                           = new EmpresaDocumentoSector();
            $empresaDocumentoSEctor->usuario_creador_id       = Auth::user()->id;
            $empresaDocumentoSEctor->empresa_id               = $request->input('new_asignacion_empresa_id');
            $empresaDocumentoSEctor->siat_documento_sector_id = $request->input('new_asignacion_documento_sector');
            $empresaDocumentoSEctor->save();

            $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function eliminarCliente(Request $request){
        if($request->ajax()){
            $usuario    = Auth::user();
            $cliente_id = $request->input('cliente');
            $cliente    = Cliente::find($cliente_id);
            if($cliente){
                // if($cliente->empresa_id == $usuario->empresa_id){

                    $cliente->usuario_eliminador_id = $usuario->id;
                    $cliente->save();

                    Cliente::destroy($cliente_id);

                    $data['text']   = 'El cliente se elimino con exito!';
                    $data['estado'] = 'success';
                // }else{
                //     $data['text']   = 'El cliente no pertenece a la empresa';
                //     $data['estado'] = 'error';
                // }
            }else{
                $data['text']   = 'El cliente no existe';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function eliminarClienteEmpresa(Request $request){
        if($request->ajax()){
            $usuario    = Auth::user();
            $cliente_id = $request->input('cliente');
            $cliente    = Cliente::find($cliente_id);
            if($cliente){
                if($cliente->empresa_id == $usuario->empresa_id){

                    $cliente->usuario_eliminador_id = $usuario->id;
                    $cliente->save();

                    Cliente::destroy($cliente_id);

                    $data['text']   = 'El cliente se elimino con exito!';
                    $data['estado'] = 'success';
                }else{
                    $data['text']   = 'El cliente no pertenece a la empresa';
                    $data['estado'] = 'error';
                }
            }else{
                $data['text']   = 'El cliente no existe';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function eliminarServicio(Request $request){
        if($request->ajax()){
            $usuario     = Auth::user();
            $servicio_id = $request->input('servicio');
            $servicio    = Servicio::find($servicio_id);
            if($servicio){
                if($servicio->empresa_id == $usuario->empresa_id){
                    $servicio->usuario_eliminador_id = $usuario->id;
                    $servicio->save();
                    Servicio::destroy($servicio_id);
                    $data['text']   = 'El servicio se elimino con exito!';
                    $data['estado'] = 'success';
                }else{
                    $data['text']   = 'El servicio no pertenece a la empresa';
                    $data['estado'] = 'error';
                }
            }else{
                $data['text']   = 'El servicio no existe';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function eliminarServicioEmpresa(Request $request){
        if($request->ajax()){
            $usuario     = Auth::user();
            $servicio_id = $request->input('servicio');
            $servicio    = Servicio::find($servicio_id);

            if($servicio){

                $servicio->usuario_eliminador_id = $usuario->id;
                $servicio->save();

                Servicio::destroy($servicio_id);
                $data['text']   = 'El servicio se elimino con exito!';
                $data['estado'] = 'success';
            }else{
                $data['text']   = 'El servicio no existe';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function eliminarAsignaconDocumentoSector(Request $request){
        if($request->ajax()){
            $asignaicon_id = $request->input('asignacion');

            $documentosSector = EmpresaDocumentoSector::find($asignaicon_id);
            $documentosSector->usuario_eliminador_id = Auth::user()->id;
            $documentosSector->save();

            EmpresaDocumentoSector::destroy($asignaicon_id);
            $data['text']   = 'Se elimino con exito!';
            $data['estado'] = 'success';
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function detalleEmpresa(Request $request) {

        $usuario          = Auth::user();
        $empresa          = $usuario->empresa;
        $roles            = Rol::where('id', '!=', 1)->get();
        $sucursales       = Sucursal::where('empresa_id',$empresa->id)->get();
        $siat_tipo_ventas = SiatTipoPuntoVenta::all();

        // dd(
        //     $usuario,
        //     $empresa,
        //     $roles,
        //     $sucursales,
        //     $siat_tipo_ventas
        // );

        return view('empresa.detalleEmpresa')->with(compact('empresa','siat_tipo_ventas','roles','sucursales'));
    }

    public function guardaEmpresa(Request $request){

        if($request->ajax()){

            // dd($request->all());

            $usuario = Auth::user();
            $empresa = $usuario->empresa;

            $empresa_id = $empresa->id;

            $empresa                         = Empresa::find($empresa_id);
            $empresa->usuario_modificador_id = Auth::user()->id;

            // $empresa->nombre                                = $request->input('nombre_empresa');
            // $empresa->nit                                   = $request->input('nit_empresa');
            // $empresa->razon_social                          = $request->input('razon_social');
            // $empresa->codigo_ambiente                       = $request->input('codigo_ambiente');
            // $empresa->codigo_modalidad                      = $request->input('codigo_modalidad');
            // $empresa->codigo_sistema                        = $request->input('codigo_sistema');
            // $empresa->codigo_documento_sector               = $request->input('documento_sectores');
            // $empresa->api_token                             = $request->input('api_token');
            // $empresa->url_facturacionCodigos                = $request->input('url_fac_codigos');
            // $empresa->url_facturacionSincronizacion         = $request->input('url_fac_sincronizacion');
            // $empresa->url_servicio_facturacion_compra_venta = $request->input('url_fac_servicios');
            // $empresa->url_facturacion_operaciones           = $request->input('url_fac_operaciones');
            // $empresa->municipio                             = $request->input('municipio');
            // $empresa->celular                               = $request->input('celular');
            $empresa->cafc                                  = $request->input('codigo_cafc');

            if($request->has('fila_archivo_p12')){
                // Obtén el archivo de la solicitud
                $file = $request->file('fila_archivo_p12');

                // Define el nombre del archivo y el directorio de almacenamiento
                $originalName = $file->getClientOriginalName();
                $filename     = time() . '_'. str_replace(' ', '_', $originalName);
                $directory    = 'assets/docs/certificate';

                // Guarda el archivo en el directorio especificado
                $file->move(public_path($directory), $filename);

                // Obtén la ruta completa del archivo
                $filePath = $directory . '/' . $filename;

                // Guarda la ruta del archivo en la base de datos
                $empresa->archivop12 = $filePath;

                if($request->input('contrasenia_archivo_p12') != null)
                    $empresa->contrasenia = $request->input('contrasenia_archivo_p12');

            }

            if($request->has('logo_empresa')){
                $foto = $request->file('logo_empresa');

                // Define el nombre del archivo y el directorio de almacenamiento
                $originalName = $foto->getClientOriginalName();
                $filename     = time() . '_'. str_replace(' ', '_', $originalName);
                $directory    = 'assets/img';

                // Guarda el archivo en el directorio especificado
                $foto->move(public_path($directory), $filename);

                // Obtén la ruta completa del archivo
                $filePath = $filename;

                // Guarda la ruta del archivo en la base de datos
                $empresa->logo = $filePath;

            }

            if($empresa->save()){
                $data['estado'] = 'success';
                $data['text']   = 'Se creo con exito';
            }else{
                $data['text']   = 'Erro al crear';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return $data;

    }

    public function guardarPuntoVentaEmpresa(Request $request){
        if($request->ajax()){

            $usuario        = Auth::user();
            $empresa        = $usuario->empresa;

            // dd($usuario, $request->all());

            $suscripcion = app(SuscripcionController::class);

            $obtenerSuscripcionVigenteEmpresa = $suscripcion->obtenerSuscripcionVigenteEmpresa($empresa);

            if($obtenerSuscripcionVigenteEmpresa){

                $sucursal_id = $usuario->sucursal_id;
                $plan        = $obtenerSuscripcionVigenteEmpresa->plan;
                $sucursal    = Sucursal::find($sucursal_id);

                if($suscripcion->verificarRegistroPuntoVentaByPlan($plan, $sucursal)){
                    $empresa_id                      = $empresa->id;

                    // $empresa_id                      = $request->input('empresa_id_punto_venta');
                    // $sucursal_id                     = $request->input('sucursal_id_punto_venta');
                    $codigo_clasificador_punto_venta = $request->input('codigo_tipo_punto_id_punto_venta');

                    $puntoVenta = PuntoVenta::where('sucursal_id', $sucursal->id)
                                            ->first();

                    $cuis       = Cuis::where('punto_venta_id', $puntoVenta->id)
                                    ->where('sucursal_id', $sucursal->id)
                                    ->where('codigo_ambiente', $empresa->codigo_ambiente)
                                    ->first();

                    $urlApiServicioSiat = new UrlApiServicioSiat();
                    // $UrlCodigos         = $urlApiServicioSiat->getUrlCodigos($empresa->codigo_ambiente, $empresa->codigo_modalidad);
                    $UrlOperaciones     = $urlApiServicioSiat->getUrlOperaciones($empresa->codigo_ambiente, $empresa->codigo_modalidad);

                    // dd(
                    //     $empresa->url_facturacion_operaciones,
                    //     $empresa->codigo_ambiente,
                    //     $empresa->cogigo_modalidad,
                    //     $empresa
                    // );

                    $descripcionPuntoVenta = $request->input('descripcion_punto_venta');
                    $nombrePuntoVenta      = $request->input('nombre_punto_venta');
                    $header                = $empresa->api_token;
                    $url4                  = $UrlOperaciones->url_servicio;
                    $codigoAmbiente        = $empresa->codigo_ambiente;
                    $codigoModalidad       = $empresa->cogigo_modalidad;
                    $codigoSistema         = $empresa->codigo_sistema;
                    $codigoSucursal        = $sucursal->codigo_sucursal;
                    $codigoTipoPuntoVenta  = $codigo_clasificador_punto_venta;
                    $scuis                 = $cuis->codigo;
                    $nit                   = $empresa->nit;

                    // dd(
                    //     $descripcionPuntoVenta,
                    //     $nombrePuntoVenta,
                    //     $header,
                    //     $url4,
                    //     $codigoAmbiente,
                    //     $codigoModalidad,
                    //     $codigoSistema,
                    //     $codigoSucursal,
                    //     $codigoTipoPuntoVenta,
                    //     $scuis,
                    //     $nit
                    // );

                    $siat = app(SiatController::class);

                    $puntoVentaGenerado = json_decode($siat->registroPuntoVenta(
                        $descripcionPuntoVenta,
                        $nombrePuntoVenta,
                        $header,
                        $url4,
                        $codigoAmbiente,
                        $codigoModalidad,
                        $codigoSistema,
                        $codigoSucursal,
                        $codigoTipoPuntoVenta,
                        $scuis,
                        $nit
                    ));

                    // dd($puntoVentaGenerado);

                    if($puntoVentaGenerado->estado === "success"){
                        if($puntoVentaGenerado->resultado->RespuestaRegistroPuntoVenta->transaccion){
                            $codigoPuntoVentaDevuelto        = $puntoVentaGenerado->resultado->RespuestaRegistroPuntoVenta->codigoPuntoVenta;

                            $punto_venta                     = new PuntoVenta();
                            $punto_venta->usuario_creador_id = Auth::user()->id;
                            $punto_venta->sucursal_id        = $sucursal->id;
                            $punto_venta->codigoPuntoVenta   = $codigoPuntoVentaDevuelto;
                            $punto_venta->nombrePuntoVenta   = $nombrePuntoVenta;
                            $punto_venta->tipoPuntoVenta     = $codigo_clasificador_punto_venta;
                            $punto_venta->codigo_ambiente    = $codigoAmbiente;

                            if($punto_venta->save()){
                                $data['text']   = 'Se creo el PUNTO DE VENTA con exito';
                                $data['estado'] = 'success';

                                $punto_ventas = PuntoVenta::where('sucursal_id', $sucursal->id)
                                                            ->get();
                                $data['listado'] = view('empresa.ajaxListadoPuntoVenta')->with(compact('punto_ventas', 'sucursal_id'))->render();

                            }else{
                                $data['text']   = 'Error al crear el PUNTO DE VENTA';
                                $data['estado'] = 'error';
                            }
                        }else{
                            $data['text']   = 'Error al crear el CUIS';
                            $data['msg']    = $puntoVentaGenerado->resultado;
                            $data['estado'] = 'error';
                        }
                    }else{
                        $data['text']   = 'Error en la consulta';
                        $data['msg']    = $puntoVentaGenerado;
                        $data['estado'] = 'error';
                    }
                }else{
                    $data['text']   = 'Alcanzo la cantidad maxima registros de puntos de ventas, solicite un plan superior.';
                    $data['estado'] = 'error_sus';
                }
            }else{
                $data['text']   = 'No existe suscripciones activas!, , solicite una suscripcion a un plan vigente.';
                $data['estado'] = 'error_sus';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function guardaSucursalEmpresa(Request $request){
        if($request->ajax()){

            // dd($request->all());

            $sucursal_id = $request->input('sucursal_id_sucursal');
            $usuario     = Auth::user();
            $empresa     = $usuario->empresa;

            $suscripcion = app(SuscripcionController::class);
            $obtenerSuscripcionVigenteEmpresa = $suscripcion->obtenerSuscripcionVigenteEmpresa($empresa);

            if($obtenerSuscripcionVigenteEmpresa){

                // $sucursal_id = $usuario->sucursal_id;
                $plan        = $obtenerSuscripcionVigenteEmpresa->plan;
                $sucursal    = Sucursal::find($sucursal_id);

                if($suscripcion->verificarRegistroSucursalByPlan($plan, $empresa) || $sucursal_id != "0"){

                    if($sucursal_id == "0"){
                        $sucursal                     = new Sucursal();
                        $sucursal->usuario_creador_id = $usuario->id;
                    }else{
                        $sucursal                         = Sucursal::find($sucursal_id);
                        $sucursal->usuario_modificador_id = $usuario->id;
                    }

                    $sucursal->nombre             = $request->input('nombre_sucursal');
                    $sucursal->codigo_sucursal    = $request->input('codigo_sucursal');
                    $sucursal->direccion          = $request->input('direccion_sucursal');
                    $sucursal->empresa_id         = $request->input('empresa_id_sucursal');

                    if($sucursal->save()){

                        $punto_venta                     = new PuntoVenta();
                        $punto_venta->usuario_creador_id = Auth::user()->id;
                        $punto_venta->sucursal_id        = $sucursal->id;
                        $punto_venta->codigoPuntoVenta   = 0;
                        $punto_venta->nombrePuntoVenta   = "PRIMER PUNTO VENTA POR DEFECTO";
                        $punto_venta->tipoPuntoVenta     = "VENTANILLA INICIAL POR DEFECTO";
                        $punto_venta->codigo_ambiente    = 2;
                        $punto_venta->save();

                        $data['estado'] = 'success';
                        $data['text']   = 'Se creo con exito';
                    }else{
                        $data['text']   = 'Erro al crear';
                        $data['estado'] = 'error';
                    }
                }else{
                    $data['text']   = 'Alcanzo la cantidad maxima registros de sucursales, solicite un plan superior.';
                    $data['estado'] = 'error_sus';
                }
            }else{
                $data['text']   = 'No existe suscripciones activas!, , solicite una suscripcion a un plan vigente.';
                $data['estado'] = 'error_sus';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function eliminarSucursalEmpresa(Request $request){
        if($request->ajax()){

            $usuario     = Auth::user();
            $empresa     = $usuario->empresa;
            $sucursal_id = $request->input('sucursal');

            $sucursal = Sucursal::find($sucursal_id);

            if($sucursal->empresa_id = $empresa->id){

                $sucursal->usuario_eliminador_id = $usuario->id;
                $sucursal->save();

                Sucursal::destroy($sucursal_id);

                $data['text']   = 'Se elimino con exito!';
                $data['estado'] = 'success';

            }else{
                $data['text']   = 'No existe el sucursal!.';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function eliminarSucursal(Request $request){
        if($request->ajax()){

            $usuario     = Auth::user();
            $sucursal_id = $request->input('sucursal');

            $sucursal = Sucursal::find($sucursal_id);

                $sucursal->usuario_eliminador_id = $usuario->id;
                $sucursal->save();

                Sucursal::destroy($sucursal_id);

                $data['text']   = 'Se elimino con exito!';
                $data['estado'] = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function guardarUsuarioEmpresaEmpresa(Request $request){
        if($request->ajax()){

            $usuarioLogeado = Auth::user();
            $empresa        = $usuarioLogeado->empresa;
            $usuario_new_id = $request->input('usuario_id_new_usuario_empresa');

            $suscripcion = app(SuscripcionController::class);
            $obtenerSuscripcionVigenteEmpresa = $suscripcion->obtenerSuscripcionVigenteEmpresa($empresa);

            if($obtenerSuscripcionVigenteEmpresa){
                $plan        = $obtenerSuscripcionVigenteEmpresa->plan;
                if($suscripcion->verificarRegistroUsuarioByPlan($plan, $empresa) || $usuario_new_id != "0"){

                    if($usuario_new_id != "0"){
                        $usuario                         = User::find($usuario_new_id);
                        $usuario->usuario_modificador_id = $usuarioLogeado->id;
                    }else{
                        $usuario                     = new User();
                        $usuario->usuario_creador_id = $usuarioLogeado->id;
                    }

                    $usuario->nombres            = $request->input('nombres_new_usuaio_empresa');
                    $usuario->ap_paterno         = $request->input('ap_paterno_new_usuaio_empresa');
                    $usuario->ap_materno         = $request->input('ap_materno_new_usuaio_empresa');
                    $usuario->name               = $usuario->nombres." ".$usuario->ap_paterno." ".$usuario->ap_materno;
                    $usuario->email              = $request->input('usuario_new_usuaio_empresa');
                    $usuario->empresa_id         = $empresa->id;

                    // if(!$request->has('contrasenia_new_usuaio_empresa')){
                    if($request->input('contrasenia_new_usuaio_empresa') != null){
                        $usuario->password           = Hash::make($request->input('contrasenia_new_usuaio_empresa'));
                    }

                    $usuario->punto_venta_id     = $request->input('punto_venta_id_new_usuaio_empresa');
                    $usuario->sucursal_id        = $request->input('sucursal_id_new_usuaio_empresa');
                    $usuario->rol_id             = $request->input('rol_id_new_usuaio_empresa');
                    $usuario->numero_celular     = $request->input('num_ceular_new_usuaio_empresa');

                    $usuario->save();

                    $data['estado'] = 'success';

                }else{
                    $data['text']   = 'Alcanzo la cantidad maxima registros de usuarios, solicite un plan superior.';
                    $data['estado'] = 'error_sus';
                }
            }else{
                $data['text']   = 'No existe suscripciones activas!, , solicite una suscripcion a un plan vigente.';
                $data['estado'] = 'error_sus';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return  $data;
    }

    public function exportarServicoProductoExcel(Request $request){

        if($request->ajax()){

            // dd($request->all());

            $usuario = Auth::user();
            $empresa = $usuario->empresa;

            $servicios = Servicio::select(
                                    'servicios.id as servicio_id',
                                    'servicios.descripcion as descripcion_servicio',
                                    'servicios.precio as servicio_precio',
                                    'servicios.numero_serie',
                                    'servicios.codigo_imei',

                                    'siat_depende_actividades.descripcion as descripcion_actividad_economica',
                                    'siat_depende_actividades.codigo_caeb',
                                    'siat_depende_actividades.tipo_actividad',

                                    'siat_producto_servicios.descripcion_producto as descripcion_producto_siat',
                                    'siat_producto_servicios.codigo_producto as codigo_producto_siat',
                                    'siat_producto_servicios.codigo_actividad as codigo_actividad_siat',

                                    'siat_unidad_medidas.codigo_clasificador as codigo_clasificador_um',
                                    'siat_unidad_medidas.descripcion as descripcion_unidad_medida',

                                    'siat_tipo_documento_sectores.descripcion as descripcion_tipo_sector'
                                )
                                ->join('siat_depende_actividades', 'siat_depende_actividades.id','=','servicios.siat_depende_actividades_id')
                                ->join('siat_producto_servicios', 'siat_producto_servicios.id', '=', 'servicios.siat_producto_servicios_id')
                                ->join('siat_unidad_medidas', 'siat_unidad_medidas.id', '=', 'servicios.siat_unidad_medidas_id')
                                ->join('siat_tipo_documento_sectores', 'siat_tipo_documento_sectores.id', '=', 'servicios.siat_documento_sector_id')
                                ->where('servicios.empresa_id', $empresa->id)
                                ->get();

            // generacion del excel
            $fileName = 'ServiciosProductos.xlsx';
            $libro = new Spreadsheet();
            $hoja = $libro->getActiveSheet();

            $hoja->setCellValue('A1', "SERVIOS PRODUCTOS");

            $hoja->setCellValue('A2', "SERVICIO_ID");
            $hoja->setCellValue('B2', "DESCRIPCION SERVICIO / PRODCUTO");
            $hoja->setCellValue('C2', "PRECIO");
            $hoja->setCellValue('D2', "NUMERO SERIE");
            $hoja->setCellValue('E2', "CODIGO IMEI");
            $hoja->setCellValue('F2', "ACTIVIDAD ECONOMICA");
            $hoja->setCellValue('G2', "CODIGO CAEB");
            $hoja->setCellValue('H2', "TIPO ACTIVIDAD");
            $hoja->setCellValue('I2', "DESCRIPCION PRODUCTO SIAT");
            $hoja->setCellValue('J2', "CODIGO PRODUCTO SIAT");
            $hoja->setCellValue('K2', "CODIGO ACTIVIDAD SIAT");
            $hoja->setCellValue('L2', "CODIGO UNIDAD MEDIDA SIAT");
            $hoja->setCellValue('M2', "UNIDAD MEDIDA");
            $hoja->setCellValue('N2', "DOCUMENTO SECTOR");
            // $hoja->setCellValue('O2', "PRUEBA");


            $encabezadoStyle =[
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ];

            $hoja->mergeCells('A1:N1');
            $hoja->getStyle('A1')->applyFromArray($encabezadoStyle);

            $encabezadoStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFFFE0B2', // Color de fondo
                    ],
                ],
            ];



            $hoja->getStyle('A2:N2')->applyFromArray($encabezadoStyle);

            $contadorInicio = 3;
            foreach ($servicios as $key => $s) {

                $hoja->setCellValue('A'.$contadorInicio, $s->servicio_id);
                $hoja->setCellValue('B'.$contadorInicio, $s->descripcion_servicio);
                $hoja->setCellValue('C'.$contadorInicio, $s->servicio_precio);
                $hoja->setCellValue('D'.$contadorInicio, $s->numero_serie);
                $hoja->setCellValue('E'.$contadorInicio, $s->codigo_imei);
                $hoja->setCellValue('F'.$contadorInicio, $s->descripcion_actividad_economica);
                $hoja->setCellValue('G'.$contadorInicio, $s->codigo_caeb);
                $hoja->setCellValue('H'.$contadorInicio, $s->tipo_actividad);
                $hoja->setCellValue('I'.$contadorInicio, $s->descripcion_producto_siat);
                $hoja->setCellValue('J'.$contadorInicio, $s->codigo_producto_siat);
                $hoja->setCellValue('K'.$contadorInicio, $s->codigo_actividad_siat);
                $hoja->setCellValue('L'.$contadorInicio, $s->codigo_clasificador_um);
                $hoja->setCellValue('M'.$contadorInicio, $s->descripcion_unidad_medida);
                $hoja->setCellValue('N'.$contadorInicio, $s->descripcion_tipo_sector);

                $contadorInicio++;

            }

            // Aplicar bordes a las celdas de datos
            $hoja->getStyle('A3:N'.($contadorInicio-1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);

            // Establecer los encabezados para forzar la descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'. $fileName .'"');
            header('Cache-Control: max-age=0');

            // Guardar el archivo
            $writer = new Xlsx($libro);
            $writer->save('php://output');
            exit;

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

    }

    public function descargarFormatoImportarExcel(Request $request){
        if($request->ajax()){

            // generacion del excel
            $fileName = 'ImportarServiciosProductos.xlsx';
            $libro = new Spreadsheet();
            $hoja = $libro->getActiveSheet();

            $hoja->getColumnDimension('A')->setWidth(40);
            $hoja->getColumnDimension('B')->setWidth(50);
            $hoja->getColumnDimension('C')->setWidth(20);
            $hoja->getColumnDimension('D')->setWidth(20);
            $hoja->getColumnDimension('E')->setWidth(20);

            // Crear una hoja separada para los valores de la lista desplegable
            $hojaLista = $libro->createSheet();
            $hojaLista->setTitle('ListaUnidadesMedidas');

            // Insertar los valores de la lista desplegable en la hoja "ListaValores"
            $unidadesMedidas = SiatUnidadMedida::orderBy('descripcion', 'asc')
                                                ->pluck('descripcion')
                                                ->toArray();

            // $valoresLista = ['Valor 1', 'Valor 2', 'Valor 3', 'Valor 4'];
            $valoresLista = $unidadesMedidas;
            foreach ($valoresLista as $key => $valor)
                $hojaLista->setCellValue('A' . ($key + 1), $valor);

            // Definir la lista de valores como un rango en la hoja activa (por ejemplo, "A1:A4" de la hoja "ListaValores")
            $listaRango = 'ListaUnidadesMedidas!$A$1:$A$' . count($valoresLista);

            $hoja->setCellValue('A1', "SERVICIOS / PRODUCTOS PARA IMPORTAR AL SISTEMA ");

            $hoja->setCellValue('A2', "UNIDAD DE MEDIDA");
            $hoja->setCellValue('B2', "DESCRIPCION DEL SERVICIO / PRODUCTO");
            $hoja->setCellValue('C2', "PRECIO DEL SERVICIO / PRODUCTO");
            $hoja->setCellValue('D2', "NUMERO SERIE");
            $hoja->setCellValue('E2', "CODIGO IMEI");


            $encabezadoStyle =[
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ];

            $hoja->mergeCells('A1:E1');
            $hoja->getStyle('A1')->applyFromArray($encabezadoStyle);

            // Aplicar márgenes y formato a los encabezados
            $encabezadoStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFFFE0B2', // Color de fondo
                    ],
                ],
            ];
            $hoja->getStyle('A2:E2')->applyFromArray($encabezadoStyle);

            $contadorCeldas = 3;
            for($i = 1 ; $i <= 1000 ; $i++){

                // de aqui el seleccionable
                $validacion = $hoja->getCell('A' . $i)->getDataValidation();
                $validacion->setType(DataValidation::TYPE_LIST);
                $validacion->setErrorStyle(DataValidation::STYLE_STOP);
                $validacion->setAllowBlank(true);
                $validacion->setShowInputMessage(true);
                $validacion->setShowErrorMessage(true);
                $validacion->setShowDropDown(true);
                $validacion->setErrorTitle('Valor inválido');
                $validacion->setError('El valor ingresado no es válido.');
                $validacion->setPromptTitle('Seleccione de la lista');
                $validacion->setPrompt('Seleccione un valor de la lista.');
                $validacion->setFormula1($listaRango);

            }

            // Proteger la hoja con la lista para evitar modificaciones
            $hojaLista->getProtection()->setSheet(true);
            $hojaLista->getProtection()->setSort(true);
            $hojaLista->getProtection()->setInsertRows(true);
            $hojaLista->getProtection()->setFormatCells(true);

            // Aplicar bordes a las celdas de datos
            $hoja->getStyle('A3:N'.($contadorCeldas-1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);

            // Establecer los encabezados para forzar la descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'. $fileName .'"');
            header('Cache-Control: max-age=0');

            // Guardar el archivo
            $writer = new Xlsx($libro);
            $writer->save('php://output');
            exit;



        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function importarServiciosProductosExcel(Request $request){

        if($request->ajax()){

            // dd($request->all());

            // Validación de los campos
            $rules = [
                'documento_sector_siat_id_importar_excel'    => 'required|integer',
                'actividad_economica_siat_id_importar_excel' => 'required|integer',
                'producto_servicio_siat_id_importar_excel'   => 'required|integer',
                'archivo_excel_importar_excel'               => 'required|file|mimes:xlsx,xls',   // Validación para archivos Excel
            ];

            $messages = [
                'archivo_excel_importar_excel.required' => 'El archivo Excel es obligatorio.',
                'archivo_excel_importar_excel.mimes'    => 'Solo se permiten archivos Excel (.xlsx, .xls).',
            ];

            // Aplicar la validación
            $validated = $request->validate($rules, $messages);

            // Obtener el archivo subido
            $archivo = $request->file('archivo_excel_importar_excel');

            // Lee el archivo Excel subido y obtiene todas las filas en un array
            $rows = Excel::toArray([], $archivo);

            $datosErroneos = [];

            $usuario                     = Auth::user();
            $documento_sector_siat_id    = $request->input('documento_sector_siat_id_importar_excel');
            $actividad_economica_siat_id = $request->input('actividad_economica_siat_id_importar_excel');
            $producto_servicio_siat_id   = $request->input('producto_servicio_siat_id_importar_excel');

            // Itera sobre cada fila a partir de la fila 3
            foreach ($rows[0] as $key => $row) {
                // Comienza desde la fila 3 (índice 2, porque el índice comienza en 0)
                if ($key >= 2) {
                    // $row es un array con los datos de cada celda en la fila
                    // echo 'Fila ' . ($key + 1) . ': ' . implode(', ', $row) .'|'.$row[0] .'<br>';
                    $unidadMedida = $row[0];
                    $descripcion  = $row[1];
                    $precio       = $row[2];
                    $numero_serie = $row[3];
                    $codigo_imei  = $row[4];

                    if(!empty($unidadMedida) && !empty($descripcion) && !empty($precio)){
                        if(is_numeric($precio)){
                            $unidadMedidaDataBase = SiatUnidadMedida::where('descripcion', $unidadMedida)->first();
                            if($unidadMedidaDataBase){
                                $servicio                              = new Servicio();
                                $servicio->usuario_creador_id          = $usuario->id;
                                $servicio->empresa_id                  = $usuario->empresa->id;

                                $servicio->siat_depende_actividades_id = $actividad_economica_siat_id;
                                $servicio->siat_producto_servicios_id  = $producto_servicio_siat_id;
                                $servicio->siat_unidad_medidas_id      = $unidadMedidaDataBase->id;
                                $servicio->siat_documento_sector_id    = $documento_sector_siat_id;

                                $servicio->descripcion                 = $descripcion;
                                $servicio->precio                      = $precio;
                                $servicio->numero_serie                = $numero_serie;
                                $servicio->codigo_imei                 = $codigo_imei;

                                $servicio->save();
                            }else{
                                $h = [
                                    'texto' => 'Unidad de Medida no encontrada',
                                    'fila'  => $row,
                                    'numero' => ($key+1)
                                ];
                                $datosErroneos[] = $h;
                            }
                        }else{
                            $h = [
                                'texto' => 'El precio debe ser numerico',
                                'fila'  => $row,
                                'numero' => ($key+1)
                            ];
                            $datosErroneos[] = $h;
                        }
                    }else{
                        $h = [
                            'texto' => 'Fila enviado con vacios o nulos',
                            'fila'  => $row,
                            'numero' => ($key+1)
                        ];
                        $datosErroneos[] = $h;
                    }
                }
            }
            if(count($datosErroneos)>0){
                $data['text']    = 'Se registro con exito, pero hay observaciones';
                $data['estado']  = 'warnig';
                $data['errores'] = $datosErroneos;
            }else{
                $data['text']   = 'Se registro con extio';
                $data['estado'] = 'success';
            }
        }else{
            $data['text']    = 'No existe';
            $data['estado']  = 'error';
        }
        return $data;
    }

    public function eliminarUsuario(Request $request){
        if($request->ajax()){

            $usuarioSession = Auth::user();
            $usuario_id     = $request->input('usuario');

            $usuario = User::find($usuario_id);

            if($usuario){

                $usuario->usuario_eliminador_id = $usuarioSession->id;
                $usuario->save();

                User::destroy($usuario_id);

                $data['text']    = 'Usuario eliminado con exito!';
                $data['estado']  = 'success';

            }else{
                $data['text']    = 'Usuario no existente';
                $data['estado']  = 'error';
            }
        }else{
            $data['text']    = 'No existe';
            $data['estado']  = 'error';
        }
        return $data;
    }

    public function eliminarUsuarioEmpresa(Request $request){
        if($request->ajax()){

            $usuarioSession = Auth::user();
            $empresa        = $usuarioSession->empresa;
            $usuario_id     = $request->input('usuario');
            $usuario        = User::find($usuario_id);

            if($usuario){
                if($usuario->empresa){
                    if($empresa->id == $usuario->empresa->id){
                        $usuario->usuario_eliminador_id = $usuarioSession->id;
                        $usuario->save();

                        User::destroy($usuario_id);

                        $data['text']    = 'Usuario eliminado con exito!';
                        $data['estado']  = 'success';
                    }else{
                        $data['text']    = 'Usuario no existente';
                        $data['estado']  = 'error';
                    }
                }else{
                    $data['text']    = 'Empresa no existente';
                    $data['estado']  = 'error';
                }
            }else{
                $data['text']    = 'Usuario no existente';
                $data['estado']  = 'error';
            }
        }else{
            $data['text']    = 'No existe';
            $data['estado']  = 'error';
        }
        return $data;
    }

    public function expoartarExcelClientes(Request $request){

        if($request->ajax()){

            // dd($request->all());

            $usuario = Auth::user();
            $empresa = $usuario->empresa;

            $clientes = Cliente::select(
                                'id',
                                'nombres',
                                'ap_paterno',
                                'ap_materno',
                                'cedula',
                                'complemento',
                                'nit',
                                'razon_social',
                                'correo',
                                'numero_celular'
                                )
                                ->where('empresa_id', $empresa->id)
                                ->get();


            // generacion del excel
            $fileName = 'Clientes.xlsx';
            $libro = new Spreadsheet();
            $hoja = $libro->getActiveSheet();

            $hoja->setCellValue('A1', "LISTADO DE CLIENTES");

            $hoja->setCellValue('A2', "CLIENTE_ID");
            $hoja->setCellValue('B2', "NOMBRE");
            $hoja->setCellValue('C2', "AP PATERNO");
            $hoja->setCellValue('D2', "AP MATERNO");
            $hoja->setCellValue('E2', "CEDULA");
            $hoja->setCellValue('F2', "COMPLEMENTO");
            $hoja->setCellValue('G2', "NIT");
            $hoja->setCellValue('H2', "RAZON SOCIAL");
            $hoja->setCellValue('I2', "CORREO ");
            $hoja->setCellValue('J2', "NUMERO CELULAR");

            $encabezadoStyle =[
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ];

            $hoja->mergeCells('A1:J1');
            $hoja->getStyle('A1')->applyFromArray($encabezadoStyle);

            $encabezadoStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FFFFE0B2', // Color de fondo
                    ],
                ],
            ];



            $hoja->getStyle('A2:J2')->applyFromArray($encabezadoStyle);

            $contadorInicio = 3;
            foreach ($clientes as $key => $c) {

                $hoja->setCellValue('A'.$contadorInicio, $c->id);
                $hoja->setCellValue('B'.$contadorInicio, $c->nombres);
                $hoja->setCellValue('C'.$contadorInicio, $c->ap_paterno);
                $hoja->setCellValue('D'.$contadorInicio, $c->ap_materno);
                $hoja->setCellValue('E'.$contadorInicio, $c->cedula);
                $hoja->setCellValue('F'.$contadorInicio, $c->complemento);
                $hoja->setCellValue('G'.$contadorInicio, $c->nit);
                $hoja->setCellValue('H'.$contadorInicio, $c->razon_social);
                $hoja->setCellValue('I'.$contadorInicio, $c->correo);
                $hoja->setCellValue('J'.$contadorInicio, $c->numero_celular);

                $contadorInicio++;

            }

            // Aplicar bordes a las celdas de datos
            $hoja->getStyle('A3:J'.($contadorInicio-1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);

            // Establecer los encabezados para forzar la descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'. $fileName .'"');
            header('Cache-Control: max-age=0');

            // Guardar el archivo
            $writer = new Xlsx($libro);
            $writer->save('php://output');
            exit;

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

    }

    public function guardarNewServioEmpresaFormularioFacturacion(Request $request){
        if($request->ajax()){

            // dd($request->all());

            $suscripcion = app(SuscripcionController::class);
            $usuario     = Auth::user();
            $empresa     = $usuario->empresa;

            $obtenerSuscripcionVigenteEmpresa = $suscripcion->obtenerSuscripcionVigenteEmpresa($empresa);

            if($obtenerSuscripcionVigenteEmpresa){
                $empresa_id = $usuario->empresa_id;
                $plan       = $obtenerSuscripcionVigenteEmpresa->plan;

                $guardarProductoServicioEmpresa = "0"; // PORQUE SIEMPRE VA AGREGAR

                if($suscripcion->verificarRegistroServicioProductoByPlan($plan, $empresa) || $guardarProductoServicioEmpresa != "0"){

                    if($guardarProductoServicioEmpresa == "0"){
                        $servicio                     = new Servicio();
                        $servicio->usuario_creador_id = $usuario->id;
                    }else{
                        $servicio                         = Servicio::find($guardarProductoServicioEmpresa);
                        $servicio->usuario_modificador_id = $usuario->id;
                    }
                    // $servicio                              = $guardarProductoServicioEmpresa == "0" ? new Servicio()  : Servicio::find($guardarProductoServicioEmpresa);
                    $servicio->empresa_id                  = $empresa_id;
                    $servicio->siat_depende_actividades_id = $request->input('actividad_economica_siat_id_new_servicio');
                    $servicio->siat_documento_sector_id    = $request->input('documento_sector_siat_id_new_servicio');
                    $servicio->siat_producto_servicios_id  = $request->input('producto_servicio_siat_id_new_servicio');
                    $servicio->siat_unidad_medidas_id      = $request->input('unidad_medida_siat_id_new_servicio');
                    $servicio->numero_serie                = $request->input('numero_serie');
                    $servicio->codigo_imei                 = $request->input('codigo_imei');
                    $servicio->descripcion                 = $request->input('descrpcion_new_servicio');
                    $servicio->precio                      = $request->input('precio_new_servicio');
                    $servicio->save();

                    $servicios = Servicio::select('*')
                                        ->where('empresa_id', $empresa_id)
                                        ->where('siat_documento_sector_id', $servicio->siat_documento_sector_id)
                                        ->get();

                    $data['servicio'] = $servicios;
                    $data['estado']   = 'success';

                }else{
                    $data['text']   = 'Alcanzo la cantidad maxima registros de producto / servicio, solicite un plan superior.';
                    $data['estado'] = 'error';
                }

            }else{
                $data['text']   = 'No existe suscripciones activas!, , solicite una suscripcion a un plan vigente.';
                $data['estado'] = 'error';
            }

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function eliminarEmpresa(Request $request) {
        if($request->ajax()){
            $usuario = Auth::user();
            if($usuario->isAdmin()){
                $empresa_id = $request->input('empresa');
                $empresa    = Empresa::find($empresa_id);
                if($empresa){

                    // ELIMINAMOS LAS EMPRESAS DOCUMENTOS SECTORES
                    $empresas_documentos_sectores = $empresa->empresas_documentos_sectores;
                    if(count($empresas_documentos_sectores) > 0){
                        EmpresaDocumentoSector::where('empresa_id', $empresa_id)
                                                ->update(['usuario_eliminador_id' => $usuario->id]);

                        EmpresaDocumentoSector::where('empresa_id', $empresa_id)->delete();
                    }

                    // ELIMINAMOS LAS SUSCRIPCIONES
                    $suscripciones = $empresa->suscripciones;
                    if(count($suscripciones) > 0){
                        Suscripcion::where('empresa_id', $empresa_id)
                                    ->update(['usuario_eliminador_id' => $usuario->id]);

                        Suscripcion::where('empresa_id', $empresa_id)->delete();
                    }

                    // ELIMINAMOS LAS SUCURSALES
                    $sucursales = $empresa->sucursales;
                    if(count($sucursales) > 0){
                        Sucursal::where('empresa_id', $empresa_id)
                        ->update(['usuario_eliminador_id' => $usuario->id]);

                        Sucursal::where('empresa_id', $empresa_id)->delete();
                    }

                    // ELIMINAMOS LOS CLIENTES
                    $clientes = $empresa->clientes;
                    if(count($clientes) > 0){
                        Cliente::where('empresa_id',$empresa_id)
                                ->update(['usuario_eliminador_id' => $usuario->id]);

                        Cliente::where('empresa_id', $empresa_id)->delete();
                    }

                    // ELIMINAMOS A LOS USUARIOS
                    $usuarios = $empresa->usuarios;
                    if(count($usuarios) > 0){
                        User::where('empresa_id',$empresa_id)
                            ->update(['usuario_eliminador_id' => $usuario->id]);

                        User::where('empresa_id',$empresa_id)->delete();
                    }

                    // ELIMINAMOS A LOS USUARIOS
                    $servicios = $empresa->servicios;
                    if(count($servicios) > 0){
                        Servicio::where('empresa_id',$empresa_id)
                            ->update(['usuario_eliminador_id' => $usuario->id]);

                        Servicio::where('empresa_id',$empresa_id)->delete();
                    }

                    // ELIMINAMOS LAS FACTURAS
                    $facturas = $empresa->facturas;
                    if(count($facturas) > 0){
                        Factura::where('empresa_id',$empresa_id)
                            ->update(['usuario_eliminador_id' => $usuario->id]);

                        Factura::where('empresa_id',$empresa_id)->delete();
                    }

                    $empresa->usuario_eliminador_id = $usuario->id;
                    $empresa->save();

                    Empresa::destroy($empresa_id);

                    $data['text']   = 'Empresa eliminado con exito';
                    $data['estado'] = 'success';

                }else{
                    $data['text']   = 'Empresa no existente';
                    $data['estado'] = 'error';
                }
            }else{
                $data['text']   = 'Sin permisos';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }
        return $data;
    }

    public function ajaxDetalleIngresoProducto(Request $request){
        if($request->ajax()){

            $usuario     = Auth::user();
            $empresa     = $usuario->empresa;
            $sucursal    = $usuario->sucursal;
            $servicio_id = $request->input('servicio');

            $movimiento = new Movimiento();
            $servicio   = Servicio::find($servicio_id);

            $cantidad_disponible = $movimiento->cantidaDisponile($sucursal->id ,$servicio_id);

            $sucursales = Sucursal::where('empresa_id', $empresa->id)->get();

            $data['text']    = 'Se proceso con exito';
            $data['listado'] = view('empresa.ajaxDetalleIngresoProducto')->with(compact('servicio', 'cantidad_disponible', 'sucursales'))->render();
            $data['estado']  = 'success';

        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return $data;
    }

    public function ingresoProductoSucursal(Request $request){

        if($request->ajax()){

            $usuario          = Auth::user();
            $empresa          = $usuario->empresa;
            $servicio_id      = $request->input('servicio_id');
            $cantidad_ingreso = $request->input('cantidad_ingreso');
            $sucursal_id      = $request->input('sucuarsal_id_add');

            $servicio = Servicio::find($servicio_id);
            $sucursal = Sucursal::find($sucursal_id);

            if($servicio->empresa_id == $empresa->id){
                if($sucursal->empresa_id == $empresa->id){

                    $movimiento                     = new Movimiento();
                    $movimiento->usuario_creador_id = $usuario->id;
                    $movimiento->sucursal_id        = $sucursal->id;
                    $movimiento->servicio_id        = $servicio->id;
                    $movimiento->ingreso            = $cantidad_ingreso;
                    $movimiento->salida             = 0;
                    $movimiento->fecha              = date('Y-m-d H:i:s');
                    $movimiento->descripcion        = "INGRESO";
                    $movimiento->save();

                    $data['text']   = 'Se proceso con exito';
                    $data['estado'] = 'success';

                }else{
                    $data['text']   = 'Sucursal no existe';
                    $data['estado'] = 'error';
                }
            }else{
                $data['text']   = 'Servicio no existe';
                $data['estado'] = 'error';
            }
        }else{
            $data['text']   = 'No existe';
            $data['estado'] = 'error';
        }

        return $data;

    }

}
