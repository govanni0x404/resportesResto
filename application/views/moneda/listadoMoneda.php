<script>
var base_url = "<?php echo base_url(); ?>";
tablaImpuestos = $('#tablaMoneda').DataTable();

$(document).ready(function() {
    ListadoDeMoneda();
});

function showTableLoader(message) {
    if ($('#dynamic-table-loader').length) {
        $('#dynamic-table-loader').show();
        return;
    }
    
    var loaderHtml = 
        '<div id="dynamic-table-loader" style="' +
        'position: absolute; top: 0; left: 0; right: 0; bottom: 0; ' +
        'background: rgba(255, 255, 255, 0.95); ' +
        'display: flex; flex-direction: column; align-items: center; justify-content: center; ' +
        'z-index: 100; gap: 12px;">' +
        '<div style="width: 40px; height: 40px; border: 4px solid rgba(0,123,255,0.2); ' +
        'border-top-color: #007bff; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>' +
        '<div style="color: #333; font-size: 14px;">' + (message || 'Cargando...') + '</div>' +
        '</div>';
    
    var $wrapper = $('#tablaMoneda').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaMoneda').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(500, function() {
        $(this).remove();
    });
}

function ListadoDeMoneda() {
    if ($.fn.dataTable.isDataTable('#tablaMoneda')) {
        tablaMoneda = $('#tablaMoneda').DataTable();
        tablaMoneda.destroy();
    }
    tablaMoneda = $("#tablaMoneda").DataTable({
        "ajax": {
            "url": base_url + "index.php/Moneda/ListadoDeMonedas",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Monedas...");
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
                mData: "id_moneda",
                className: "text-center"
            },
            {
                mData: "nombre_moneda",
                className: "text-center"
            },
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
    tablaMoneda
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaMoneda.rows(indexes).data().toArray();

            $("#idMoneda").val(rowData[0].id_moneda);
            $("#nombreMoneda").val(rowData[0].nombre_moneda);
            $('#ModalNuevaMoneda').modal('show');
        })
}
</script>