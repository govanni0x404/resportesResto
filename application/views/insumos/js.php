<script>
var base_url = "<?php echo base_url(); ?>";
tableInsumos = $('#tableInsumos').DataTable();


$(document).ready(function() {
    listarInsumos();
});

function listarInsumos() {
    if ($.fn.dataTable.isDataTable('#tableInsumos')) {
        tableInsumos = $('#tableInsumos').DataTable();
        tableInsumos.destroy();
    }
    // Customize this variable to hide the columns you want
    var columnas = [6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
    tableInsumos = $("#tableInsumos").DataTable({
        "ajax": {
            "url": base_url + "index.php/Insumos/listadoInsumos"
        },
        "bPaginate": true,
        "bProcessing": true,
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
                mData: "id_insumo",
                className: "text-center",
                "visible": false,
                // width: "1%;",
            },
            {
                mData: "cbarra",
                className: "text-center",
                width: "10%",
            },
            {
                mData: "nombre_insumo",
                className: "text-center",
                width: "40%",
            },
            {
                mData: "nombre_grupo_insumo",
                className: "text-center",
                width: "40%",
            },
            {
                mData: "costo_neto_ppp",
                className: "text-center",
                "width": "1%",
            },
            {
                mData: "costo_bruto_ppp",
                className: "text-center",
                "width": "1%",
            },
            {
                mData: "umedida_inventario",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "u_medida_inv",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "umedida_receta",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "u_medida_receta",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "iva",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "iva_adicional",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "merma",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "esreceta",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "inventario_cierre_turno",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "precio_ucompra",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "inv_c_caja",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
                visible: false
            },
            {
                mData: "observaciones",
                className: "text-center",
                width: "1%",
                // "width": "10px;",
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
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
            // traduce la linea de abajo que quedaba en ingles
            select: {
                rows: {
                    _: " (Seleccionadas %d filas)",
                    0: " (Presione una linea a seleccionar)",
                    1: " (1 Fila seleccionada)"
                }
            }
        },
        "buttons": [{
                text: 'Más Información',
                action: function(e, dt, node, config) {
                    dt.columns(columnas).visible(true);
                }
            },
            {
                text: 'Menos Información',
                action: function(e, dt, node, config) {
                    dt.columns(columnas).visible(false);
                }
            }
        ]
    });
    tableInsumos
        .on('select', function(e, dt, type, indexes) {

            tableStockInsumo = $('#tableStockInsumo').DataTable();
            tableRecetasInsumo = $('#tableRecetasInsumo').DataTable();
            tableUmMedidaReceta = $('#tableUmMedidaReceta').DataTable();
            tableStockInsumoReceta = $('#tableStockInsumoReceta').DataTable();
            tableHistoricoCompras = $('#tableHistoricoCompras').DataTable();



            var rowData = tableInsumos.rows(indexes).data().toArray();
            console.log(rowData[0].id_insumo);
            $('#idInsumo').val(rowData[0].id_insumo);
            $("#codBarra").val(rowData[0].cbarra)
            $('#nombreInsumo').val(rowData[0].nombre_insumo);
            $("#medInventario").val(rowData[0].umedida_inventario)
            $('#medReceta').val(rowData[0].umedida_receta);
            $("#costoReceta").val(rowData[0].costo_neto_ppp);
            $("#costoBruto").val(rowData[0].costo_bruto_ppp);
            $("#costoUltCompra").val(rowData[0].precio_ucompra);
            $("#obsInsumo").val(rowData[0].observaciones);
            tabsInsumos();
            $("#tabReceta").show();
            $("#tabProd").show();
            $("#tabStocks").show();
            $("#tabUmStockInsumosRecetas").addClass('active');
            $('#tabUmStockInsumosRecetas').addClass('in');
            $("#tabUmMedidaReceta").addClass('active');
            $("#tabUmMedidaReceta").addClass('in');
            $("#tabUmMedidaReceta").show();
            $("#tabStockInsumoRecetas").removeClass('active');
            $('#tabStockInsumoRecetas').removeClass('in');
            $("#tabStockInsumoReceta").removeClass('active');
            $('#tabStockInsumoReceta').removeClass('in');
            $("#tabStockInsumoReceta").hide();
            if (rowData[0].inventario_cierre_turno == 't') {
                $('#ckInvCierre').prop('checked', true);
            } else {
                $('#ckInvCierre').prop('checked', false);
            }
            console.log(rowData[0].u_medida_inv);
            console.log(rowData[0].u_medida_receta);
            llenarRecetaProduccion(rowData[0].id_insumo);
            listarStockInsumo(rowData[0].id_insumo);
            listarInsumosReceta(rowData[0].id_insumo);
            listarInsumosProduccion(rowData[0].id_insumo);
            llenarGrupoInsumos(rowData[0].nombre_grupo_insumo);
            llenarImpto(rowData[0].iva, rowData[0].iva_adicional);
            llenarUnMedida(rowData[0].u_medida_inv, rowData[0].u_medida_receta);
            listarRecetasInsumo(rowData[0].id_insumo);
        })
}

