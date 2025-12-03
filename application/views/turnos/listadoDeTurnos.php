<script>
var base_url = "<?php echo base_url(); ?>";
tablaTurnos = $('#tablaTurnos').DataTable();

$(document).ready(function() {
    ListadoDeTurnos();
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
    
    var $wrapper = $('#tablaTurnos').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaTurnos').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(500, function() {
        $(this).remove();
    });
}

function ListadoDeTurnos() {
    if ($.fn.dataTable.isDataTable('#tablaTurnos')) {
        tablaTurnos = $('#tablaTurnos').DataTable();
        tablaTurnos.destroy();
    }
    tablaTurnos = $("#tablaTurnos").DataTable({
        "ajax": {
            "url": base_url + "index.php/Turnos/ListadoDeTurnos",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Turnos...");
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
                mData: "id_turno",
                className: "text-center"
            },
            {
                mData: "nombre_turno",
                className: "text-left"
            },
            {
                mData: "cierre_turno",
                className: "text-center"
            },
            {
                mData: "informe_z",
                className: "text-center"
            },
            {
                mData: "impedir_cierre_con_mesas_abiertas",
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
    tablaTurnos
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaTurnos.rows(indexes).data().toArray();

            $("#idTurnos").val(rowData[0].id_turno);
            $("#nombreTurno").val(rowData[0].nombre_turno);
            if (rowData[0].cierre_turno == 'SI') {
                $('#ckCierreTurno').prop('checked', true);
            } else {
                $('#ckCierreTurno').prop('checked', false);
            }
            if (rowData[0].informe_z == 'SI') {
                $('#ckInformeZ').prop('checked', true);
            } else {
                $('#ckInformeZ').prop('checked', false);
            }
            if (rowData[0].impedir_cierre_con_mesas_abiertas == 'SI') {
                $('#ckImprimirCierreConMesasAbiertas').prop('checked', true);
            } else {
                $('#ckImprimirCierreConMesasAbiertas').prop('checked', false);
            }
            $('#ModalNuevoTurno').modal('show');
        })
}
</script>