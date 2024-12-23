<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cantidad Total de Alumnos</title>
    <style>
        @page {
            margin: 0cm 0cm;
            font-family: Arial;
        }

        body {
            margin: 3cm 1cm 2cm;
        }

        header {
            position: fixed;
            top: 1cm;
            left: 1cm;
            right: 1cm;
            height: 2cm;
            background-color: #ffffff;
            color: black;
            text-align: center;
            line-height: 30px;
        }

        footer {
            position: fixed;
            bottom: 0cm;
            left: 1cm;
            right: 1cm;
            height: 2cm;
            background-color: #fff;
            color: black;
            text-align: center;
            line-height: 35px;
        }

        .bordes {
            /* border: #24486C 1px solid; */
            border: 1px solid;
            border-collapse: collapse;
        }

        table.celdas {
            width: 100%;
            background-color: #fff;
            /* border: 1px solid; */
            border-collapse: collapse;
        }

        .celdas th {
            height: 10px;
            background-color: #E0E0E0;
            /* color: #fff; */
        }

        .celdas td {
            height: 12px;
        }

        .celdas th, .celdas td {
            border: 1px solid black;
            padding: 2px;
            text-align: center;
        }

        .celdabg {
            /* background-color: #E1ECF4; */
            background-color: #ffffff;
        }

    </style>
    <!-- <link href="{{ asset('dist/css/style.min.css') }}" rel="stylesheet"> -->
</head>
<body>
<header>
    <table style="width:100%">
        <tr>
            <td style="text-align:center; font-family: 'Times New Roman', Times, serif; font-size:20px; line-height:100%">
                <strong>INSTITUTO TECNICO "EF - GIPET" S.R.L.</strong>
            </td>
        </tr>
        <tr>
            <td style="text-align:center; font-family: 'Times New Roman', Times, serif; font-size:12px; line-height:100%">
                <strong>TOTAL DE ALUMNOS POR CURSO Y TURNO</strong>
            </td>
        </tr>
        <tr>
            <td style="text-align:center; font-family: 'Times New Roman', Times, serif; font-size:15px; line-height:100%">
                DESDE: 01/01/{{ date('Y') }} HASTA: {{ date('d')}}/{{ date('m') }}/{{ date('Y') }}
            </td>
        </tr>
    </table>
</header>
<main>
    <table style="width:100% text-align:center; font-family: 'Times New Roman', Times, serif; font-size:12px; white-space: nowrap;">
        <thead style="border-top:1px solid; border-bottom:1px solid;">
        <tr>
            <td>DETALLE</td>
            <td>VIGENTES</td>
            <td>TEMPORALES</td>
            <td>NUEVOS</td>
            <td>ABANDONOS</td>
            <td>TOTAL</td>
            <td>T.GRAL</td>
        </tr>
        </thead>
        <tbody>
        @php
