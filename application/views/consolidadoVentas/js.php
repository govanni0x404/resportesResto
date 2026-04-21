<script>
	var base_url = "<?php echo base_url(); ?>";
	//tableVentasPorFamilia = $('#tableVentasPorFamilia').DataTable();
	$(document).ready(function() {

		//consultarResumenDeVentas();
		//cargarValidacionReporteResumenVentasPorFamilias();
		//cargarValidacionReporteResumenVentasPorCanal();
		/* resumenVentasPorFamilia( desde, hasta );
		resumenVentasPorCanalDeVentas();
		resumenDetalleConsolidadoVentas();
		resumenMontoTotalVentas();*/
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


	function consultarResumenDeVentas() {
		var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
		var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
		resumenVentasPorFamilia(desde, hasta);
		resumenDetalleConsolidadoVentas(desde, hasta);
		resumenMontoTotalVentas();
		resumenVentasPorCanalDeVentas(desde, hasta);
		cargarValidacionReporteResumenVentasPorFamilias();
		cargarValidacionReporteResumenVentasPorCanal();
		cargarValidacionOcultarTotales();
	}

	function commaSeparateNumber(val) {
		if (val > 0) {
			while (/(\d+)(\d{3})/.test(val.toString())) {
				val = val.toString().replace(/(\d+)(\d{3})/, '$1' + ',' + '$2');
			}
			return val;
		}
	}

	function resumenDetalleConsolidadoVentas(desde, hasta) {
		$.post(base_url + "index.php/ConsolidadoVentas/ResumenConsolidadoVentas", {
			'desde': desde,
			'hasta': hasta
		}, function(data) {
			var obj = jQuery.parseJSON(data);
			$.each(obj, function(index, v) {
				if (v.cantidad_ventas > 0) {
					$('#cantidadVentas').text(commaSeparateNumber(v.cantidad_ventas));
					// $('#cantidadVentas').counterUp({
					//     delay: 10,
					//     time: 2000
					// });
				} else {
					$('#cantidadVentas').text(0);
				}
				if (v.total_neto > 0) {
					$('#totalNeto').text(commaSeparateNumber(v.total_neto));
					// $('#totalNeto').counterUp({
					//     delay: 10,
					//     time: 2000
					// });
				} else {
					$('#totalNeto').text(0);
				}
				if (v.total_iva > 0) {
					$('#totalIva').text(commaSeparateNumber(v.total_iva));
					// $('#totalIva').counterUp({
					//     delay: 10,
					//     time: 2000
					// });
				} else {
					$('#totalIva').text(0);
				}
				if (v.total_descuento > 0) {
					$('#totalDescuentos').text(commaSeparateNumber(v.total_descuento));
					// $('#totalDescuentos').counterUp({
					//     delay: 10,
					//     time: 2000
					// });
				} else {
					$('#totalDescuentos').text(0);
				}
				if (v.total_ventas > 0) {
					$('#totalVentas').text(commaSeparateNumber(v.total_ventas));
					// $('#totalVentas').counterUp({
					//     delay: 10,
					//     time: 2000
					// });
				} else {
					$('#totalVentas').text(0);
				}
				if (v.total_general > 0) {
					$('#TotalGeneral').text(commaSeparateNumber(v.total_general));
					// $('#TotalGeneral').counterUp({
					//     delay: 10,
					//     time: 2000
					// });
				} else {
					$('#TotalGeneral').text(0);
				}
			});
		}).done(function(datos) {})
	}


	function resumenMontoTotalVentas() {
		$.post(base_url + "index.php/ConsolidadoVentas/MontoTotalventas", {}, function(data) {
			var obj = jQuery.parseJSON(data);
			$.each(obj, function(index, v) {
				if (v.monto_total_ventas > 0) {
					$('#MontoVentaTotal').text(commaSeparateNumber(v.monto_total_ventas));
					// $('#MontoVentaTotal').counterUp({
					//     delay: 10,
					//     time: 2000
					// });
				} else {
					$('#MontoVentaTotal').text(0);
				}

			});
		}).done(function(datos) {})
	}

	function resumenVentasPorFamilia(desde, hasta) {
		$.post(base_url + "index.php/ConsolidadoVentas/ResumenVentasPorFamilia", {
			'desde': desde,
			'hasta': hasta
		}, function(data) {
			var obj = jQuery.parseJSON(data);
			tbl = "";
			var CantidadVentas = 0;
			var totalNeto = 0;
			var totalIva = 0;
			var totalVentas = 0;
			$.each(obj, function(index, v) {
				tbl += "<tr>";
				tbl += "<td class='text-center' style= 'height:10px'>" + v.nombre_familia + "</td>";
				tbl += "<td class='text-center' style= 'height:10px'>" + commaSeparateNumber(v.cantidad) + "</td>";

				if (v.monto_total > 0) {
					tbl += "<td class='text-center ocultarNetoVentasPorFamilias' style= 'height:10px'>$" + commaSeparateNumber(v.monto_neto) + "</td>";
					tbl += "<td class='text-center ocultarIvaVentasPorFamilias' style= 'height:10px'>$" + commaSeparateNumber(v.monto_iva) + "</td>";
					tbl += "<td class='text-center' style= 'height:10px'>$" + commaSeparateNumber(v.monto_total) + "</td>";
				} else {
					tbl += "<td class='text-center' style= 'height:10px'>$0</td>";
					tbl += "<td class='text-center' style= 'height:10px'>$0</td>";
					tbl += "<td class='text-center' style= 'height:10px'>$0</td>";
				}

				tbl += "</tr>";
				CantidadVentas += parseInt(v.cantidad);
				totalNeto += parseInt(v.monto_neto);
				totalIva += parseInt(v.monto_iva);
				totalVentas += parseInt(v.monto_total);
			});
			if (totalVentas > 0) {
				tbl += "<tr><td colspan='2' class='text text-center'><h4><b>TOTAL</b></h4></td><td class='text text-center ocultarNetoVentasPorFamilias'><h4><b>$" + commaSeparateNumber(totalNeto) + "</b></h4></td><td class='text text-center ocultarIvaVentasPorFamilias'><h4><b>$" + commaSeparateNumber(totalIva) + "</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalVentas) + "</b></h4></td></tr>";
			}
			$('#resumenVentasPorFamilia').html(tbl);

		})
	}

	function cargarValidacionReporteResumenVentasPorFamilias() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {
        }, function (datos) {
            var obj = $.parseJSON(datos); 
            $.each(obj, function (index, v) { 
                if (v.desglozar_solo_monto_total_en_reportes == 't') {
                $('.ocultarNetoVentasPorFamilias').hide();
                $('.ocultarIvaVentasPorFamilias').hide();
                }
            });
        }).done(function () {
        })
                .fail(function () {
                    alert("Se produjo un error al realizar la consulta");
                })
                .always(function () {
                });
    }

	function cargarValidacionOcultarTotales() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {
        }, function (datos) {
            var obj = $.parseJSON(datos); 
            $.each(obj, function (index, v) { 
                if (v.desglozar_solo_monto_total_en_reportes == 't') {
				$('.ocultarTotalNeto').hide();
				$('.ocultarTotalIva').hide();
                }
            });
        }).done(function () {
        })
                .fail(function () {
                    alert("Se produjo un error al realizar la consulta");
                })
                .always(function () {
                });
    }


	 $('#btnConsultar').click(function() {
  	cargarValidacionReporteResumenVentasPorFamilias();
	cargarValidacionReporteResumenVentasPorCanal();
	cargarValidacionOcultarTotales();
    })


	function resumenVentasPorCanalDeVentas(desde, hasta) {
		$.post(base_url + "index.php/ConsolidadoVentas/ResumenVentasPorCanal", {
			'desde': desde,
			'hasta': hasta
		}, function(data) {
			var obj = jQuery.parseJSON(data);
			tbl = "";
			var CantidadVentas = 0;
			var totalNeto = 0;
			var totalIva = 0;
			var totalVentas = 0;
			$.each(obj, function(index, v) {
				tbl += "<tr>";
				tbl += "<td class='text-center' style= 'height:10px'>" + v.nombre_canal_venta + "</td>";
				tbl += "<td class='text-center' style= 'height:10px'>" + commaSeparateNumber(v.cantidad_ventas) + "</td>";
				tbl += "<td class='text-center ocultarNetoVentasPorCanal' style= 'height:10px'>$" + commaSeparateNumber(v.total_neto) + "</td>";
				tbl += "<td class='text-center ocultarIvaVentasPorCanal' style= 'height:10px'>$" + commaSeparateNumber(v.total_iva) + "</td>";
				tbl += "<td class='text-center' style= 'height:10px'>$" + commaSeparateNumber(v.total_ventas) + "</td>";
				tbl += "</tr>";
				CantidadVentas += parseInt(v.cantidad_ventas);
				totalNeto += parseInt(v.total_neto);
				totalIva += parseInt(v.total_iva);
				totalVentas += parseInt(v.total_ventas);
			});
			if (totalVentas > 0) {
				tbl += "<tr><td colspan='2' class='text text-center'><h4><b>TOTAL</b></h4></td><td class='text text-center ocultarNetoVentasPorCanal'><h4><b>$" + commaSeparateNumber(totalNeto) + "</b></h4></td><td class='text text-center ocultarIvaVentasPorCanal'><h4><b>$" + commaSeparateNumber(totalIva) + "</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalVentas) + "</b></h4></td></tr>";
			}
			$('#resumenVentasPorCanal').html(tbl);

		})
	}

	function cargarValidacionReporteResumenVentasPorCanal() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {
        }, function (datos) {
            var obj = $.parseJSON(datos); 
            $.each(obj, function (index, v) { 
                if (v.desglozar_solo_monto_total_en_reportes == 't') {
                $('.ocultarNetoVentasPorCanal').hide();
                $('.ocultarIvaVentasPorCanal').hide();
                }
            });
        }).done(function () {
        })
                .fail(function () {
                    alert("Se produjo un error al realizar la consulta");
                })
                .always(function () {
                });
    }

	function PDFConsolidadoDeVentas(id_caja) {
		var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
		var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
		$.redirect(base_url + 'index.php/ConsolidadoVentas/PDFConsolidadoDeVentas', {
			'desde': desde,
			'hasta': hasta,
		}, 'POST', 'wDteD');
	}

	function ExcelConsolidadoDeVentas() {
		var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
		var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');

		var html = "<table border='1'>";
		html += "<tr><td colspan='5' style='font-size:18px; font-weight:bold; text-align:center;'>CONSOLIDADO DE VENTAS</td></tr>";
		html += "<tr><td colspan='5' style='text-align:center;'>Desde: " + desde + " - Hasta: " + hasta + "</td></tr>";
		html += "<tr></tr>";

		html += "<tr><td colspan='2' style='font-weight:bold; background-color:#f2f2f2;'>RESUMEN GENERAL</td><td colspan='3'></td></tr>";
		html += "<tr><td>Cantidad Ventas</td><td>" + $('#cantidadVentas').text() + "</td><td colspan='3'></td></tr>";
		html += "<tr><td>Total Neto</td><td>" + $('#totalNeto').text() + "</td><td colspan='3'></td></tr>";
		html += "<tr><td>Total Iva</td><td>" + $('#totalIva').text() + "</td><td colspan='3'></td></tr>";
		html += "<tr><td>Total Ventas</td><td>" + $('#totalVentas').text() + "</td><td colspan='3'></td></tr>";
		html += "<tr><td>Total Descuentos</td><td>" + $('#totalDescuentos').text() + "</td><td colspan='3'></td></tr>";
		html += "<tr><td>Total General</td><td>" + $('#TotalGeneral').text() + "</td><td colspan='3'></td></tr>";
		html += "<tr><td>Monto Venta Total</td><td>" + $('#MontoVentaTotal').text() + "</td><td colspan='3'></td></tr>";
		html += "<tr></tr>";

		html += "<tr><td colspan='5' style='font-weight:bold; background-color:#f2f2f2;'>VENTAS POR FAMILIA</td></tr>";
		html += "<tr><th style='background-color:#dff0d8;'>Familia</th><th style='background-color:#dff0d8;'>Cantidad</th><th style='background-color:#dff0d8;'>Neto</th><th style='background-color:#dff0d8;'>Iva</th><th style='background-color:#dff0d8;'>Total</th></tr>";
		$('#resumenVentasPorFamilia tr').each(function() {
			html += "<tr>";
			$(this).find('td').each(function() {
				html += "<td>" + $(this).text() + "</td>";
			});
			html += "</tr>";
		});
		html += "<tr></tr>";

		html += "<tr><td colspan='5' style='font-weight:bold; background-color:#f2f2f2;'>VENTAS POR CANAL</td></tr>";
		html += "<tr><th style='background-color:#dff0d8;'>Canal</th><th style='background-color:#dff0d8;'>Cantidad</th><th style='background-color:#dff0d8;'>Neto</th><th style='background-color:#dff0d8;'>Iva</th><th style='background-color:#dff0d8;'>Total</th></tr>";
		$('#resumenVentasPorCanal tr').each(function() {
			html += "<tr>";
			$(this).find('td').each(function() {
				html += "<td>" + $(this).text() + "</td>";
			});
			html += "</tr>";
		});
		html += "</table>";

		var blob = new Blob(['\ufeff', html], {
			type: 'application/vnd.ms-excel'
		});
		var link = document.createElement("a");
		link.href = URL.createObjectURL(blob);
		link.download = "ConsolidadoVentas_" + desde + "_" + hasta + ".xls";
		link.click();
	}
</script>