<script>
var base_url = "<?php echo base_url(); ?>";
tablaMesas = $('#tablaMesas').DataTable();

$(document).ready(function() {
    ListadoDeMesas();
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
    
    var $wrapper = $('#tablaMesas').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaMesas').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(500, function() {
        $(this).remove();
    });
}

function ListadoDeMesas() {
    if ($.fn.dataTable.isDataTable('#tablaMesas')) {
        tablaMesas = $('#tablaMesas').DataTable();
        tablaMesas.destroy();
    }
    tablaMesas = $("#tablaMesas").DataTable({
        "ajax": {
            "url": base_url + "index.php/Mesas/ListadoDeMesas",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Mesas...");
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
                mData: "id_mesa",
                className: "text-center"
            },
            {
                mData: "disponible",
                className: "text-center",
                render: function(data) {
                    return data ? '<span class="label label-success">DISPONIBLE</span>' : '<span class="label label-danger">OCUPADA</span>';
                }
            },
            {
                mData: "vigente",
                className: "text-center",
                render: function(data) {
                    var vigente = (data === true || data === "true" || data === "t" || data === 1);
                    if (vigente) {
                        return '<span class="label label-success">ACTIVO</span>';
                    } else {
                        return '<span class="label label-danger">INACTIVO</span>';
                    }
                }
            }
        ],
        'language': {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
            select: {
                rows: {
                    _: " (Seleccionadas %d filas)",
                    0: " (Presione una línea a seleccionar)",
                    1: " (1 Fila seleccionada)"
                }
            }
        },
        "buttons": [
        ]
    });
    tablaMesas
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaMesas.rows(indexes).data().toArray();

            // Modo edición
            modoEdicion = true;
            
            $("#idMesa").val(rowData[0].id_mesa);
            
            // Convertir el valor booleano a string para el select
            var vigenteValue = rowData[0].vigente === true || rowData[0].vigente === 't' || rowData[0].vigente === 'true' ? 'true' : 'false';
            $("#vigente").val(vigenteValue);
            
            console.log('Vigente original:', rowData[0].vigente);
            console.log('Vigente convertido:', vigenteValue);
            
            // Mostrar campos de edición y ocultar cantidad
            $('#divCantidadMesas').hide();
            $('#divIdMesa').show();
            $('#divVigente').show();
            
            $('#tituloModalMesa h4').text('EDITAR MESA');
            
            $('#ModalNuevaMesa').modal('show');
        })
}
</script>