function listarStockInsumo(id_insumo) {
    if ($.fn.dataTable.isDataTable('#tableStockInsumo')) {
        tableStockInsumo = $('#tableStockInsumo').DataTable();
        tableStockInsumo.destroy();
    }
    tableStockInsumo = $("#tableStockInsumo").DataTable({
        "ajax": {
            "url": base_url + "index.php/Insumos/listadoStockInsumo",
            "type": "POST",
            "data": {
                'id_insumo': id_insumo,
            }
        },
        "bPaginate": true,
        "bProcessing": true,
        "pageLength": 10,
        "select": true,
        "dom": "t",
        "ordering": false,
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
                mData: "id_insumo",
                className: "text-right",
                "visible": false,
            },
            {
                mData: "nombre_bodega",
                className: "text-left",
                width: "50%",
            },
            {
                mData: "saldo",
                className: "text-right",
                width: "10%",
            },
            {
                mData: "nombre_umedida",
                className: "text-left",
                width: "40%",
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
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
}

function listarInsumosReceta(id_insumo) {
    if ($.fn.dataTable.isDataTable('#tableUmMedidaReceta')) {
        tableUmMedidaReceta = $('#tableUmMedidaReceta').DataTable();
        tableUmMedidaReceta.destroy();
    }
    tableUmMedidaReceta = $("#tableUmMedidaReceta").DataTable({
        "ajax": {
            "url": base_url + "index.php/Insumos/listadoInsumosReceta",
            "type": "POST",
            "data": {
                'id_insumo': id_insumo,
            }
        },
        "bPaginate": true,
        "bProcessing": true,
        "pageLength": 10,
        "select": true,
        "dom": "t",
        "ordering": false,
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
                mData: "nombre_insumo",
                className: "text-left",
                width: "50%",
            },
            {
                mData: "cantidad",
                className: "text-right",
                width: "50%",
            },
            {
                mData: "nombre_umedida",
                className: "text-left",
                width: "5%",
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
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
}

function listarInsumosProduccion(id_insumo) {
    if ($.fn.dataTable.isDataTable('#tableStockInsumoReceta')) {
        tableStockInsumoReceta = $('#tableStockInsumoReceta').DataTable();
        tableStockInsumoReceta.destroy();
    }
    tableStockInsumoReceta = $("#tableStockInsumoReceta").DataTable({
        "ajax": {
            "url": base_url + "index.php/Insumos/listadoInsumoProduccion",
            "type": "POST",
            "data": {
                'id_insumo': id_insumo,
            }
        },
        "bPaginate": true,
        "bProcessing": true,
        "pageLength": 10,
        "select": true,
        "dom": "Bftrip",
        "ordering": false,
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
                mData: "cantidad",
                className: "text-right",
                width: "1%",
            },
            {
                mData: "nombre_umedida",
                className: "text-left",
                width: "30%",
            },
            {
                mData: "costo_unitario",
                className: "text-right",
                width: "15%",
            },
            {
                mData: "costo_total",
                className: "text-right",
                width: "15%",
            },
            {
                mData: "fecha_proceso",
                className: "text-center",
                width: "20%",
            },
            {
                mData: "fecha_hora",
                className: "text-center",
                width: "20%",
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
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
}

function tabsInsumos() {
    $("#tabUmedida").addClass('active');
    $('#tabUmedida').addClass('in');
    $("#tabStocks").removeClass('active');
    $('#tabStocks').removeClass('in');
    $("#tabCostos").removeClass('active');
    $('#tabCostos').removeClass('in');
    $("#tabProd").removeClass('active');
    $('#tabProd').removeClass('in');
    $("#tabReceta").removeClass('active');
    $('#tabReceta').removeClass('in');
    $("#tabObs").removeClass('active');
    $('#tabObs').removeClass('in');

    $("#tabUnidadMedidaInsumo").addClass('active');
    $('#tabUnidadMedidaInsumo').addClass('in');
    $("#tabStock").removeClass('active');
    $('#tabStock').removeClass('in');
    $("#tabCosto").removeClass('active');
    $('#tabCosto').removeClass('in');
    $("#tabProduccion").removeClass('active');
    $('#tabProduccion').removeClass('in');
    $("#tabRecetas").removeClass('active');
    $('#tabRecetas').removeClass('in');
    $("#tabObservaciones").removeClass('active');
    $('#tabObservaciones').removeClass('in');

    $("#tabUnidadMedidaInsumo").show();
    $("#tabStock").hide();
    $("#tabCosto").hide();
    $("#tabProduccion").hide();
    $("#tabStockInsumoReceta").hide();
    $("#tabRecetas").hide();
    $("#tabObservaciones").hide();

    $('#modalInsumo').modal({
        backdrop: 'static',
        keyboard: false
    });
}
</script>
;