//            $totalGeneralVigentes = 0;
//            $totalGeneralTemporales = 0;
//            $totalGeneralAbandonos = 0;
        @endphp
{{--        @foreach($carreras as $carrera)--}}
{{--            @php--}}
{{--                $totalCarreraVigentes = 0;--}}
{{--                $totalCarreraTemporales = 0;--}}
{{--                $totalCarreraAbandonos = 0;--}}
{{--            @endphp--}}
{{--            <tr>--}}
{{--                <th colspan="7" style="text-align:left; border-top:1px solid; border-bottom:1px solid; border-left:1px solid; border-right:1px solid;">CARRERA: {{ strtoupper($carrera->nombre) }}</th>--}}
{{--            </tr>--}}
{{--            @for($i = 1; $i <= $carrera->duracion_anios; $i++)--}}
{{--                @php--}}
{{--                    switch ($i) {--}}
{{--                        case 1:--}}
{{--                            $gestion = 'PRIMER AÑO';--}}
{{--                            break;--}}
{{--                        case 2:--}}
{{--                            $gestion = 'SEGUNDO AÑO';--}}
{{--                            break;--}}
{{--                        case 3:--}}
{{--                            $gestion = 'TERCER AÑO';--}}
{{--                            break;--}}
{{--                        case 4:--}}
{{--                            $gestion = 'CUARTO AÑO';--}}
{{--                            break;--}}
{{--                        case 5:--}}
{{--                            $gestion = 'QUINTO AÑO';--}}
{{--                            break;--}}
{{--                        default:--}}
{{--                            $gestion = 'AÑO INDEFINIDO';--}}
{{--                    }--}}
{{--                    $totalGestionVigentes = 0;--}}
{{--                    $totalGestionTemporales = 0;--}}
{{--                    $totalGestionAbandonos = 0;--}}
{{--                @endphp--}}
{{--                <tr>--}}
{{--                    <th colspan="7" style="text-align:left;">{{ $gestion }}</th>--}}
{{--                </tr>--}}
{{--                @foreach($turnos as $turno)--}}
{{--                    @php--}}
{{--                        $vigentes   = App\CarrerasPersona::where('carrera_id', $carrera->id)--}}
{{--                                                    ->where('turno_id', $turno->id)--}}
{{--                                                    ->where('gestion', $i)--}}
{{--                                                    ->where('anio_vigente', date('Y'))--}}
{{--                                                    ->where('vigencia', 'Vigente')--}}
{{--                                                    ->count();--}}
{{--                        $temporales = App\CarrerasPersona::where('carrera_id', $carrera->id)--}}
{{--                                                    ->where('turno_id', $turno->id)--}}
{{--                                                    ->where('gestion', $i)--}}
{{--                                                    ->where('anio_vigente', date('Y'))--}}
{{--                                                    ->where('vigencia', 'Temporal')--}}
{{--                                                    ->count();--}}
{{--                        $abandonos = App\CarrerasPersona::where('carrera_id', $carrera->id)--}}
{{--                                                    ->where('turno_id', $turno->id)--}}
{{--                                                    ->where('gestion', $i)--}}
{{--                                                    ->where('anio_vigente', date('Y'))--}}
{{--                                                    ->where('vigencia', 'Abandono')--}}
{{--                                                    ->count();--}}
{{--                        $totalGestionVigentes   = $totalGestionVigentes + $vigentes;--}}
{{--                        $totalGestionTemporales = $totalGestionTemporales + $temporales;--}}
{{--                        $totalGestionAbandonos  = $totalGestionAbandonos + $abandonos;--}}
{{--                    @endphp--}}
{{--                    <tr>--}}
{{--                        <td>{{ strtoupper($turno->descripcion) }}</td>--}}
{{--                        <td style="text-align:center;">{{ $vigentes }}</td>--}}
{{--                        <td style="text-align:center;">{{ $temporales }}</td>--}}
{{--                        <td style="text-align:center;">0</td>--}}
{{--                        <td style="text-align:center;">{{ $abandonos }}</td>--}}
{{--                        <td style="text-align:center;">{{ ($vigentes + $temporales + $abandonos) }}</td>--}}
{{--                        <td style="text-align:center;">{{ ($vigentes + $temporales + $abandonos) }}</td>--}}
{{--                    </tr>--}}
{{--                @endforeach--}}
{{--                @php--}}
{{--                    $totalCarreraVigentes   = $totalCarreraVigentes + $totalGestionVigentes;--}}
{{--                    $totalCarreraTemporales = $totalCarreraTemporales + $totalGestionTemporales;--}}
{{--                    $totalCarreraAbandonos  = $totalCarreraAbandonos + $totalGestionAbandonos;--}}
{{--                @endphp--}}
{{--                <tr>--}}
{{--                    <th style="text-align:left;">TOTAL {{ $gestion }}</th>--}}
{{--                    <th style="text-align:center; border-top:1px solid;">{{ $totalGestionVigentes }}</th>--}}
{{--                    <th style="text-align:center; border-top:1px solid;">{{ $totalGestionTemporales }}</th>--}}
{{--                    <th style="text-align:center; border-top:1px solid;">0</th>--}}
{{--                    <th style="text-align:center; border-top:1px solid;">{{ $totalGestionAbandonos }}</th>--}}
{{--                    <th style="text-align:center; border-top:1px solid;">{{ ($totalGestionVigentes + $totalGestionTemporales + $totalGestionAbandonos) }}</th>--}}
{{--                    <th style="text-align:center; border-top:1px solid;">{{ ($totalGestionVigentes + $totalGestionTemporales + $totalGestionAbandonos) }}</th>--}}
{{--                </tr>--}}
{{--            @endfor--}}
{{--            @php--}}
{{--                $totalGeneralVigentes = $totalGeneralVigentes + $totalCarreraVigentes;--}}
{{--                $totalGeneralTemporales = $totalGeneralTemporales + $totalCarreraTemporales;--}}
{{--                $totalGeneralAbandonos = $totalGeneralAbandonos + $totalCarreraAbandonos;--}}
{{--            @endphp--}}
{{--            <tr>--}}
{{--                <th style="text-align:left;">TOTAL {{ strtoupper($carrera->nombre) }}</th>--}}
{{--                <th>{{ $totalCarreraVigentes }}</th>--}}
{{--                <th>{{ $totalCarreraTemporales }}</th>--}}
{{--                <th>0</th>--}}
{{--                <th>{{ $totalCarreraAbandonos }}</th>--}}
{{--                <th>{{ ($totalCarreraVigentes + $totalCarreraTemporales + $totalCarreraAbandonos) }}</th>--}}
{{--                <th>{{ ($totalCarreraVigentes + $totalCarreraTemporales + $totalCarreraAbandonos) }}</th>--}}
{{--            </tr>--}}
{{--        @endforeach--}}
        </tbody>
        <tfoot>
        <tr>
{{--            <th style="text-align:left;">TOTAL GENERAL</th>--}}
{{--            <th>{{ $totalGeneralVigentes }}</th>--}}
{{--            <th>{{ $totalGeneralTemporales }}</th>--}}
{{--            <th>0</th>--}}
{{--            <th>{{ $totalGeneralAbandonos }}</th>--}}
{{--            <th>{{ ($totalGeneralVigentes + $totalGeneralTemporales + $totalGeneralAbandonos) }}</th>--}}
{{--            <th>{{ ($totalGeneralVigentes + $totalGeneralTemporales + $totalGeneralAbandonos) }}</th>--}}
        </tr>
        </tfoot>
    </table>

</main>
</body>
</html>
