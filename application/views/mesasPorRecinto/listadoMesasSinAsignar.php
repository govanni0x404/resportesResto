<script>
var base_url = "<?php echo base_url(); ?>";
var tablaMesasSinAsignar = $('#tablaMesasSinAsignar').DataTable();

$(document).ready(function() {
    ListadoDeMesasSinAsignar();
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
    
    var $wrapper = $('#tablaMesasSinAsignar').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaMesasSinAsignar').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(500, function() {
        $(this).remove();
    });
}

function ListadoDeMesasSinAsignar() {
    if ($.fn.dataTable.isDataTable('#tablaMesasSinAsignar')) {
        tablaMesasSinAsignar = $('#tablaMesasSinAsignar').DataTable();
        tablaMesasSinAsignar.destroy();
    }
    tablaMesasSinAsignar = $("#tablaMesasSinAsignar").DataTable({
        "ajax": {
            "url": base_url + "index.php/MesasPorRecinto/ListadoDeMesasSinAsignar",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Mesas Sin Asignar...");
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
                mData: "id_recinto",
                className: "text-center",
                render: function(data) {
                    return data == 0 ? '<span class="label label-warning">SIN ASIGNAR</span>' : '<span class="label label-info">RECINTO ' + data + '</span>';
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
        "buttons": []
    });
    
    tablaMesasSinAsignar.on('select', function(e, dt, type, indexes) {
        var rowData = tablaMesasSinAsignar.rows(indexes).data().toArray();
        
        var idMesa = rowData[0].id_mesa;
        var posCalculado = idMesa - 1;
        
        $("#idMesaAsignar").val(idMesa);
        $("#posCalculado").val(posCalculado);
        $("#recintoSelect").val("0");
        
        console.log('Mesa seleccionada:', idMesa, 'Pos calculado:', posCalculado);
        
        $('#ModalAsignarMesa').modal('show');
    });
}
</script>