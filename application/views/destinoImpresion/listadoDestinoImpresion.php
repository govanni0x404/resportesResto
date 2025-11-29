<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaDestinoImpresion = $('#tablaDestinoImpresion').DataTable();

    $(document).ready(function () {
        ListadoDestinoDeImpresionDatatable();
    });

    function ListadoDestinoDeImpresionDatatable() {
        if ($.fn.dataTable.isDataTable('#tablaDestinoImpresion')) {
            tablaDestinoImpresion = $('#tablaDestinoImpresion').DataTable();
            tablaDestinoImpresion.destroy();
        }
        tablaDestinoImpresion = $("#tablaDestinoImpresion").DataTable({
            "ajax": {
                "url": base_url + "index.php/DestinoDeImpresion/ListadoDestinoDeImpresionDatatable"
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
                mData: "id_dimpresion",
                className: "text-center"
            },
            {
                mData: "nombre_dimpresion",
                className: "text-center"
            },
            {
                mData: "ip",
                className: "text-center",
                visible: false

            },
            {
                mData: "puerto",
                className: "text-center",
                visible: false

            },
            {
                mData: "ruta_windows",
                className: "text-center",
                visible: false

            },
            {
                mData: "impresion_grafica_windows",
                className: "text-center",
                visible: false

            },

            {
                mData: "permite_precuenta",
                className: "text-center",
                visible: false

            },
            {
                mData: "impresion_local",
                className: "text-center",
                visible: false

            },
            {
                mData: "usar_logo_precuenta_pos",
                className: "text-center",
                visible: false

            },
            {
                mData: "size_font",
                className: "text-center",
                visible: false

            },
            {
                mData: "sin_impresion",
                className: "text-center",
                visible: false

            },
            {
                mData: "usar_dimpresion_recinto",
                className: "text-center",
                visible: false

            },
            {
                mData: "usar_dimpresion_precuenta_android",
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
        tablaDestinoImpresion
            .on('select', function (e, dt, type, indexes) {
                var rowData = tablaDestinoImpresion.rows(indexes).data().toArray();

                $("#idDestinoDeImpresion").val(rowData[0].id_dimpresion);
                $("#nombreDestinoDeImpresion").val(rowData[0].nombre_dimpresion);
                $("#rutaImpresora").val(rowData[0].ip);
                $("#PuertoImpresora").val(rowData[0].puerto);
                $("#RutaWindowsImpresora").val(rowData[0].ruta_windows);

                if (rowData[0].impresion_grafica_windows == 'SI') {
                    $('#ckImpresionGraficaWindows').prop('checked', true);
                } else {
                    $('#ckImpresionGraficaWindows').prop('checked', false);
                }
                if (rowData[0].impresion_local == 'SI') {
                    $('#ckPermitirImpresionLocal').prop('checked', true);
                } else {
                    $('#ckPermitirImpresionLocal').prop('checked', false);
                }
                if (rowData[0].usar_logo_precuenta_pos == 'SI') {
                    $('#ckUsarLogoPrecuenta').prop('checked', true);
                } else {
                    $('#ckUsarLogoPrecuenta').prop('checked', false);
                }
                if (rowData[0].sin_impresion == 'SI') {
                    $('#ckNoImprimirComprobantes').prop('checked', true);
                } else {
                    $('#ckNoImprimirComprobantes').prop('checked', false);
                }
                if (rowData[0].usar_dimpresion_recinto == 'SI') {
                    $('#CkReemplazarPorDestinoRecinto').prop('checked', true);
                } else {
                    $('#CkReemplazarPorDestinoRecinto').prop('checked', false);
                }
                if (rowData[0].usar_dimpresion_precuenta_android == 'SI') {
                    $('#CkPermitirImpresionPrecuenta').prop('checked', true);
                } else {
                    $('#CkPermitirImpresionPrecuenta').prop('checked', false);
                }

                $("#ZiseFont").val(rowData[0].size_font);
                $('#ModalNuevoDestinoDeImpresion').modal('show');
            })
    }
</script>