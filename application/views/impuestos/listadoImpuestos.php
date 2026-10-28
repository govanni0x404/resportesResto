<script>
var base_url = "<?php echo base_url(); ?>";
tablaImpuestos = $('#tablaImpuestos').DataTable();

$(document).ready(function() {
    ListadoDeImpuestos();
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
    
    var $wrapper = $('#tablaImpuestos').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaImpuestos').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(500, function() {
        $(this).remove();
    });
}

function ListadoDeImpuestos() {
    if ($.fn.dataTable.isDataTable('#tablaImpuestos')) {
        tablaImpuestos = $('#tablaImpuestos').DataTable();
        tablaImpuestos.destroy();
    }
    tablaImpuestos = $("#tablaImpuestos").DataTable({
        "ajax": {
            "url": base_url + "index.php/Impuestos/ListadoDeImpuestos",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Impuestos...");
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
                mData: "id_impuesto",
                className: "text-center"
            },
            {
                mData: "nombre_impuesto",
                className: "text-left"
            },
            {
                mData: "observacion",
                className: "text-left"
            },
            {
                mData: "valor",
                className: "text-center"
            },
            {
                mData: "codigo_sii",
                className: "text-center",
                visible: false
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
    tablaImpuestos
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaImpuestos.rows(indexes).data().toArray();

            $("#idImpuesto").val(rowData[0].id_impuesto);
            $("#nombreImpuesto").val(rowData[0].nombre_impuesto);
            $("#txtObservacionImpuesto").val(rowData[0].observacion);
            $("#TasaImpuesto").val(rowData[0].valor);
            $("#CodigoSiiImpuesto").val(rowData[0].codigo_sii);
            $('#ModalNuevoImpuesto').modal('show');
        })
}
</script>