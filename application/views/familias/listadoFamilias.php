<script>
var base_url = "<?php echo base_url(); ?>";
//tablaFamilias = $('#tablaFamilias').DataTable();

$(document).ready(function() {
    ListadoDeFamilias();
});

function showTableLoader(message) {
    if ($('#dynamic-table-loader').length) {
        $('#dynamic-table-loader').show();
        return;
    }
    
    var loaderHtml = 
        '<div id="dynamic-table-loader" style="' +
        'position: absolute; top: 0; left: 0; right: 0; bottom: 0; ' +
        'background: rgba(246, 241, 232, 0.95); ' +
        'display: flex; flex-direction: column; align-items: center; justify-content: center; ' +
        'z-index: 100; gap: 12px;">' +
        '<div style="width: 40px; height: 40px; border: 4px solid rgba(180, 67, 46,0.18); ' +
        'border-top-color: #B4432E; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>' +
        '<div style="color: #3A2A22; font-size: 14px;">' + (message || 'Cargando...') + '</div>' +
        '</div>';
    
    var $wrapper = $('#tablaFamilias').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaFamilias').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(500, function() {
        $(this).remove();
    });
}

function ListadoDeFamilias() {
    if ($.fn.dataTable.isDataTable('#tablaFamilias')) {
        tablaFamilias = $('#tablaFamilias').DataTable();
        tablaFamilias.clear().destroy();
        $('#tablaFamilias tbody').empty();
    }
    tablaFamilias = $("#tablaFamilias").DataTable({
        "bDestroy": true,
        "ajax": {
            "url": base_url + "index.php/Familias/ListadoDeFamilias",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Familias...");
            },
            complete: function () {
                hideTableLoader();
            },
            error: function () {
                hideTableLoader();
            }
        },
        "bPaginate": true,
        "bProcessing": false,
        "pageLength": 10,
        "select": true,
        "dom": "Bfrtip",
        "ordering": true,
        "filter": true,
        "info": false,
        "footer": false,
        "responsive": {
            details: {
                type: 'column',
                target: -1
            }
        },
        "columns": [{
                mData: "id_familia",
                className: "text-center"
            },
            {
                mData: "nombre_familia",
                className: "text-left"
            },
            {
                mData: "alias",
                className: "text-left"
            },
            {
                mData: "venta_directa",
                className: "text-center"
            },
            {
                mData: "orden_familia",
                className: "text-center"
            },
            {
                mData: "imprimir_en_detalle_pedido",
                className: "text-center",
                visible: false
            }
        ],
        'language': {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
            // traduce la linea de abajo que quedaba en ingles
            select: {
                rows: {
                    _: " (Seleccionadas %d filas)",
                    0: " (Presione una linea a seleccionar)",
                    1: " (1 Fila seleccionada)"
                }
            }
        },
        "buttons": [
        ]
    });
    tablaFamilias
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaFamilias.rows(indexes).data().toArray();

            $("#idFamilia").val(rowData[0].id_familia);
            $("#nombreFamilia").val(rowData[0].nombre_familia);
            $("#AliasFamilia").val(rowData[0].alias);
            $("#ordenFamilia").val(rowData[0].orden_familia);
            if (rowData[0].venta_directa == 'SI') {
                $('#ckVentaDirectaFamilia').prop('checked', true);
            } else {
                $('#ckVentaDirectaFamilia').prop('checked', false);
            }

            if (rowData[0].imprimir_en_detalle_pedido == 't') {
                $('#ckImprimirEnDetallePedido').prop('checked', true);
            } else {
                $('#ckImprimirEnDetallePedido').prop('checked', false);
            }
            $('#ModalNuevaFamilia').modal('show');
        })
}
</script>
