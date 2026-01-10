<div class="row">

    <!-- TABLA IZQUIERDA -->
    <div class="col-md-3">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inventarios as $inv): ?>
                <tr class="inventario"
                    data-id="<?= $inv->id_inventario ?>">
                    <td><?= $inv->id_inventario ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($inv->fecha_movimiento)) ?></td>
                    <td><?= $inv->usuario ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- TABLA DERECHA -->
    <div class="col-md-9">
        <table class="table table-sm table-bordered" id="tablaDetalle">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Un. Med</th>
                    <th>Actual</th>
                    <th>Nuevo</th>
                    <th>Diferencia</th>
                    <th>Costo</th>
                    <th>Dif. Costo</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>
<script>
$(document).ready(function(){

    $(document).on('click', '.inventario', function(){

        $('.inventario').removeClass('table-active');
        $(this).addClass('table-active');

        let idInventario = $(this).data('id');
        console.log('Inventario:', idInventario);

        $.ajax({
            url: '<?= base_url("inventario/detalle/") ?>' + idInventario,
            type: 'GET',
            dataType: 'json',
            success: function(data){
                console.log(data);

                let html = '';

                if (data.length === 0) {
                    html = `
                        <tr>
                            <td colspan="7" class="text-center">
                                Sin detalle para este inventario
                            </td>
                        </tr>
                    `;
                } else {
                    data.forEach(d => {
                        html += `
                            <tr>
                                <td>${d.producto}</td>
                                <td>${d.unidad_medida}</td>
                                <td>${d.actual}</td>
                                <td>${d.nuevo}</td>
                                <td>${d.diferencia}</td>
                                <td>${d.costo_un}</td>
                                <td>${d.valor_diferencia_costo}</td>
                            </tr>
                        `;
                    });
                }

                $('#tablaDetalle tbody').html(html);
            },
            error: function(xhr){
                console.error(xhr.responseText);
            }
        });
    });

});
</script>
