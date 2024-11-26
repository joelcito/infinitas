<table class="table align-middle table-row-dashed fs-6 gy-5">
    <thead>
        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
            <th>Nombre</th>
            <th>Precio</th>
            <th>Stock</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $servicio->descripcion }}</td>
            <td>{{ $servicio->precio }}</td>
            <td>{{ $cantidad_disponible }}</td>
        </tr>
    </tbody>
</table>
<hr>
<form id="formlario_ingreso_prodcuto">
    <div class="row">
        <div class="col-md-6">
            <label class="fs-6 fw-semibold form-label mb-2">Sucursal</label>
            <select name="sucuarsal_id_add" id="sucuarsal_id_add" class="form-control" required>
                @foreach ( $sucursales as $s)
                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                @endforeach
            </select>
            <input type="hidden" value="{{ $servicio->id }}" id="servicio_id" name="servicio_id">
        </div>
        <div class="col-md-6">
            <label class="fs-6 fw-semibold form-label mb-2">Cantidad Ingreso</label>
            <input type="number" class="form-control fw-bold" name="cantidad_ingreso" id="cantidad_ingreso" required>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col-md-12">
            <button type="button" class="btn btn-success w-100 btn-sm" onclick="ingresoProductoSucursal()">Guardar</button>
        </div>
    </div>
</form>
