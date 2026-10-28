<script>
var base_url = "<?php echo base_url(); ?>";
tablaFormasDePago = $('#tablaFormasDePago').DataTable();

$(document).ready(function() {
    ListadoDeFormasDePago();
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
    
    var $wrapper = $('#tablaFormasDePago').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaFormasDePago').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(500, function() {
        $(this).remove();
    });
}

function ListadoDeFormasDePago() {
    if ($.fn.dataTable.isDataTable('#tablaFormasDePago')) {
        tablaFormasDePago = $('#tablaFormasDePago').DataTable();
        tablaFormasDePago.destroy();
    }
    tablaFormasDePago = $("#tablaFormasDePago").DataTable({
        "ajax": {
            "url": base_url + "index.php/FormasDePago/ListadoDeFormasDePago",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Formas de Pago...");
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
                mData: "id_fpago",
                className: "text-center"
            },
            {
                mData: "nombre_fpago",
                className: "text-left"
            },
            {
                mData: "escortesia",
                className: "text-center"
            },
            {
                mData: "boleta_electronica",
                className: "text-center"
            },
            {
                mData: "vigente",
                className: "text-center"
            },
            {
                mData: "permite_propina",
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
    tablaFormasDePago
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaFormasDePago.rows(indexes).data().toArray();

            $("#idFormaDePago").val(rowData[0].id_fpago);
            $("#nombreFormaDePago").val(rowData[0].nombre_fpago);
            if (rowData[0].escortesia == 'SI') {
                $('#ckEsCortesia').prop('checked', true);
            } else {
                $('#ckEsCortesia').prop('checked', false);
            }

            if (rowData[0].boleta_electronica == 'SI') {
                $('#ckBoletaElectronica').prop('checked', true);
            } else {
                $('#ckBoletaElectronica').prop('checked', false);
            }

            if (rowData[0].vigente == 'SI') {
                $('#ckVigente').prop('checked', true);
            } else {
                $('#ckVigente').prop('checked', false);
            }

            if (rowData[0].permite_propina == 'SI') {
                $('#ckPermitePropina').prop('checked', true);
            } else {
                $('#ckPermitePropina').prop('checked', false);
            }
            $('#ModalNuevaFormaDePago').modal('show');
        })
}
</script>