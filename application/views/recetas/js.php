<script>
var base_url = "<?php echo base_url(); ?>";
tableRecetas = $('#tableRecetas').DataTable();

$(document).ready(function() {
    listarRecetas();
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
    
    var $wrapper = $('#tableRecetas').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableRecetas').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(200, function() {
        $(this).remove();
    });
}

function listarRecetas() {
    if ($.fn.dataTable.isDataTable('#tableRecetas')) {
        tableRecetas = $('#tableRecetas').DataTable();
        tableRecetas.destroy();
    }
    tableRecetas = $("#tableRecetas").DataTable({
        "ajax": {
            "url": base_url + "index.php/Recetas/listadoRecetas",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Recetas...");
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
        "select": {
            style: 'single', //permite elegir solo 1 fila
            style: 'os',
            selector: 'td:not(:last-child)' //esto permite que al presionar botones no se active el select de la tabla (detalle)
        },
        "responsive": {
            details: {
                type: 'column',
                target: -1
            }
        },
        "columns": [{
                mData: "id_receta",
                className: "text-center",
                "visible": false,
            },
            {
                mData: "nombre_receta",
                className: "text-center",
                width: "50%",
            },
            {
                mData: "nombre_familia_receta",
                className: "text-left",
                width: "5%",
            },
            {
                mData: "costo_insumos",
                className: "text-center",
                width: "10%",
            },
            {
                mData: "margen_ganancia",
                className: "text-center",
                width: "40%",
            },
            {
                mData: "por_costo",
                className: "text-center",
                width: "50%",
            },
            {
                mData: "por_ganancia",
                className: "text-center",
                width: "10%",
            },
            {
                mData: "precio_venta",
                className: "text-center",
                width: "40%",
            },
            {
                mData: "precio_venta",
                className: "text-center",
                width: "10%",
            },
            {
                mData: "rendimiento",
                className: "text-center",
                width: "40%",
            },
            {
                mData: "id_umedida",
                className: "text-center",
                visible: false

            },
            {
                mData: "id_familia_receta",
                className: "text-center",
                visible: false
            },
            {
                mData: "id_seccion",
                className: "text-center",
                visible: false
            },
            {
                mData: "responsive",
                "width": "1%",
                className: 'dtr-control',
                orderable: false,
                targets: -1
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
        "buttons": []
    });
    tableRecetas
        .on('select', function(e, dt, type, indexes) {
            var rowData = tableRecetas.rows(indexes).data().toArray();
            $('#idReceta').val(rowData[0].id_receta);
            $("#nombreReceta").val(rowData[0].nombre_receta)
            $('#rendimientoReceta').val(rowData[0].rendimiento);
            $("#brutoSugerido").val(rowData[0].precio_venta)

            $("#tabDetalleInsumosReceta").addClass('active');
            $('#tabDetalleInsumosReceta').addClass('in');
            $("#tabPreparacionReceta").removeClass('active');
            $('#tabPreparacionReceta').removeClass('in');

            $("#tabDetallesReceta").addClass('active');
            $('#tabDetallesReceta').addClass('in');
            $("#tabPreparacionRecetas").removeClass('active');
            $('#tabPreparacionRecetas').removeClass('in');

            $("#tabDetalleInsumosReceta").show();
            $("#tabPreparacionReceta").hide();

            llenarCboUnMedida(rowData[0].id_umedida);
            llenarFamiliaReceta(rowData[0].id_familia_receta);
            llenarSeccionReceta(rowData[0].id_seccion);
            llenarCboInsumos(0);

            tableInsumoReceta.clear().draw();

            $.post(base_url + "index.php/Recetas/getInsumosReceta", {
                id_receta: rowData[0].id_receta
            }, function(datos) {
                var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
                $.each(obj, function(index, v) { // recorro el array
                    if (v.es_receta == 't') {
                        es_receta = 'SI'
                    } else {
                        es_receta = 'NO'
                    }
                    $("#preparacionReceta").val(v.preparacion)
                    $('#cant_lineas').val(parseInt($('#cant_lineas').val()) + 1);
                    var rnd = parseInt($("#contador")
                .val()); //variable que define la row especifica a borrar
                    addToTable(rnd, v.id_detalle_receta, v.id_insumo, v.cbarra, v.nombre_insumo,
                        es_receta, v.cantidad, v.nombre_umedida, v.costo_neto_ppp, v
                        .costo_bruto_ppp);
                }); //fin each
            }).done(function() {})

            $('#modalReceta').modal({
                backdrop: 'static',
                keyboard: false
            });
        })
}
</script>