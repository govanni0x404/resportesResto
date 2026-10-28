<script>
    var base_url = "<?php echo base_url(); ?>";
    tableVentasPorPagar = $("#tableVentasPorPagar").DataTable();
    tableDetallePedido = $("#tableDetallePedido").DataTable();
    tableVentasPorFamilia = $('#tableVentasPorFamilia').DataTable();
    tableDetalleDePropinas = $('#tableDetallePropinas').DataTable();
    // tableVentasPorProducto = $('#tableVentasPorProducto').DataTable();
    tableDetalleAtencion = $('#tableDetalleAtencion').DataTable();
    tablaFormaDePago = $('tablaFormaDePago');
    $(document).ready(function () {
        $("#cboGraficos").select2();
        /*cargarValidacionReporteDetalleDeAtencion();
        cargarValidacionReporteVentasPorMesas();
        cargarValidacionReporteVentasPorFamilias();
        cargarValidacionReporteVentasPorPagar();
        //cargarValidacionReporteResumenPorTipoDeDocumento();*/
        //cargarValidacionReporteResumenPorFormaDePago();
        //cargarValidacionReporteResumenCortesias();


        //var fecha = $('#fechaCaja').val();
        //$('#fechaCaja').daterangepicker({
        // format: 'DD-MM-YYYY'
        // });
        /*$('#fechaCaja').daterangepicker({
            singleDatePicker: false,
            format: 'DD/MM/YYYY',
            "locale": {
                "format": "DD/MM/YYYY",
                "separator": " - ",
                "applyLabel": "Elegir",
                "cancelLabel": "Cancelar",
                "fromLabel": "Desde",
                "toLabel": "Hasta",
                "customRangeLabel": "Custom",
                "daysOfWeek": [
                    "Dom",
                    "Lun",
                    "Mar",
                    "Mie",
                    "Jue",
                    "Vie",
                    "Sab"
                ],
                "monthNames": [
                    "Enero",
                    "Febrero",
                    "Marzo",
                    "Abril",
                    "Mayo",
                    "Junio",
                    "Julio",
                    "Agosto",
                    "Septiembre",
                    "Octubre",
                    "Noviembre",
                    "Diciembre"
                ],
                "firstDay": 1
            }
        });*/

        // var date = $('#fecha').data('daterangepicker').startDate.format('DD-MM-YYYY');
        // var hasta = $('#rangoFechas').data('daterangepicker').endDate.format('DD-MM-YYYY');
        // var id_caja = 288;
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        $('#divBodegas').hide();

        totalVenta(0, desde, hasta);
        resumenCaja(0, desde, hasta);
        resumenTipoDocumento(0, desde, hasta);
        resumenFormaPago(0, desde, hasta);
        resumenCortesias(0, desde, hasta);
        resumenVentasPorPagar(desde, hasta);
        ventasPorMesas(0, desde, hasta);
        detalleAtencion(0, desde, hasta);
        // pieChartFamilias();
        ventasPorFamilia(0, desde, hasta);
        // ventasPorProductos(0);
        //llenarCboCajas(desde, hasta);
        empresa();
        pieChartFamilias(0, desde, hasta);
    });

    /*
    $('#date-range-picker').daterangepicker({
        singleDatePicker: false,
        format: 'DD/MM/YYYY',
        "locale": {
            "format": "DD/MM/YYYY",
            "separator": " - ",
            "applyLabel": "Elegir",
            "cancelLabel": "Cancelar",
            "fromLabel": "Desde",
            "toLabel": "Hasta",
            "customRangeLabel": "Custom",
            "daysOfWeek": [
                "Dom",
                "Lun",
                "Mar",
                "Mie",
                "Jue",
                "Vie",
                "Sab"
            ],
            "monthNames": [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Septiembre",
                "Octubre",
                "Noviembre",
                "Diciembre"
            ],
            "firstDay": 1
        }
    });

    */
   /*
    $('#pruebaFecha').daterangepicker({
        singleDatePicker: false,
        format: 'DD/MM/YYYY',
        "locale": {
            "format": "DD/MM/YYYY",
            "separator": " - ",
            "applyLabel": "Elegir",
            "cancelLabel": "Cancelar",
            "fromLabel": "Desde",
            "toLabel": "Hasta",
            "customRangeLabel": "Custom",
            "daysOfWeek": [
                "Dom",
                "Lun",
                "Mar",
                "Mie",
                "Jue",
                "Vie",
                "Sab"
            ],
            "monthNames": [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Septiembre",
                "Octubre",
                "Noviembre",
                "Diciembre"
            ],
            "firstDay": 1
        }
    });
    */

        $('#date-range-picker').daterangepicker({
        ranges: dateRange,
        format: 'DD-MM-YYYY',
        locale: {
            format: 'DD-MM-YYYY', // Formato de fecha
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            customRangeLabel: 'Rango personalizado',
            weekLabel: 'S',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
            monthNames: [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ],
            firstDay: 1 // Comienza el lunes
        }
    });

    function updateInput(start, end) {
        $('#date-range-picker').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
    }

    $('#date-range-picker').on('apply.daterangepicker', function (ev, picker) {
        updateInput(picker.startDate, picker.endDate);
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('.daterangepicker').length && !$(event.target).is('#date-range-picker')) {
            var picker = $('#date-range-picker').data('daterangepicker');
            if (picker) {
                updateInput(picker.startDate, picker.endDate);
            }
        }
    });

    $("#btnTest").on("click", function () {
        var id_caja = $('#cboCajas').find('option:selected').attr('value');
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        var fecha = $('#date-range-picker').val();
        var cambioFecha = $("#pruebaFecha").val();
        // alert(date);
        if (fecha != cambioFecha) {
            llenarCboCajas(desde, hasta);
            totalVenta(0, desde, hasta);
            resumenCaja(0, desde, hasta);
            resumenTipoDocumento(0, desde, hasta);
            resumenFormaPago(0, desde, hasta);
            resumenCortesias(0, desde, hasta);
            resumenVentasPorPagar();
            if (document.getElementById('chartFamilias')) {
                pieChartFamilias(0, desde, hasta);
            } else {
                pieChartFamilias(0, desde, hasta);
            }
            ventasPorFamilia(0, desde, hasta);
            detalleAtencion(0, desde, hasta);
            ventasPorMesas(0, desde, hasta);
            $("#pruebaFecha").val(fecha);
        } else {
            if ($("#cboGraficos").val() == 0) {
                totalVenta(0, desde, hasta);
                resumenCaja(0, desde, hasta);
                resumenTipoDocumento(0, desde, hasta);
                resumenFormaPago(0, desde, hasta);
                resumenCortesias(0, desde, hasta);
                resumenVentasPorPagar();
                if (document.getElementById('chartFamilias')) {
                    pieChartFamilias(0, desde, hasta);
                } else {
                    pieChartFamilias(0, desde, hasta);
                }
                ventasPorFamilia(0, desde, hasta);
                detalleAtencion(0, desde, hasta);
                ventasPorMesas(0, desde, hasta);
            } else {
                totalVenta(id_caja, desde, hasta);
                resumenCaja(id_caja, desde, hasta);
                resumenTipoDocumento(id_caja, desde, hasta);
                resumenFormaPago(id_caja, desde, hasta);
                resumenCortesias(id_caja, desde, hasta);
                resumenVentasPorPagar();
                if (document.getElementById('chartFamilias')) {
                    pieChartFamilias(id_caja, desde, hasta);
                } else {
                    pieChartFamilias(id_caja, desde, hasta);
                }
                ventasPorFamilia(id_caja, desde, hasta);
                detalleAtencion(id_caja, desde, hasta);
                ventasPorMesas(id_caja, desde, hasta);
            }
        }
    });

    //OBTENER DATOS DE EL PRODUCTO CUANDO SE SELECCIONA EL PRODUCTO
    $("#cboGraficos").on('change', function (e) {
        if ($('#cboGraficos').val() == 1) {
            document.getElementById("ventasPorFamilias").classList.remove('active', 'in');
            document.getElementById("detalleDeMesas").classList.remove('active', 'in');
            document.getElementById("detalleAtencionAgarzones").classList.remove('active', 'in');
            document.getElementById("cajaActual").classList.add('active', 'in');

        } else if ($('#cboGraficos').val() == 2) {
            chartFamilias.update();
            var id_caja = $('#cboCajas').find('option:selected').attr('value');
            //var fecha = $('#fechaCaja').val();
            var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
            var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
            document.getElementById("cajaActual").classList.remove('active', 'in');
            document.getElementById("detalleDeMesas").classList.remove('active', 'in');
            document.getElementById("detalleAtencionAgarzones").classList.remove('active', 'in');
            document.getElementById("ventasPorFamilias").classList.add('active', 'in');
            if (document.getElementById('chartFamilias')) {
                if (id_caja == 0) {
                    pieChartFamilias(0, desde, hasta);
                } else {
                    pieChartFamilias(id_caja, desde, hasta);
                }
            }
            ventasPorFamilia(id_caja, desde, hasta);
        } else if ($('#cboGraficos').val() == 3) {
            document.getElementById("cajaActual").classList.remove('active', 'in');
            document.getElementById("ventasPorFamilias").classList.remove('active', 'in');
            document.getElementById("detalleAtencionAgarzones").classList.remove('active', 'in');
            document.getElementById("detalleDeMesas").classList.add('active', 'in');

        } else if ($('#cboGraficos').val() == 4) {
            var id_caja = $('#cboCajas').find('option:selected').attr('value');
            //var fecha = $('#fechaCaja').val();
            var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
            var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
            document.getElementById("cajaActual").classList.remove('active', 'in');
            document.getElementById("ventasPorFamilias").classList.remove('active', 'in');
            document.getElementById("detalleDeMesas").classList.remove('active', 'in');
            document.getElementById("detalleAtencionAgarzones").classList.add('active', 'in');

        }
    });

    // $("#cboCajas").on('change', function(e) {
    // 	var id_caja = $('#cboCajas').find('option:selected').attr('value');
    // 	var fecha = $('#fechaCaja').val();
    // 	// alert(date);
    // 	totalVenta(id_caja, fecha);
    // 	resumenCaja(id_caja, fecha);
    // 	resumenTipoDocumento(id_caja, fecha);
    // 	resumenFormaPago(id_caja, fecha);
    // 	resumenVentasPorPagar();
    // 	if (document.getElementById('chartFamilias')) {
    // 		pieChartFamilias(id_caja, fecha);
    // 	} else {
    // 		pieChartFamilias(id_caja, fecha);
    // 	}
    // 	ventasPorFamilia(id_caja, fecha);
    // 	detalleAtencion(id_caja, fecha);
    // 	ventasPorMesas(id_caja, fecha);

    // 	// ventasPorProductos(id_caja);
    // });

    function commaSeparateNumber(val) {
        if (val > 0) {
            while (/(\d+)(\d{3})/.test(val.toString())) {
                val = val.toString().replace(/(\d+)(\d{3})/, '$1' + ',' + '$2');
            }
            return val;
        }
    }

    function empresa() {
        $.post(base_url + "index.php/Dashboard/empresa", {}, function () { }).done(function (data) {
            try {
                var obj = jQuery.parseJSON(data);
                console.log("Datos empresa:", obj); // Debug
                
                // Si no hay datos de empresa o el array está vacío
                if (!obj || obj.length === 0) {
                    mostrarAlertaEmpresaVacia();
                    return;
                }
                
                $('#local').text(obj[0].nombre_fantasia || '');
                $('#lblNombreCliente').text(obj[0].razon_social || '');
                
                // Validar datos importantes de empresa
                validarDatosEmpresa(obj[0]);
                
            } catch (e) {
                console.log("Error parsing empresa:", e, data);
            }
        }).fail(function () {
            swal("Se produjo un error al realizar el proceso, favor vuelva a intentarlo");
        });
    }

    function mostrarAlertaEmpresaVacia() {
        var mensaje = 'No hay datos de empresa registrados en el sistema.\n\n';
        mensaje += 'Por favor ingrese los datos de la empresa en el icono de engranaje -> Configuraciones -> Datos de la Empresa.';
        
        if (typeof swal !== 'undefined') {
            swal({
                title: "Datos de Empresa Requeridos",
                text: mensaje,
                icon: "warning",
                buttons: {
                    configurar: {
                        text: "Ir a Configurar",
                        value: "configurar",
                    },
                    cerrar: {
                        text: "Cerrar",
                        value: "cerrar",
                    }
                }
            }).then((value) => {
                if (value === "configurar") {
                    window.location.href = base_url + "index.php/ConfiguracionesGenerales";
                }
            });
        } else {
            alert(mensaje);
        }
    }

    function validarDatosEmpresa(empresa) {
        console.log("Validando empresa:", empresa); // Debug
        var camposFaltantes = [];
        
        // Campos importantes de empresa a validar
        var rut = empresa.rut_empresa;
        var dv = empresa.dv_empresa;
        var razon = empresa.razon_social;
        var giro = empresa.giro;
        var direccion = empresa.direccion_empresa;
        
        if (!rut || rut == '' || rut == 0 || rut == null) {
            camposFaltantes.push('RUT Empresa');
        }
        if (!dv || dv == '' || dv == null) {
            camposFaltantes.push('Dígito Verificador');
        }
        if (!razon || razon == '' || razon == null) {
            camposFaltantes.push('Razón Social');
        }
        if (!giro || giro == '' || giro == null) {
            camposFaltantes.push('Giro');
        }
        if (!direccion || direccion == '' || direccion == null) {
            camposFaltantes.push('Dirección');
        }
        
        console.log("Campos faltantes:", camposFaltantes); // Debug
        
        if (camposFaltantes.length > 0) {
            var mensaje = 'ATENCION: Faltan datos importantes de la empresa:\n\n';
            mensaje += '- ' + camposFaltantes.join('\n- ');
            mensaje += '\n\nPor favor complete estos datos en Configuraciones Generales -> Datos de la Empresa.';
            
            // Usar swal si está disponible, sino usar alert
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Datos de Empresa Incompletos",
                    text: mensaje,
                    icon: "warning",
                    buttons: {
                        configurar: {
                            text: "Ir a Configurar",
                            value: "configurar",
                        },
                        cerrar: {
                            text: "Cerrar",
                            value: "cerrar",
                        }
                    }
                }).then((value) => {
                    if (value === "configurar") {
                        window.location.href = base_url + "index.php/ConfiguracionesGenerales";
                    }
                });
            } else {
                alert(mensaje);
            }
        }
    }

    function totalVenta(id_caja, desde, hasta) {
        $.post(base_url + "index.php/Dashboard/totalVentas", {
            'id_caja': id_caja,
            //'fecha': fecha
            'desde': desde,
            'hasta': hasta
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            if (obj[0].total != 0) {
                $('#totalVentas').text(commaSeparateNumber(obj[0].total));
                // $('#totalVentas').counterUp({
                // 	delay: 10,
                // 	time: 2000
                // });
            } else {
                $('#totalVentas').text(0);
            }
        }).done(function (datos) { })
    }

    function resumenCaja(id_caja, desde, hasta) {
        $.post(base_url + "index.php/Dashboard/resumenCaja", {
            'id_caja': id_caja,
            //'fecha': fecha
            'desde': desde,
            'hasta': hasta
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            $.each(obj, function (index, v) {
                if (v.fondo_fijo > 0) {
                    $('#fondoFijo').text(commaSeparateNumber(v.fondo_fijo));
                    // $('#fondoFijo').counterUp({
                    // 	delay: 10,
                    // 	time: 2000
                    // });
                } else {
                    $('#fondoFijo').text(0);
                }
                if (v.descuento > 0) {
                    $('#descuentos').text(commaSeparateNumber(v.descuento));
                    // $('#descuentos').counterUp({
                    // 	delay: 10,
                    // 	time: 2000
                    // });
                } else {
                    $('#descuentos').text(0);
                }
                if (v.propina > 0) {
                    $('#propinas').text(commaSeparateNumber(v.propina));
                    // $('#propinas').counterUp({
                    // 	delay: 10,
                    // 	time: 2000
                    // });
                } else {
                    $('#propinas').text(0);
                }
                if (v.ingresos > 0) {
                    $('#ingresos').text(commaSeparateNumber(v.ingresos));
                    // $('#ingresos').counterUp({
                    // 	delay: 10,
                    // 	time: 2000
                    // });
                } else {
                    $('#ingresos').text(0);
                }
                if (v.egresos > 0) {
                    $('#egresos').text(commaSeparateNumber(v.egresos));
                    // $('#egresos').counterUp({
                    // 	delay: 10,
                    // 	time: 2000
                    // });
                } else {
                    $('#egresos').text(0);
                }
            });
        }).done(function (datos) { })
    }

    var totalesFamilias = [];
    var familias = [];
    dataFamilias = {
        labels: familias,
        datasets: [{
            data: totalesFamilias,
            backgroundColor: [
                "#C0533A",
                "#E0A93B",
                "#6F8F3D",
                "#3E7C8C",
                "#8B4F6E",
                "#D98C4F",
                "#9DB07A",
                "#5B6B7F",
                "#D48A8A",
                "#CBB28B",
                "#5FA39A",
                "#6B4A3A",
            ],
        }]
    },

        configFamilias = {
            type: 'doughnut',
            data: dataFamilias,
            options: {
                responsive: true,
                // maintainAspectRatio: true,
                // padding: 1000,
                aspectRatio: 2,
                cutout: '58%',
                hoverOffset: 8,
                borderWidth: 2,
                borderColor: '#FFFFFF',
                hoverBorderColor: '#FFFFFF',
                layout: { padding: 8 },
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 14, color: '#3A2A22', font: { size: 12 } }
                    },
                    tooltip: { backgroundColor: '#2A1A12', padding: 10, cornerRadius: 8 }
                }
            }
        };

    const chartFamilias = new Chart(
        document.getElementById('chartFamilias'),
        configFamilias,
    );

    function pieChartFamilias(id_caja, desde, hasta) {
        //pie chart
        $.post(base_url + "index.php/Dashboard/pieVentasPorFamilia", {
            'id_caja': id_caja,
            //'fecha': fecha
            'desde': desde,
            'hasta': hasta
        }, function (datos) {
            try {
                var obj = $.parseJSON(datos);
                var sum = 0;
                $.each(obj, function (index, v) {
                    familias[index] = v.nombre_familia + ' / ' + v.porcentaje + '%';
                    totalesFamilias[index] = v.monto_total;
                    sum += parseInt(v.monto_total);
                });
                if (obj.length <= 0 || sum <= 0) {
                    $('#grafico').hide();
                } else {
                    $('#grafico').show();
                }
                chartFamilias.update();
            } catch (e) {
                console.log(e);
            }
        })
    }

    function ventasPorFamilia(id_caja, desde, hasta) {
        let tituloExport = "Ventas Por Familia";
        if ($.fn.dataTable.isDataTable('#tableVentasPorFamilia')) {
            tableVentasPorFamilia = $('#tableVentasPorFamilia').DataTable();
            tableVentasPorFamilia.destroy();
        }
        tableVentasPorFamilia = $("#tableVentasPorFamilia").DataTable({
            "ajax": {
                "url": base_url + "index.php/Dashboard/tableVentasPorFamilia",
                "type": "POST",
                "data": {
                    'id_caja': id_caja,
                    //'fecha': fecha
                    'desde': desde,
                    'hasta': hasta
                }
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 20,
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
                mData: "id_familia",
                className: "text-center",
                "visible": false
            },
            {
                mData: "nombre_familia",
                className: "text-center"
            },
            {
                mData: "cantidad",
                className: "text-center"
            },
            /*
            {
                mData: "monto_neto",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "monto_iva",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },*/
            {
                mData: "monto_total",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "btnVerDetalleFamilia",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "test",
                "width": "1%",
                className: 'dtr-control',
                orderable: false,
                targets: -1
            }
            ],
            "drawCallback": function () {
                totalCantidad = this.api();
                $(totalCantidad.column(2).footer()).html(
                    ' ' + totalCantidad.column(2, {
                        page: 'current'
                    }).data().sum()
                )

                /*
                var totalNeto = this.api();
                var totalN = totalNeto.column(3, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalN.toFixed(0));
                $(totalNeto.column(3).footer()).html(
                    '$' + totalFormatted
                );


                var totalIva = this.api();
                var totalI = totalIva.column(4, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalI.toFixed(0));
                $(totalIva.column(4).footer()).html(
                    '$' + totalFormatted
                );
*/
                var totalPorFamilias = this.api();
                var totalFamilia = totalPorFamilias.column(3, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalFamilia.toFixed(0));
                $(totalPorFamilias.column(3).footer()).html(
                    '$' + totalFormatted
                );

                msgTotales = this.api();
                $(msgTotales.column(4).footer()).html(
                    'Totales'
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
                    columns: [1, 2, 3, 4, 5]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: tituloExport,
                className: "btn btn-danger"
            }]
        });
    }

    function ventasPorProductos(id_familia) {
        var id_caja = $('#cboCajas').find('option:selected').attr('value');
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        var fecha = $('#fechaCaja').val();
        let tituloExport = "Ventas Por Producto";
        if ($.fn.dataTable.isDataTable('#tableVentasPorProducto')) {
            tableVentasPorProducto = $('#tableVentasPorProducto').DataTable();
            tableVentasPorProducto.destroy();
        }
        tableVentasPorProducto = $("#tableVentasPorProducto").DataTable({
            "ajax": {
                "url": base_url + "index.php/Dashboard/ventasPorProducto",
                "type": "POST",
                "data": {
                    'id_caja': id_caja,
                    'id_familia': id_familia,
                    //'fecha': fecha
                    'desde': desde,
                    'hasta': hasta
                }
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 20,
            "select": true,
            "dom": "Bfrtip",
            "ordering": true,
            "filter": true,
            "info": false,
            "footer": false,
            "columns": [{
                mData: "nombre_producto",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "cantidad",
                "width": "1%",
                className: "text-center"
            },
            /*
            {
                mData: "monto_neto",
                "width": "1%",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "monto_iva",
                "width": "1%",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },*/
            {
                mData: "monto_total",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            }
            ],
            "drawCallback": function () {
                totalCantidad = this.api();
                $(totalCantidad.column(1).footer()).html(
                    ' ' + totalCantidad.column(1, {
                        page: 'current'
                    }).data().sum()
                )
                /*
                                var totalNeto = this.api();
                                var totalN = totalNeto.column(2, { page: 'current' }).data().sum();
                                function formatNumber(num) {
                                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                }
                                var totalFormatted = formatNumber(totalN.toFixed(0));
                                $(totalNeto.column(2).footer()).html(
                                    '$' + totalFormatted
                                );
                
                
                                var totalIva = this.api();
                                var totalI = totalIva.column(3, { page: 'current' }).data().sum();
                                function formatNumber(num) {
                                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                }
                                var totalFormatted = formatNumber(totalI.toFixed(0));
                                $(totalIva.column(3).footer()).html(
                                    '$' + totalFormatted
                                );
                */
                var totalProductos = this.api();
                var totalP = totalProductos.column(2, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalP.toFixed(0));
                $(totalProductos.column(2).footer()).html(
                    '$' + totalFormatted
                );

                msgTotales = this.api();
                $(msgTotales.column(0).footer()).html(
                    'TOTALES '
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
            "buttons": []
        });
    }

    function resumenTipoDocumento(id_caja, desde, hasta) {
        $.post(base_url + "index.php/Dashboard/resumenTipoDocumento", {
            'id_caja': id_caja,
            //'fecha': fecha
            'desde': desde,
            'hasta': hasta
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            tbl = "";
            var totalNeto = 0;
            var totalIva = 0;
            var total = 0;
            $.each(obj, function (index, v) {
                tbl += "<tr>";
                tbl += "<td class='text-center' style= 'height:10px' >" + v.nombre_tdocumento + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>" + commaSeparateNumber(v.cantidad) + "</td>";
                // tbl += "<td class='text-right' style= 'height:10px'>$" + commaSeparateNumber(v.neto) + "</td>";
                if (v.id_tdocumento == 5) {
                    //tbl += "<td class='text-right ocultarNetoTdocumento' style= 'height:10px'>$" + commaSeparateNumber(v.total) + "</td>";
                    //tbl += "<td class='text-right ocultarIvaTdocumento' style= 'height:10px'>$" + 0 + "</td>";
                    tbl += "<td class='text-center' style= 'height:10px'>$" + commaSeparateNumber(v.total) + "</td>";
                    //totalNeto += parseInt(v.total);
                    //totalIva += parseInt('0');
                    total += parseInt(v.total);
                } else {
                    //tbl += "<td class='text-right ocultarNetoTdocumento' style= 'height:10px'>$" + commaSeparateNumber(v.neto) + "</td>";
                    //tbl += "<td class='text-right ocultarIvaTdocumento' style= 'height:10px'>$" + commaSeparateNumber(v.iva) + "</td>";
                    tbl += "<td class='text-center' style= 'height:10px'>$" + commaSeparateNumber(v.total) + "</td>";
                    //totalNeto += parseInt(v.neto);
                    //totalIva += parseInt(v.iva);
                    total += parseInt(v.total);
                }

                tbl += "</tr>";
                // totalNeto += parseInt(v.neto);

            });
            if (total > 0) {
                tbl += "<tr><td colspan='2' class='text text-left'><h4><b>TOTAL</b></h4></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(total) + "</b></h4></td></tr>";
            }
            /*
            if (total > 0) {
                if (totalIva > 0) {
                    tbl += "<tr><td colspan='2' class='text text-left'><h4><b>TOTAL</b></h4></td><td class='text text-right ocultarNetoTdocumento'><h4><b>$" + commaSeparateNumber(totalNeto) + "</b></h4></td><td class='text text-right ocultarIvaTdocumento'><h4><b>$" + commaSeparateNumber(totalIva) + "</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(total) + "</b></h4></td></tr>";
                } else {
                    tbl += "<tr><td colspan='2' class='text text-left'><h4><b>TOTAL</b></h4></td><td class='text text-right ocultarNetoTdocumento'><h4><b>$" + commaSeparateNumber(totalNeto) + "</b></h4></td><td class='text text-right ocultarIvaTdocumento'><h4><b>$" + 0 + "</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(total) + "</b></h4></td></tr>";
                }
            }*/
            $('#resumenTipoDocumento').html(tbl);

        })
    }

    /*
    function cargarValidacionReporteResumenPorTipoDeDocumento() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_solo_monto_total_en_reportes == 't') {
                    $('.ocultarNetoTdocumento').hide();
                    $('.ocultarIvaTdocumento').hide();
                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }*/

    function resumenFormaPago(id_caja, desde, hasta) {
        $.post(base_url + "index.php/Dashboard/resumenFormaPago", {
            'id_caja': id_caja,
            //'fecha': fecha
            'desde': desde,
            'hasta': hasta
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            tbl = "";
            var totalNeto = 0;
            var totalIva = 0;
            var total = 0;
            var totalPropina = 0;
            $.each(obj, function (index, v) {
                tbl += "<tr>";
                tbl += "<td class='text-center' style= 'height:10px'><span class='dash-chip'>" + v.nombre_fpago + "</span></td>";
                tbl += "<td class='text-center' style= 'height:10px'>" + v.cantidad + "</td>";

                //tbl += "<td class='text-right ocultarNetoFormaDePago' style= 'height:10px'>$" + commaSeparateNumber(v.neto) + "</td>";
                //tbl += "<td class='text-right ocultarIvaFormaDePago' style= 'height:10px'>$" + commaSeparateNumber(v.iva) + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>$" + commaSeparateNumber(v.total) + "</td>";

                tbl += "<td class='text-center' style= 'height:10px'>$" + (v.propina > 0 ? commaSeparateNumber(v.propina) : 0) + "</td>";
                tbl += "</tr>";
                //totalNeto += parseInt(v.neto);
                //totalIva += parseInt(v.iva);
                total += parseInt(v.total);
                totalPropina += parseInt(v.propina);


            });
            if (total > 0) {
                tbl += "<tr><td colspan='2' class='text text-left'><h4><b>TOTALES</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(total) + "</b></h4></td><td class='text text-right'><h4><b>$" + (totalPropina > 0 ? commaSeparateNumber(totalPropina) : 0) + "</b></h4></td></tr>";
                //tbl += "<tr><td colspan='2' class='text text-left'><h4><b>TOTALES</b></h4></td><td class='text text-right ocultarNetoFormaDePago'><h4><b>$" + commaSeparateNumber(totalNeto) + "</b></h4></td><td class='text text-right ocultarIvaFormaDePago'><h4><b>$" + commaSeparateNumber(totalIva) + "</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(total) + "</b></h4></td><td class='text text-right'><h4><b>$" + (totalPropina > 0 ? commaSeparateNumber(totalPropina) : 0) + "</b></h4></td></tr>";
            }
            $('#resumenFormaPago').html(tbl);

        })
    }

    /*
    function cargarValidacionReporteResumenPorFormaDePago() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_solo_monto_total_en_reportes == 't') {
                    $('.ocultarNetoFormaDePago').hide();
                    $('.ocultarIvaFormaDePago').hide();
                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }*/

    function resumenCortesias(id_caja, desde, hasta) {
        $.post(base_url + "index.php/Dashboard/resumenCortesias", {
            'id_caja': id_caja,
            //'fecha': fecha
            'desde': desde,
            'hasta': hasta
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            tbl = "";
            var totalNeto = 0;
            var totalIva = 0;
            var total = 0;
            var totalPropina = 0;
            $.each(obj, function (index, v) {
                tbl += "<tr>";
                tbl += "<td class='text-center' style= 'height:10px'>" + v.nombre_fpago + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>" + v.cantidad + "</td>";
                //tbl += "<td class='text-right ocultarNetoCortesias' style= 'height:10px'>$" + commaSeparateNumber(v.neto) + "</td>";
                //tbl += "<td class='text-right ocultarIvaCortesias' style= 'height:10px'>$" + commaSeparateNumber(v.iva) + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>$" + commaSeparateNumber(v.total) + "</td>";
                tbl += "</tr>";
                //totalNeto += parseInt(v.neto);
                //totalIva += parseInt(v.iva);
                total += parseInt(v.total);
                totalPropina += parseInt(v.propina);
            });
            if (total > 0) {
                tbl += "<tr><td colspan='2' class='text text-left'><h4><b>TOTALES</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(total) + "</b></h4></td></tr>";
                //tbl += "<tr><td colspan='2' class='text text-left'><h4><b>TOTALES</b></h4></td><td class='text text-right ocultarNetoCortesias'><h4><b>$" + commaSeparateNumber(totalNeto) + "</b></h4></td><td class='text text-right ocultarIvaCortesias'><h4><b>$" + commaSeparateNumber(totalIva) + "</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(total) + "</b></h4></td></tr>";
            }
            $('#resumenCortesias').html(tbl);

        })
    }

    /*
    function cargarValidacionReporteResumenCortesias() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_solo_monto_total_en_reportes == 't') {
                    $('.ocultarNetoCortesias').hide();
                    $('.ocultarIvaCortesias').hide();
                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }*/

    //cargar datos en datatable Productos
    function resumenVentasPorPagar() {
        let tituloExport = "Resumen Ventas Por Pagar";
        if ($.fn.dataTable.isDataTable('#tableVentasPorPagar')) {
            tableVentasPorPagar = $('#tableVentasPorPagar').DataTable();
            tableVentasPorPagar.destroy();
        }
        tableVentasPorPagar = $("#tableVentasPorPagar").DataTable({
            "ajax": {
                "test": 0,
                "url": base_url + "index.php/Dashboard/resumenVentasPorPagar",
                "type": "POST"
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
                mData: "id_cotizacion",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "id_mesa",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "fecha_hora",
                className: "text-center",
            },
            {
                mData: "garzon",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "nombre_cliente",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "cuenta",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "descuento",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            /*
            {
                mData: "neto",
                "width": "1%",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "iva",
                "width": "1%",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },*/
            {
                mData: "total",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "id_divpedido",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "btnVerMesas",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "test",
                "width": "1%",
                className: 'dtr-control',
                orderable: false,
                targets: -1
            }
            ],
            "drawCallback": function () {
                var totalCuenta = this.api();
                var totalc = totalCuenta.column(5, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalc.toFixed(0));
                $(totalCuenta.column(5).footer()).html(
                    '$' + totalFormatted
                );

                var totalDescuentos = this.api();
                var totalD = totalDescuentos.column(6, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalD.toFixed(0));
                $(totalDescuentos.column(6).footer()).html(
                    '$' + totalFormatted
                );

                /*
                var totalNeto = this.api();
                var totalN = totalNeto.column(7, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalN.toFixed(0));
                $(totalNeto.column(7).footer()).html(
                    '$' + totalFormatted
                );


                var totalIva = this.api();
                var totalI = totalIva.column(8, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalI.toFixed(0));
                $(totalIva.column(8).footer()).html(
                    '$' + totalFormatted
                );
*/

                /*
                                var totalGeneral = this.api();
                                var totalG = totalGeneral.column(9, { page: 'current' }).data().sum();
                                function formatNumber(num) {
                                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                }
                                var totalFormatted = formatNumber(totalG.toFixed(0));
                                $(totalGeneral.column(9).footer()).html(
                                    '$' + totalFormatted
                                );*/

                var totalGeneral = this.api();
                var totalG = totalGeneral.column(7, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalG.toFixed(0));
                $(totalGeneral.column(7).footer()).html(
                    '$' + totalFormatted
                );

                msgTotales = this.api();
                $(msgTotales.column(10).footer()).html(
                    'totales'
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
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Ventas Por Pagar',
                className: "btn btn-danger"
            }]
        });
    }

    function ventasPorMesas(id_caja, desde, hasta) {
        let tituloExport = "Ventas Por Mesas";
        if ($.fn.dataTable.isDataTable('#tableVentasPorMesa')) {
            tableVentasPorMesa = $('#tableVentasPorMesa').DataTable();
            tableVentasPorMesa.destroy();
        }
        tableVentasPorMesa = $("#tableVentasPorMesa").DataTable({
            "ajax": {
                "url": base_url + "index.php/Dashboard/ventasPorMesas",
                "type": "POST",
                "data": {
                    'id_caja': id_caja,
                    //'fecha': fecha
                    'desde': desde,
                    'hasta': hasta
                }
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 20,
            "select": true,
            "dom": "Bfrtip",
            "ordering": true,
            "filter": true,
            "info": false,
            "footer": false,
            "columns": [{
                mData: "id_mesa",
                className: "text-center"
            },
            {
                mData: "cantidad",
                className: "text-center"
            },
            /*{
                mData: "monto_neto",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "monto_iva",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },*/
            {
                mData: "monto_total",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            }
            ],
            "drawCallback": function () {
                totalCantidad = this.api();
                $(totalCantidad.column(1).footer()).html(
                    ' ' + totalCantidad.column(1, {
                        page: 'current'
                    }).data().sum()
                )

                /*
                var totalNeto = this.api();
                var totalN = totalNeto.column(2, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalN.toFixed(0));
                $(totalNeto.column(2).footer()).html(
                    '$' + totalFormatted
                );


                var totalIva = this.api();
                var totalI = totalIva.column(3, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalI.toFixed(0));
                $(totalIva.column(3).footer()).html(
                    '$' + totalFormatted
                );
*/
                var totalGeneral = this.api();
                var totalP = totalGeneral.column(2, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalP.toFixed(0));
                $(totalGeneral.column(2).footer()).html(
                    '$' + totalFormatted
                );

                msgTotales = this.api();
                $(msgTotales.column(0).footer()).html(
                    'Totales'
                )
            },
            /*
            "footerCallback": function(row, data, start, end, display) {
                montoMesa = this.api()
                    .column(4) //numero de columna a sumar
                    .data()
                    .reduce(function(a, b) {
                        return parseInt(a) + parseInt(b);
                    }, 0);
                if (montoMesa > 0) {
                    $('#montoMesa').show();
                } else {
                    $('#montoMesa').hide();
                }
                $('#montoMesa').text("TOTAL VENTAS: $" + new Intl.NumberFormat().format(montoMesa));
            },*/
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
                    columns: [0, 1, 2, 3, 4]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: tituloExport,
                className: "btn btn-danger"
            }]
        });
    }

    function detalleMesas(id_cotizacion) {
        let tituloExport = "DetallePedido";
        if ($.fn.dataTable.isDataTable('#tableDetallePedido')) {
            tableDetallePedido = $('#tableDetallePedido').DataTable();
            tableDetallePedido.destroy();
        }

        tableDetallePedido = $("#tableDetallePedido").DataTable({
            "ajax": {
                "url": base_url + "index.php/Dashboard/detalleMesas",
                "type": "POST",
                "data": {
                    'id_cotizacion': id_cotizacion
                }
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 10,
            "select": true,
            "dom": "Bfrtip",
            "filter": true,
            "footer": false,
            "info": false,
            "ordering": true,
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                mData: null,
                render: function (data, type, row, meta) {
                    return data.nombre_producto
                },
                className: "text-center"
            },
            {
                mData: "cantidad",
                className: "text-center"
            },
            {
                mData: "bruto_un",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            /*
            {
                mData: "neto",
                className: "text-right"
            },
            {
                mData: "iva",
                className: "text-right"
            },*/
            {
                mData: "total_bruto",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: null,
                render: function (data, type, row, meta) {
                    return data.hora_pedido
                },
                className: "text-center"
            },
            {
                mData: "test",
                "width": "1%",
                className: 'dtr-control',
                orderable: false,
                targets: -1
            }
            ],
            "drawCallback": function () {
                totalCantidad = this.api();
                $(totalCantidad.column(1).footer()).html(
                    ' ' + totalCantidad.column(1, {
                        page: 'current'
                    }).data().sum()
                )
                /*
                totalUnitario = this.api();
                $(totalUnitario.column(2).footer()).html(
                    ' $' + totalUnitario.column(2, {
                        page: 'current'
                    }).data().sum()
                )*/
                /*
                 var totalNeto = this.api();
                 var totalN = totalNeto.column(3, { page: 'current' }).data().sum();
                 function formatNumber(num) {
                     return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                 }
                 var totalFormatted = formatNumber(totalN.toFixed(0));
 
                 $(totalNeto.column(3).footer()).html(
                     '$' + totalFormatted
                 );
 
 
                 var totalIva = this.api();
                 var totalI = totalIva.column(4, { page: 'current' }).data().sum();
                 function formatNumber(num) {
                     return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                 }
                 var totalFormatted = formatNumber(totalI.toFixed(0));
 
                 $(totalIva.column(4).footer()).html(
                     '$' + totalFormatted
                 );
 */
                var totalGeneral = this.api();
                var totalP = totalGeneral.column(3, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalP.toFixed(0));

                $(totalGeneral.column(3).footer()).html(
                    '$' + totalFormatted
                );
                msgTotales = this.api();
                $(msgTotales.column(0).footer()).html(
                    'Totales'
                )
            },
            "language": {
                url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
            },
            "buttons": []
        });
    }

    function detalleAtencion(id_caja, desde, hasta) {
        let tituloExport = "Detalle Atencion";
        if ($.fn.dataTable.isDataTable('#tableDetalleAtencion')) {
            tableDetalleAtencion = $('#tableDetalleAtencion').DataTable();
            tableDetalleAtencion.destroy();

        }
        tableDetalleAtencion = $("#tableDetalleAtencion").DataTable({
            "ajax": {
                "url": base_url + "index.php/Dashboard/detalleAtencion",
                "type": "POST",
                "data": {
                    'id_caja': id_caja,
                    //'fecha': fecha
                    'desde': desde,
                    'hasta': hasta
                }
            },


            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 20,
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
                mData: "nombre_usuario",
                className: "text-center"
            },
            {
                mData: "mesas_atendidas",
                className: "text-center"
            },
            /*{
                mData: "neto",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "iva",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },*/
            {
                mData: "ventas",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "propina",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "test",
                "width": "1%",
                className: 'dtr-control',
                orderable: false,
                targets: -1
            }
            ],
            "drawCallback": function () {
                totalMesas = this.api();
                $(totalMesas.column(1).footer()).html(
                    ' ' + totalMesas.column(1, {
                        page: 'current'
                    }).data().sum()
                )

                /*
                var totalNeto = this.api();
                var totalN = totalNeto.column(2, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalN.toFixed(0));
                $(totalNeto.column(2).footer()).html(
                    '$' + totalFormatted
                );

                var totalIva = this.api();
                var totalI = totalIva.column(3, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalI.toFixed(0));
                $(totalIva.column(3).footer()).html(
                    '$' + totalFormatted
                );*/

                var totalVentas = this.api();
                var totalV = totalVentas.column(2, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalV.toFixed(0));
                $(totalVentas.column(2).footer()).html(
                    '$' + totalFormatted
                );

                var totalPropinas = this.api();
                var totalP = totalPropinas.column(3, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalP.toFixed(0));
                $(totalPropinas.column(3).footer()).html(
                    '$' + totalFormatted
                );

                msgTotales = this.api();
                $(msgTotales.column(0).footer()).html(
                    'Totales'
                )


            },
            /*
            "footerCallback": function(row, data, start, end, display) {
                totalVentas = this.api()
                    .column(4) //numero de columna a sumar
                    .data()
                    .reduce(function(a, b) {
                        return parseInt(a) + parseInt(b);
                    }, 0);
                totalPropinas = this.api()
                    .column(5) //numero de columna a sumar
                    .data()
                    .reduce(function(a, b) {
                        return parseInt(a) + parseInt(b);
                    }, 0);
                if (totalVentas > 0) {
                    $('#totalVentasGarzon').show();
                    $('#totalPropinasGarzon').show();
                } else {
                    $('#totalVentasGarzon').hide();
                    $('#totalPropinasGarzon').hide();
                }
                $('#totalVentasGarzon').text("TOTAL VENTAS: $" + new Intl.NumberFormat().format(totalVentas));
                $('#totalPropinasGarzon').text("TOTAL PROPINAS: $" + new Intl.NumberFormat().format(totalPropinas));
            },*/
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
                    columns: [0, 1, 2, 3, 4, 5]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Detalle De Atencion De Garzones',
                className: "btn btn-danger"
            }]
        });
    }

    function llenarCboCajas(desde, hasta) {
        var fecha = $('#fechaCaja').val();
        $.post(base_url + "index.php/Dashboard/cajasAbiertas", {
            //'fecha': fecha
            'desde': desde,
            'hasta': hasta
        }, function (datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            if (obj.length <= 0) {
                // $('#divBodegas').hide();
                $('#divCaja1').hide();
                $('#divCaja2').hide();
            } else if (obj.length == 1) {
                $('#divCaja2').hide();
                $('#divCaja1').show();
            } else if (obj.length >= 2) {
                $('#divCaja1').show();
                $('#divCaja2').show();
            }
            if (obj.length <= 0) {
                $("#caja").text("Sin cajas abiertas");
            } else {
                $('#caja').text('Reporte De Ventas De Las Cajas Del ' + obj[0].fecha);
            }
            tbl = "";
            tbl = '<option selected value="0">Todas Las Cajas Del Día</option>';
            $.each(obj, function (index, v) { // recorro el array
                tbl = tbl + '<option value="' + v.id_caja + '">Caja N°: ' + v.id_caja + ' - Usuario: ' + v.nombre_usuario + ' - Fecha: ' + v.fecha + '</option>';
            }); //fin each
            $("#cboCajas").html(tbl);
            $("#cboCajas").select2();
        });
    }


    function PDFDetalleCaja() {
        //window.open(base_url+"index.php/Dashboard/verPDFDetalleCaja/");
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        $.redirect(base_url + 'index.php/Dashboard/PDFDetalleCaja', {
            'desde': desde,
            'hasta': hasta,

        }, 'POST', 'wDteD');
    }

    //Detalle De Ingresos
    function AbrirModalDetalleDeIngresosDeCaja(id_caja, desde, hasta) {
        resumenIngresosDetalleCaja(id_caja, desde, hasta);
        $('#modalDetalleIngresos').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function resumenIngresosDetalleCaja(id_caja, desde, hasta) {
        var id_caja = $('#cboCajas').find('option:selected').attr('value');
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        $.post(base_url + "index.php/Dashboard/obtenerDetalleDeIngresos", {
            'id_caja': id_caja,
            'desde': desde,
            'hasta': hasta,
        },
            function (data) {
                var obj = jQuery.parseJSON(data);
                tbl = "";
                var totalIngresos = 0;
                $.each(obj, function (index, v) {
                    tbl += "<tr>";
                    tbl += "<td class='text-right' style= 'height:10px'>" + v.numero_ingreso + "</td>";
                    tbl += "<td class='text-left' style= 'height:10px'>" + v.descripcion_corta + "</td>";
                    tbl += "<td class='text-right' style= 'height:10px'>$" + commaSeparateNumber(v.ingreso) + "</td>";
                    tbl += "<td class='text-center' style= 'height:10px'>" + v.fecha_hora_ingreso + "</td>";
                    tbl += "<td class='text-left' style= 'height:10px'>" + v.observacion + "</td>";
                    tbl += "<td class='text-left' style= 'height:10px'>" + v.nombre_ccosto + "</td>";
                    tbl += "</tr>";
                    totalIngresos += parseInt(v.ingreso);
                });
                if (totalIngresos > 0) {
                    tbl += "<tr><td colspan='2' class='text text-center'><h4><b>TOTALES</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(totalIngresos) + "</b></h4></td></tr>";
                }
                $('#resumenDetalleIngresoDeCaja').html(tbl);
            })
    }
    //Detalle De Retiro
    function AbrirModalDetalleRetiroDeCaja(id_caja, desde, hasta) {
        resumenRetirosDetalleCaja(id_caja, desde, hasta);
        $('#modalDetalleRetiros').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function resumenRetirosDetalleCaja(id_caja, desde, hasta) {
        var id_caja = $('#cboCajas').find('option:selected').attr('value');
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        $.post(base_url + "index.php/Dashboard/obtenerDetalleDeRetiros", {
            'id_caja': id_caja,
            'desde': desde,
            'hasta': hasta,
        },
            function (data) {
                var obj = jQuery.parseJSON(data);
                tbl = "";
                var totalRetiro = 0;
                $.each(obj, function (index, v) {
                    tbl += "<tr>";
                    tbl += "<td class='text-right' style= 'height:10px'>" + v.numero_ingreso + "</td>";
                    tbl += "<td class='text-left' style= 'height:10px'>" + v.descripcion_corta + "</td>";
                    tbl += "<td class='text-right' style= 'height:10px'>$" + commaSeparateNumber(v.egreso) + "</td>";
                    tbl += "<td class='text-center' style= 'height:10px'>" + v.fecha_hora_retiro + "</td>";
                    tbl += "<td class='text-left' style= 'height:10px'>" + v.observacion + "</td>";
                    tbl += "<td class='text-left' style= 'height:10px'>" + v.nombre_ccosto + "</td>";
                    tbl += "</tr>";
                    totalRetiro += parseInt(v.egreso);
                });
                if (totalRetiro > 0) {
                    tbl += "<tr><td colspan='2' class='text text-center'><h4><b>TOTALES</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(totalRetiro) + "</b></h4></td></tr>";
                }
                $('#resumenDetalleRetiroDeCaja').html(tbl);
            })
    }

    //Detalle De Descuentos
    function AbrirModalDetalleDescuentosDeCaja(id_caja, desde, hasta) {
        resumenDescuentosDeCaja(id_caja, desde, hasta);
        $('#modalDetalleDeDescuentos').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function resumenDescuentosDeCaja(id_caja, desde, hasta) {
        var id_caja = $('#cboCajas').find('option:selected').attr('value');
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        $.post(base_url + "index.php/Dashboard/obtenerDetalleDeDescuentos", {
            'id_caja': id_caja,
            'desde': desde,
            'hasta': hasta,
        },
            function (data) {
                var obj = jQuery.parseJSON(data);
                tbl = "";
                var totalDescuento = 0;
                var totalSinDescuento = 0;
                var totalConDescuento = 0;
                $.each(obj, function (index, v) {
                    tbl += "<tr>";
                    tbl += "<td class='text-right' style= 'height:10px'>" + v.id_venta + "</td>";
                    tbl += "<td class='text-left' style= 'height:10px'>" + v.nombre_cliente + "</td>";
                    tbl += "<td class='text-right' style= 'height:5px'>" + 'N° ' + v.folio_cotizacion + '   ' + 'MESA' + v.id_mesa + "</td>";
                    tbl += "<td class='text-right' style= 'height:7px'>$" + commaSeparateNumber(v.subtotal) + "</td>";
                    tbl += "<td class='text-right' style= 'height:5px'>" + v.por_descuento + '%' + "</td>";
                    tbl += "<td class='text-right' style= 'height:10px'>$" + commaSeparateNumber(v.monto_descuento) + "</td>";
                    tbl += "<td class='text-right' style= 'height:10px'>$" + commaSeparateNumber(v.total) + "</td>";
                    tbl += "</tr>";
                    totalDescuento += parseInt(v.monto_descuento);
                    totalSinDescuento += parseInt(v.subtotal);
                    totalConDescuento += parseInt(v.total);
                });
                if (totalDescuento > 0) {
                    tbl += "<tr><td colspan='5' class='text text-center'><h4><b>TOTALES</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(totalDescuento) + "</b></h4></td></tr>";
                    // tbl += "<tr><td colspan='3' class='text text-left'><h4><b>TOTALES</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(totalSinDescuento) + "</b></h4></td><td  class='text text-right'><h4><b>$" + commaSeparateNumber(totalDescuento) + "</b></h4></td><td class='text text-right'><h4><b>$" + commaSeparateNumber(totalConDescuento) + "</b></h4></td></tr>";

                }
                $('#resumenDetalleDescuentos').html(tbl);
            })
    }

    /*
    function cargarValidacionReporteDetalleDeAtencion() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                    tableDetalleAtencion.column(2).visible(true);
                    tableDetalleAtencion.column(3).visible(true);
                } else {
                    tableDetalleAtencion.column(2).visible(false);
                    tableDetalleAtencion.column(3).visible(false);

                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    function cargarValidacionReporteVentasPorMesas() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                    tableVentasPorMesa.column(2).visible(true);
                    tableVentasPorMesa.column(3).visible(true);
                } else {
                    tableVentasPorMesa.column(2).visible(false);
                    tableVentasPorMesa.column(3).visible(false);

                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    function cargarValidacionReporteVentasPorFamilias() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                    tableVentasPorFamilia.column(3).visible(true);
                    tableVentasPorFamilia.column(4).visible(true);
                } else {
                    tableVentasPorFamilia.column(3).visible(false);
                    tableVentasPorFamilia.column(4).visible(false);

                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    function cargarValidacionReporteDetalleVentasPorFamilias() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                    tableVentasPorProducto.column(2).visible(true);
                    tableVentasPorProducto.column(3).visible(true);
                } else {
                    tableVentasPorProducto.column(2).visible(false);
                    tableVentasPorProducto.column(3).visible(false);

                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    function cargarValidacionReporteVentasPorPagar() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                    tableVentasPorPagar.column(7).visible(true);
                    tableVentasPorPagar.column(8).visible(true);
                } else {
                    tableVentasPorPagar.column(7).visible(false);
                    tableVentasPorPagar.column(8).visible(false);

                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    function cargarValidacionReporteDetalleVentasPorPagar() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                    tableDetallePedido.column(3).visible(true);
                    tableDetallePedido.column(4).visible(true);
                } else {
                    tableDetallePedido.column(3).visible(false);
                    tableDetallePedido.column(4).visible(false);

                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    function cargarValidacionReporteFormasDePago() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                    tablaFormaDePago.tbl(2).visible(true);
                    tablaFormaDePago.column(3).visible(true);
                } else {
                    tablaFormaDePago.column(2).visible(false);
                    tablaFormaDePago.column(3).visible(false);

                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    $('#btnTest').click(function () {
        cargarValidacionReporteDetalleDeAtencion();
        cargarValidacionReporteVentasPorMesas();
        cargarValidacionReporteVentasPorFamilias();
        cargarValidacionReporteVentasPorPagar();
        //cargarValidacionReporteResumenPorTipoDeDocumento();
        //cargarValidacionReporteResumenPorFormaDePago();
        //cargarValidacionReporteResumenCortesias();
    })
*/
    //Detalle De Propinas
    function AbrirModalDetallePropinasDeCaja(id_caja, desde, hasta, id_usuario) {
        var id_caja = $('#cboCajas').find('option:selected').attr('value');
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        var id_usuario = $('#cboUsuarios').find('option:selected').attr('value');
        DetalleDePropinas(id_caja, desde, hasta, id_usuario);
        llenarCboUsuarios();
        $('#ckTodosLosUsuarios').prop('checked', true);
        $("#cboUsuarios").attr("disabled", true);
        $('#modalDetalleDePropinas').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    $('#ckTodosLosUsuarios').click(function () {
        if ($('#ckTodosLosUsuarios').prop('checked') == false) {
            $("#cboUsuarios").attr("disabled", false);
            llenarCboUsuarios();

        } else {
            $("#cboUsuarios").attr("disabled", true);
        }
    });

    $('#btnFiltrarPropinasPorUsuario').on('click', function () {
        var id_caja = $('#cboCajas').find('option:selected').attr('value');
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('YYYY-MM-DD');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('YYYY-MM-DD');
        var id_usuario = $("#cboUsuarios").val();
        if ($('#ckTodosLosUsuarios').prop('checked') == false) {
            if (id_usuario == null || id_usuario == 0) {
                alert("Debe seleccionar el usuario");
                return;
            }
        } else {
            id_usuario = 0;
        }
        DetalleDePropinas(id_caja, desde, hasta, id_usuario);

    });

    function DetalleDePropinas(id_caja, desde, hasta, id_usuario) {
        let tituloExport = "Detalle De Propinas";
        if ($.fn.dataTable.isDataTable('#tableDetallePropinas')) {
            tableDetalleDePropinas = $('#tableDetallePropinas').DataTable();
            tableDetalleDePropinas.destroy();
        }
        tableDetalleDePropinas = $("#tableDetallePropinas").DataTable({
            "ajax": {
                "url": base_url + "index.php/Dashboard/obtenerDetalleDePropinas",
                "type": "POST",
                "data": {
                    'id_caja': id_caja,
                    'desde': desde,
                    'hasta': hasta,
                    'id_usuario': id_usuario,

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
            "columns": [{
                mData: "id_usuario_garzon",
                className: "text-center",
                visible: false,
            },
            {
                mData: "nombre_usuario",
                className: "text-center"
            },
            {
                mData: "nombre_fpago",
                className: "text-center"
            },
            {
                mData: "total_propina",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            }
            ],
            "drawCallback": function () {
                totalPropina = this.api();
                $(totalPropina.column(3).footer()).html(
                    ' ' + totalPropina.column(3, {
                        page: 'current'
                    }).data().sum()
                )
                msgTotales = this.api();
                $(msgTotales.column(0).footer()).html(
                    'Totales'
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
                    columns: [0, 1, 2, 3, 4]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: tituloExport,
                className: "btn btn-danger"
            }]
        });
    }

    function llenarCboUsuarios() {
        $.post(base_url + "index.php/DetalleOperaciones/ListarUsuarios", {
            id: ""
        }, function (datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = "<option value='0' disabled>Seleccione El Usuario</option>";
            $.each(obj, function (index, v) { // recorro el array
                tbl += '<option selected value="' + v.id_usuario + '">' + v.nombre_usuario + '</option>';
            }); //fin each
            $("#cboUsuarios").html(tbl);
            $("#cboUsuarios").select2();
        });
    }



    // $("#btnDetalleMesas").on("click", function(id_cotizacion) {
    // 	detalleMesas(id_cotizacion);
    // })

    // $("#btnDetalleFamilia").on("click", function(e, dt, type, indexes) {
    // 	// var id_caja = $('#cboCajas').find('option:selected').attr('value');
    // 	// var id_familia = rowData[0].id_familia;
    // 	// var fecha = $('#fechaCaja').val();
    // 	// ventasPorProductos(id_caja, id_familia, fecha);
    // 	$("#modalProductos").modal({
    // 		backdrop: 'static',
    // 		keyboard: false,
    // 	});
    // });
</script>