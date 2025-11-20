<script>
    var base_url = "<?php echo base_url(); ?>";
    tableInventarioPorBodega = $('#tableInventarioPorBodega').DataTable();

    $(document).ready(function () {
        var fecha = $('#fechaCaja').val();
        // llenarCboBodegas();
        //inventarioPorBodega();
        ListadoDeBodega("");
        ListarFamiliasInsumo("");
        $('#ckTodasLasBodegas').prop('checked', 'true');
        $("#cboBodegas").attr("disabled", true);
        $('#ckTodasLasFamilias').prop('checked', 'true');
        $("#cboFamiliasInsumos").attr("disabled", true);
    });

    $('#ckTodasLasBodegas').click(function () {
        if ($('#ckTodasLasBodegas').prop('checked') == false) {
            $("#cboBodegas").attr("disabled", false);
            ListadoDeBodega();

        } else {
            $("#cboBodegas").attr("disabled", true);
        }
    });

    $('#ckTodasLasFamilias').click(function () {
        if ($('#ckTodasLasFamilias').prop('checked') == false) {
            $("#cboFamiliasInsumos").attr("disabled", false);
            ListarFamiliasInsumo();
        } else {
            $("#cboFamiliasInsumos").attr("disabled", true);
        }
    });

    $('#btnFiltrar').on('click', function () {
        var id_bodega = $("#cboBodegas").val();
        console.log(id_bodega);
        if ($('#ckTodasLasBodegas').prop('checked') == false) {
            if (id_bodega == null || id_bodega == 0) {
                swal("error", "Debe seleccionar la bodega", "error");
                return;
            }
        } else {
            id_bodega = 0;
        }
        var id_grupo_insumo = $("#cboFamiliasInsumos").val();
        console.log(id_grupo_insumo);
        if ($('#ckTodasLasFamilias').prop('checked') == false) {
            if (id_grupo_insumo == null || id_grupo_insumo == 0) {
                swal("error", "Debe seleccionar la familia", "error");
                return;
            }
        } else {
            id_grupo_insumo = 0;
        }
        inventarioPorBodega(id_bodega, id_grupo_insumo);

    });

    function inventarioPorBodega(id_bodega, id_grupo_insumo) {
        let tituloExport = "Stock Por Bodega";
        if ($.fn.dataTable.isDataTable('#tableInventarioPorBodega')) {
            tableInventarioPorBodega = $('#tableInventarioPorBodega').DataTable();
            tableInventarioPorBodega.destroy();
        }
        tableInventarioPorBodega = $("#tableInventarioPorBodega").DataTable({
            "ajax": {
                "url": base_url + "index.php/InsumosPorBodega/inventarioPorBodega",
                "type": "POST",
                "data": {
                    'id_bodega': id_bodega,
                    'id_grupo_insumo': id_grupo_insumo,
                }
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
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                mData: "id_insumo",
                className: "text-center",
                "visible": false
            },
            {
                mData: "nombre_insumo",
                className: "text-center"
            },
            {
                mData: "nombre_grupo_insumo",
                className: "text-center",
            },
            {
                mData: "saldo",
                className: "text-center"
            },
            {
                mData: "nombre_umedida",
                className: "text-center"
            },
            {
                mData: "nombre_bodega",
                className: "text-center"
            },
            ],
            "drawCallback": function () {
                stockGeneral = this.api();
                $(stockGeneral.column(3).footer()).html(
                    'Stock Total: ' + stockGeneral.column(3, {
                        page: 'current'
                    }).data().sum()
                )
            },
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
            "buttons": [{
                text: "PDF",
                extend: 'pdf',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [1, 2, 3]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: tituloExport,
                className: "btn btn-danger"
            }]
        });
        tableInventarioPorBodega
            .on('select', function (e, dt, type, indexes) {
                var rowData = tableInventarioPorBodega.rows(indexes).data().toArray();
                var id_insumo = rowData[0].id_insumo;
                stockInsumos(id_insumo);
                $("#modalDetalleInsumos").modal({
                    backdrop: 'static',
                    keyboard: false,
                });
            });
    }

    function stockInsumos(id_insumo) {
        $.post(base_url + "index.php/InsumosPorBodega/detalleStockInsumos", {
            'id_insumo': id_insumo
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            tbl = "";
            console.log(obj);
            $.each(obj, function (index, v) {
                tbl += "<tr>";
                tbl += "<td class='text-center' style= 'height:10px'>" + v.nombre_insumo + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>" + v.saldo + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>" + v.nombre_bodega + "</td>";
                tbl += "</tr>";
            });
            $('#tableDetalleInsumos').html(tbl);
        })
    }

    function ListadoDeBodega(objSelected) {
        $.post(base_url + "index.php/Productos/ListadoDeBodega", {
            id: ""
        }, function (datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = '<option selected disabled value="0">Seleccione La Bodega</option>';
            $.each(obj, function (index, v) { // recorro el array
                if (v.nombre_bodega == objSelected) {
                    tbl = tbl + '<option selected value="' + v.id_bodega + '">' + v.nombre_bodega +
                        '</option>';
                } else {
                    tbl = tbl + '<option value="' + v.id_bodega + '">' + v.nombre_bodega + '</option>';
                }
            }); //fin each
            $("#cboBodegas").html(tbl);
            $("#cboBodegas").select2();
        });
    }

    function ListarFamiliasInsumo(objSelected) {
        $.post(base_url + "index.php/FamiliasInsumos/ListarFamiliasInsumo", {
            id: ""
        }, function (datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = '<option selected disabled value="0">Seleccione La Familia</option>';
            $.each(obj, function (index, v) { // recorro el array
                if (v.nombre_bodega == objSelected) {
                    tbl = tbl + '<option selected value="' + v.id_grupo_insumo + '">' + v.nombre_grupo_insumo +
                        '</option>';
                } else {
                    tbl = tbl + '<option value="' + v.id_grupo_insumo + '">' + v.nombre_grupo_insumo + '</option>';
                }
            }); //fin each
            $("#cboFamiliasInsumos").html(tbl);
            $("#cboFamiliasInsumos").select2();
        });
    }
</script>