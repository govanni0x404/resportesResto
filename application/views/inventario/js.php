<script>
    const BASE_URL = "<?= base_url() ?>";
</script>

<script>
$(document).ready(function(){
    let inventarioActual = null;
    let detalleCompleto = [];

    if ($('#selectBodega option').length > 0) {
        $('#selectBodega').val($('#selectBodega option:first').val());
    }

    $('#selectBodega').change(function(){
        let idBodega = $(this).val();
        cargarInventariosPorBodega(idBodega);
    });

    function cargarInventariosPorBodega(idBodega) {
        $.ajax({
            url: BASE_URL + "index.php/inventario/listar_por_bodega",
            type: 'POST',
            data: { id_bodega: idBodega },
            dataType: 'json',
            success: function(data){
                let html = '';
                
                if (data.length === 0) {
                    html = `<tr><td colspan="3" class="text-center"><em>No hay inventarios</em></td></tr>`;
                } else {
                    data.forEach(inv => {
                        html += `
                            <tr class="inventario-row" data-id="${inv.id_inventario}" style="cursor: pointer;">
                                <td class="text-center">${inv.id_inventario}</td>
                                <td style="font-size: 11px;">${formatFecha(inv.fecha_movimiento)}</td>
                                <td style="font-size: 11px;">${(inv.usuario || 'SIN USUARIO').toUpperCase()}</td>
                            </tr>
                        `;
                    });
                }
                
                $('#tbodyInventarios').html(html);
                $('#tablaDetalle tbody').html(`
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 40px;">
                            <em class="text-muted">Seleccione un inventario</em>
                        </td>
                    </tr>
                `);
            }
        });
    }

    $(document).on('click', '.inventario-row', function () {

        $('.inventario-row').removeClass('selected');
        $(this).addClass('selected');

        inventarioActual = $(this).data('id');

        detalleCompleto = [];
        $('#chkSoloDiferencias').prop('checked', false);
        $('#txtBuscarInsumo').val('');
        $('#totalDifCosto').text('0.00');

        $('#tablaDetalle tbody').html(`
            <tr>
                <td colspan="6" class="text-center">
                    <em class="text-muted">Cargando detalle...</em>
                </td>
            </tr>
        `);

        cargarDetalle(inventarioActual);
    });

    function cargarDetalle(idInventario) {
        $.ajax({
            url: BASE_URL + "index.php/inventario/detalle",
            type: 'POST',
            data: { id_inventario: idInventario },
            dataType: 'json',
            success: function(data){
                detalleCompleto = data;
                renderizarDetalle(data);
            }
        });
    }

    function renderizarDetalle(data) {
        let html = '';
        let totalDifCosto = 0;

        if (!data || data.length === 0) {
            $('#tablaDetalle tbody').html(`
                <tr>
                    <td colspan="6" class="text-center">
                        <em class="text-muted">Sin detalle para este inventario</em>
                    </td>
                </tr>
            `);

            $('#totalDifCosto').text('0.00');
            return;
        }

        data.forEach(d => {
            let diferencia = parseFloat(d.diferencia || 0);
            let difCosto = parseFloat(d.valor_diferencia_costo || 0);

            totalDifCosto += difCosto;

            let rowClass =
                diferencia > 0 ? 'diferencia-positiva' :
                diferencia < 0 ? 'diferencia-negativa' :
                'sin-diferencia';

            html += `
                <tr class="${rowClass}">
                    <td>${d.nombre_insumo}</td>
                    <td class="text-right">${formatNumber(d.actual)}</td>
                    <td class="text-right">${formatNumber(d.nuevo)}</td>
                    <td class="text-right">${formatNumber(d.diferencia)}</td>
                    <td class="text-right">${formatNumber(d.costo_un)}</td>
                    <td class="text-right">${formatNumber(difCosto)}</td>
                </tr>
            `;
        });

        $('#tablaDetalle tbody').html(html);
        $('#totalDifCosto').text(formatNumber(totalDifCosto));
    }

    $('#chkSoloDiferencias').change(function(){
        if ($(this).is(':checked')) {
            let filtrado = detalleCompleto.filter(d => parseFloat(d.diferencia || 0) !== 0);
            renderizarDetalle(filtrado);
        } else {
            renderizarDetalle(detalleCompleto);
        }
    });

    $('#txtBuscarInsumo').on('keyup', function(){
        let valor = $(this).val().toLowerCase();
        
        if (valor === '') {
            renderizarDetalle(detalleCompleto);
        } else {
            let filtrado = detalleCompleto.filter(d => 
                (d.nombre_insumo || '').toLowerCase().includes(valor)
            );
            renderizarDetalle(filtrado);
        }
    });

    $('#chkSelectAll').change(function(){
        $('.chk-detalle').prop('checked', $(this).is(':checked'));
    });

    function formatNumber(num) {
        if (!num) return '0.00';
        return parseFloat(num).toLocaleString('es-CL', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatFecha(fecha) {
        let d = new Date(fecha);
        let dia = String(d.getDate()).padStart(2, '0');
        let mes = String(d.getMonth() + 1).padStart(2, '0');
        let año = d.getFullYear();
        let hora = String(d.getHours()).padStart(2, '0');
        let min = String(d.getMinutes()).padStart(2, '0');
        let seg = String(d.getSeconds()).padStart(2, '0');
        
        return `${dia}/${mes}/${año} ${hora}:${min}:${seg}`;
    }

    $('#btnSalir').click(function(){
        window.location.href = '<?= base_url("dashboard") ?>';
    });

    $(document).keyup(function(e) {
        if (e.key === "Escape") {
            $('#btnSalir').click();
        }
    });

    $('#btnNuevoInventario').click(function(){
        window.location.href = '<?= base_url("inventario/nuevo") ?>';
    });

    $('#btnEliminar').click(function(){
        let seleccionados = $('.chk-detalle:checked');
        
        if (seleccionados.length === 0) {
            alert('Debe seleccionar al menos un item');
            return;
        }
        
        if (!confirm('¿Está seguro de eliminar los items seleccionados?')) {
            return;
        }

        let ids = [];
        seleccionados.each(function(){
            ids.push($(this).val());
        });

        $.ajax({
            url: BASE_URL + "index.php/inventario/eliminar_items",
            type: 'POST',
            data: { ids: ids },
            dataType: 'json',
            success: function(response){
                if (response.success) {
                    alert('Items eliminados correctamente');
                    cargarDetalle(inventarioActual);
                } else {
                    alert('Error al eliminar: ' + response.message);
                }
            }
        });
    });

    $('#selectBodega').trigger('change');
});
</script>