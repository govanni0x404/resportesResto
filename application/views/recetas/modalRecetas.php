<?php $this->load->view('recetas/modalRecetaHtml.php'); /* v1.39.6: la ventana compartida con la Carta */ ?>
 <?php $this->load->view('umedida/jsNuevaUmedida.php'); ?>
 <?php $this->load->view('recetas/jsUnidadesReceta.php'); /* v1.34.0 */ ?>

<script>
	var tableInsumoReceta = $("#tableInsumoReceta").on("draw.dt", function() {
		$(this).find(".dataTables_empty").parents('tbody').empty();
	}).DataTable({
		"dom": "t"
	});

	$("#tab1").on("click", function() {
		$("#tabPreparacionReceta").hide();
		$("#tabDetalleInsumosReceta").show();
	});

	$("#tab2").on("click", function() {
		$("#tabDetalleInsumosReceta").hide();
		$("#tabPreparacionReceta").show();
	});

	$("#btnNuevaReceta").on("click", function() {
		tableInsumoReceta.clear().draw();
		
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

		$("#idReceta").val("");
		$("#nombreReceta").val("");
		$("#rendimientoReceta").val("");
		$("#brutoSugerido").val("");
		$('#esSubreceta').val("");
		$('#idInsumoReceta').val("");
		$("#nombreInsumoReceta").val("");
		$('#cantidadInsumoReceta').val("");
		$('#unMedidaInsumoReceta').val("");
		$('#costoInsumoReceta').val("");
		$('#costoTotalInsumoReceta').val("");
		$('#cBarraI').val("");
		$("#preparacionReceta").val("");
		llenarCboInsumos(0);
		llenarCboUnMedida(0);
		llenarFamiliaReceta(0);
		llenarSeccionReceta(0);
		$('#modalReceta').modal({
			backdrop: 'static',
			keyboard: false
		});
	});

	$("#cboCodBarraInsumoReceta").on('change', function(e) {
		var cBarra = $('#cboCodBarraInsumoReceta').find('option:selected').attr('cbarra');
		var id_insumo = $('#cboCodBarraInsumoReceta').find('option:selected').attr('value');
		var nombre_insumo = $('#cboCodBarraInsumoReceta').find('option:selected').attr('nombreInsumo');
		var esreceta = $('#cboCodBarraInsumoReceta').find('option:selected').attr('esReceta');
		var nombre_umedida = $('#cboCodBarraInsumoReceta').find('option:selected').attr('umedida');
		var costo_neto_ppp = $('#cboCodBarraInsumoReceta').find('option:selected').attr('costoPP');
		if (esreceta == 't') {
			esreceta = 'SI'
		} else {
			esreceta = 'NO'
		}
		if ($('#cboCodBarraInsumoReceta').val() == 0) {
			$("#nombreInsumoReceta").val("");
			$('#esSubreceta').val("");
			$('#idInsumoReceta').val("");
			$('#cantidadInsumoReceta').val("");
			$('#unMedidaInsumoReceta').val("");
			$('#costoInsumoReceta').val("");
			$('#costoTotalInsumoReceta').val("");
			$('#cBarraI').val("");
		} else {
			$('#cantidadInsumoReceta').val("");
			$('#costoTotalInsumoReceta').val("");
			$("#nombreInsumoReceta").val(nombre_insumo);
			$('#esSubreceta').val(esreceta);
			$('#idInsumoReceta').val(id_insumo);
			$('#unMedidaInsumoReceta').val(nombre_umedida);
			$('#costoInsumoReceta').val(recDosDecimales(costo_neto_ppp));   // v1.37.13: 0.50 (con su cero)
			$('#cBarraI').val(cBarra);
		}
		recPintarUnidades();   // v1.34.0
	});

	$("#btnAgregarInsumoTabla").on('click', function() {
		agregarInsumoReceta();
	});

// REEMPLAZA LA SECCIÓN $("#btnGrabarInsumo").on("click", function() { ... });

	$("#btnGrabarInsumo").on("click", function() {
		var id_receta = $('#idReceta').val();
		var nombre_receta = $('#nombreReceta').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		var rendimiento = $('#rendimientoReceta').val();
		var precio_venta = $('#brutoSugerido').val();
		var id_umedida = $('#cboUnMedida').val();
		var id_familia_receta = $('#cboGrupoReceta').val();
		var id_seccion = $('#cboSeccionReceta').val();
		var preparacion = $('#preparacionReceta').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		var msg = "";

		// Validaciones
		if ($('#nombreReceta').val() == 0) {
			showAppAlert('error', "Falta Ingresar el Nombre de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#rendimientoReceta').val() == 0) {
			showAppAlert('error', "Falta Ingresar el Rendimiento de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboUnMedida').val() == 0) {
			showAppAlert('error', "Falta Ingresar la Unidad de Medida de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#brutoSugerido').val() == 0) {
			showAppAlert('error', "Falta Ingresar el Precio Bruto de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboGrupoReceta').val() == 0) {
			showAppAlert('error', "Falta Seleccionar el Grupo/Familia de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboSeccionReceta').val() == 0) {
			showAppAlert('error', "Falta Seleccionar la Seccion de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		// Preparar JSON de la receta
		var miJson = {
			'id_receta': id_receta,
			'nombre_receta': nombre_receta,
			'rendimiento': rendimiento,
			'precio_venta': precio_venta,
			'id_umedida': id_umedida,
			'id_familia_receta': id_familia_receta,
			'id_seccion': id_seccion,
			'preparacion': preparacion
		};

		// Preparar detalles de la receta
		var detalleReceta = [];
		$("#tableInsumoReceta tbody tr").each(function(index) {
			var id_detalle_receta = 0;
			var id_insumo = 0;
			var es_receta = '';
			var cantidad = 0;
			var costo_pp = 0;
			var costo_total = 0;

			$(this).children("td").each(function(index2) {
				switch (index2) {
					case 0: // id detalle
						id_detalle_receta = $(this).html();
						break;
					case 1: // id insumo
						id_insumo = $(this).html();
						break;
					case 4: // es receta
						es_receta = $(this).html();
						break;
					case 5: // cantidad
						var row = $(this);
						if (row.find('input[type="text"]').val()) {
							cantidad = String(recNumero(row.find('input[type="text"]').val()));   // v1.39.6: "0,5" = medio
						}
						break;
					case 7: // precio un
						costo_pp = $(this).attr('data-costo') !== undefined ? $(this).attr('data-costo') : String(recNumero($(this).html()));
						break;
					case 8: // total
						costo_total = String(recNumero($(this).html()));
						break;
				}
			});

			// Agregar al array solo si tiene datos válidos
			if (id_insumo > 0) {
				detalleReceta.push({
					'id_detalle_receta': id_detalle_receta,
					'id_receta': id_receta,
					'id_insumo': id_insumo,
					'cantidad': cantidad,
					'costo_pp': costo_pp,
					'costo_total': costo_total,
					'es_receta': es_receta
				});
			}
		});

		console.log('Datos a enviar:', {receta: miJson, detalles: detalleReceta});

		// Enviar datos al servidor
		$.ajax({
			url: base_url + "index.php/Recetas/grabarReceta",
			type: 'POST',
			data: {
				'prod': miJson,
				'detalleReceta': detalleReceta
			},
			dataType: 'json',
			success: function(response) {
				console.log('Respuesta del servidor:', response);
				
				if (response.status === 'success') {
					recMensajeGrabada($.trim($('#nombreReceta').val()));   // v1.37.16: confirmación al medio con el resumen
					// Cerrar el modal
					$('#modalReceta').modal('hide');
					
					// Recargar la tabla
					tableRecetas.ajax.reload(null, false);
					
				} else {
					showAppAlert('error', response.message || "Error al guardar la receta", {
                        duration: 6000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
				}
			},
			error: function(xhr, status, error) {
				console.error('Error AJAX:', {xhr: xhr, status: status, error: error});
				showAppAlert('error', "Se produjo un error al realizar el proceso. Detalle: " + error, {
                    duration: 6000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
			}
		});
	});

	function agregarInsumoReceta() {
		var id_detalle_receta = 0;
		var cbarra = $('#cBarraI').val();
		var id_insumo = $('#idInsumoReceta').val();
		var nombre_insumo = $('#nombreInsumoReceta').val();
		// v1.34.0: el campo SUBRECETA está escondido en esta pantalla (salía "undefined" en la tabla)
		var subreceta = $('#esSubreceta').length ? $('#esSubreceta').val() : ($('#cboCodBarraInsumoReceta option:selected').attr('esReceta') === 't' ? 'SI' : 'NO');
		// v1.34.0: la cantidad se guarda en la unidad de receta del insumo (si se escribió en kilos, se pasa a gramos)
		var cantidad_insumo = $.trim($('#cantidadInsumoReceta').val()) === '' ? '' : recCantidadEnReceta();
		var uMedida = $('#unMedidaInsumoReceta').val();
		var costoInsumo = recCostoElegido();   // v1.37.12: con todos sus decimales
		var costoTotal = 0; // se calculará en la tabla
		var errorexiste = 0;
		//RECORRE LA TABLA EN BUSCA DEL PRODUCTO EN CASO DE QUE YA ESTE, SI ESTA NO DEJA AGREGAR
		$("#tableInsumoReceta tbody tr").each(function(index) {
			$(this).children("td").each(function(index2) {
				switch (index2) {
					case 1:
						var idInsumo = $(this).html();
						if (idInsumo == id_insumo) {
							errorexiste = 1;
						}
						break;
				}
			});
		});
		//COMPROBACIONES
		if (id_insumo == 0) {
			showAppAlert('error', "Elija un insumo por favor", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
		} else if (cantidad_insumo === null) {
			showAppAlert('error', "No se puede convertir esa unidad a la del insumo", {
				duration: 6000,
				image: base_url + 'assets/img/errorGif.gif'
			});
		} else if (cantidad_insumo === '' || !(cantidad_insumo > 0)) {
			showAppAlert('error', "Ingrese una cantidad", {
            	duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
		} else if (errorexiste == 1) {
			showAppAlert('error', "El producto ya se encuentra en la lista", {
            	duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
		} else {
			rnd = $("#contador").val();
			$('#cboCodBarraInsumoReceta').val(0).trigger('change');
			$("#nombreInsumoReceta").val("");
			$('#esSubreceta').val("");
			$('#idInsumoReceta').val("");
			$('#cantidadInsumoReceta').val("");
			$('#unMedidaInsumoReceta').val("");
			$('#costoInsumoReceta').val("");
			$('#costoTotalInsumoReceta').val("");
			$('#cBarraI').val("");
			addToTable(rnd, id_detalle_receta, id_insumo, cbarra, nombre_insumo, subreceta, cantidad_insumo, uMedida, costoInsumo, costoTotal);
		}
	}

	function addToTable(rnd, id_detalle_receta, id_insumo, cbarra, nombre_insumo, subreceta, cantidad_insumo, uMedida, costoInsumo, costoTotal) {
		var tbl;
		tbl = "<tr id='" + rnd + "' style='border-width:10px;'>";
		tbl = tbl + "<td style='display:none;' vertical-align:middle;'>" + id_detalle_receta + "</td>";
		tbl = tbl + "<td style='display:none;' vertical-align:middle;'>" + id_insumo + "</td>";
		tbl = tbl + "<td class='text-left'>" + cbarra + "</td>";
		tbl = tbl + "<td class='text-left'>" + nombre_insumo + "</td>";
		tbl = tbl + "<td class='text-left'>" + subreceta + "</td>";
		tbl = tbl + "<td style='vertical-align:middle; height:50px;' class='text-left'> <a  onclick='aumentarCantidad(" + rnd + ")' href='#'  class='btn_cantidad'><i class='fa fa-plus-square' style='font-size: 18px;'></i></a>   <input style='width:70px; margin-right:4px' class='text-right' onpaste='return false;' ondrop='return false;' autocomplete='off' onkeyup='actualizarCantidadTXT(" + rnd + ")' onblur='calcularDatos(" + rnd + ", true)'  id='txt" + rnd + "' type='text' pattern='\d*' maxlength='10' style='width: 100px;  margin: 10px;' class='input-sm' name='' onkeypress='validate(event)' value='" + cantidad_insumo + "'><a  onclick='disminuirCantidad(" + rnd + ")' href='#' class='btn_cantidad'><i class='fa fa-minus-square' style='font-size: 18px;'></i></a></td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-left'>" + uMedida + "</td>";
		// v1.37.12: el costo se ve con 2 decimales y el exacto queda en data-costo para el cálculo
		tbl = tbl + "<td style='vertical-align:middle;' class='text-right' data-costo='" + (Number(costoInsumo) || 0) + "'>" + recDosDecimales(costoInsumo) + "</td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-right montoTotal'>" + costoTotal + "</td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-center'><a  onclick='removeRow(" + rnd + ")' href='#' class='btn btn-danger btn-icon'><i class='fa fa-trash-o'></i></a></td>";
		tbl = tbl + "</tr>";
		$('#tableInsumoReceta').append(tbl);
		$("#contador").val(parseInt(rnd + 1));
		calcularDatos(rnd);
	}

	function llenarCboUnMedida(id_umedida) {
		$.post(base_url + "index.php/Recetas/llenarCboUnMedida", {
			'id_umedida': id_umedida,
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			tbl = "<option selected value = 0>Seleccione La Unidad De Medida</option>";
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_umedida == id_umedida || v.nombre_umedida == id_umedida) {
					tbl = tbl + '<option selected value="' + v.id_umedida + '">' + v.nombre_umedida + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_umedida + '">' + v.nombre_umedida + '</option>';
				}
			}); //fin each

			$("#cboUnMedida").html(tbl);
			$("#cboUnMedida").select2();
		});
	}

	function llenarFamiliaReceta(id_familia_receta) {
		$.post(base_url + "index.php/Recetas/llenarFamiliaReceta", {
			'id_familia_receta': id_familia_receta,
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			tbl = "<option selected value = 0>Seleccione La Familia Receta</option>";
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_familia_receta == id_familia_receta) {
					tbl = tbl + '<option selected value="' + v.id_familia_receta + '">' + v.nombre_familia_receta + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_familia_receta + '">' + v.nombre_familia_receta + '</option>';
				}
			}); //fin each

			$("#cboGrupoReceta").html(tbl);
        // v1.39.9 (pedido del usuario): en una receta nueva se elige GENERAL por defecto
        if (!Number(id_familia_receta)) {
            var $gen = $('#cboGrupoReceta option').filter(function () { return $.trim($(this).text()).toUpperCase() === 'GENERAL'; }).first();
            if ($gen.length) $('#cboGrupoReceta').val($gen.val()).trigger('change.select2');
        }
			$("#cboGrupoReceta").select2();
		});
	}

	function llenarSeccionReceta(id_seccion) {
		$.post(base_url + "index.php/Recetas/llenarSeccionReceta", {
			'id_seccion': id_seccion,
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			tbl = "<option selected value = 0>Seleccione La Seccion</option>";
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_seccion == id_seccion) {
					tbl = tbl + '<option selected value="' + v.id_seccion + '">' + v.nombre_seccion + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_seccion + '">' + v.nombre_seccion + '</option>';
				}
			}); //fin each

			$("#cboSeccionReceta").html(tbl);
        // v1.39.9 (pedido del usuario): en una receta nueva se elige GENERAL por defecto
        if (!Number(id_seccion)) {
            var $gen = $('#cboSeccionReceta option').filter(function () { return $.trim($(this).text()).toUpperCase() === 'GENERAL'; }).first();
            if ($gen.length) $('#cboSeccionReceta').val($gen.val()).trigger('change.select2');
        }
			$("#cboSeccionReceta").select2();
		});
	}

	function llenarCboInsumos(id_insumo) {
		$.post(base_url + "index.php/Recetas/llenarCboInsumos", {
			'id_insumo': id_insumo,
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			tbl = "<option selected value = 0>Seleccione El Insumo</option>";
			$.each(obj, function(index, v) { tbl = tbl + recOpcionInsumo(v, v.id_insumo == id_insumo); });   // v1.34.0 //fin each

			$("#cboCodBarraInsumoReceta").html(tbl);
			$("#cboCodBarraInsumoReceta").select2();
		});
	}

	// Función para manejar la selección del insumo en el combobox
	$("#cboCodBarraInsumoReceta").on('change', function() {
		// Obtener la opción seleccionada
		var selectedOption = $(this).find('option:selected');
		
		// Obtener los datos de los atributos personalizados (data attributes)
		var id_insumo = selectedOption.val();
		var cbarra = selectedOption.attr('cbarra');
		var nombre_insumo = selectedOption.attr('nombreInsumo');
		var es_receta = selectedOption.attr('esReceta');
		var umedida = selectedOption.attr('umedida');
		var costoPP = selectedOption.attr('costoPP');

		// Asignar los valores a los campos de texto ocultos/visibles del modal
		if (id_insumo != 0) {
			$("#idInsumoReceta").val(id_insumo);
			$("#cBarraI").val(cbarra);
			$("#nombreInsumoReceta").val(nombre_insumo);
			$("#esSubreceta").val(es_receta === 't' ? 'SI' : 'NO'); // Asumiendo 't' es true
			$("#unMedidaInsumoReceta").val(umedida);
			$("#costoInsumoReceta").val(recDosDecimales(costoPP));   // v1.37.13: 0.50 (con su cero)
			
			// Inicializar cantidad y calcular el costo total
			$("#cantidadInsumoReceta").val(1); // Puedes poner 1 como cantidad inicial
			
			// Disparar el cálculo inicial
			$("#cantidadInsumoReceta").trigger('keyup'); 
			
		} else {
			// Limpiar campos si selecciona la opción "Seleccione El Insumo"
			$("#idInsumoReceta").val("");
			$("#cBarraI").val("");
			$("#nombreInsumoReceta").val("");
			$("#esSubreceta").val("");
			$("#unMedidaInsumoReceta").val("");
			$("#costoInsumoReceta").val("");
			$("#cantidadInsumoReceta").val("");
			$("#costoTotalInsumoReceta").val("");
		}
	});

	function sololetrasyNumeros(e, espace) {
		key = e.keyCode || e.which;
		teclado = String.fromCharCode(key).toLowerCase();
		letras = "qwertyuiopasdfghjklñzxcvbnm1234567890 ";
		especiales = "8-37-38-46-164";
		teclado_especial = false;

		for (var i in especiales) {
			if (key == especiales[i]) {
				teclado_especial = true;
				break;
			}
		}

		if (espace == true) {
			if (e.keyCode == 32) {
				return false;
			}
		}

		if (letras.indexOf(teclado) == -1 && !teclado_especial) {
			return false;
		}
	}

	function soloNumeros(e, espace) {
		key = e.keyCode || e.which;
		teclado = String.fromCharCode(key).toLowerCase();
		letras = "1234567890";
		especiales = "8-37-38-46-164";
		teclado_especial = false;
		for (var i in especiales) {
			if (key == especiales[i]) {
				teclado_especial = true;
				break;
			}
		}
		if (espace == true) {
			if (e.keyCode == 32) {
				return false;
			}
		}
		if (letras.indexOf(teclado) == -1 && !teclado_especial) {
			return false;
		}
	}

	function soloNumerosDecimales(e, espace) {
		key = e.keyCode || e.which;
		teclado = String.fromCharCode(key).toLowerCase();
		letras = "1234567890.";
		especiales = "8-37-38-46-164";
		teclado_especial = false;
		for (var i in especiales) {
			if (key == especiales[i]) {
				teclado_especial = true;
				break;
			}
		}
		if (espace == true) {
			if (e.keyCode == 32) {
				return false;
			}
		}
		if (letras.indexOf(teclado) == -1 && !teclado_especial) {
			return false;
		}
	}

	function removeRow(rnd) {
		swal({
				title: "Esta seguro?",
				text: "Confirma que desea quitar el insumo de la lista?",
				type: "warning",
				showCancelButton: true,
				confirmButtonColor: "#C9372C",
				cancelButtonText: "Cancelar",
				confirmButtonText: "Eliminar",
				closeOnConfirm: true
			},
			function() {
				var cant_lineas = $('#cant_lineas').val();
				$('#' + rnd).remove();
				$('#cant_lineas').val(parseInt(cant_lineas) - 1);
				showAppAlert('success', "Insumo Eliminado Correctamente", {
                	duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
			});
	}

	function aumentarCantidad(rnd) {
		var cantidad = recNumero($('#txt' + rnd).val());   // v1.39.6
		if (isNaN(cantidad) || cantidad == null || cantidad == "") {
			$('#txt' + rnd).val(0.1);
			cantidad = 0.1;
		}
		cantidad = Math.round((cantidad + 1) * 1000) / 1000;
		$('#txt' + rnd).val(cantidad);
		calcularDatos(rnd);

	}

	function disminuirCantidad(rnd) {
		var cantidad = recNumero($('#txt' + rnd).val());   // v1.39.6
		if (isNaN(cantidad) || cantidad == null || cantidad == "") {
			$('#txt' + rnd).val(0.1);
			cantidad = 0.1;
		}
		cantidad = Math.round((cantidad - 1) * 1000) / 1000;
		if (cantidad < 0.1) {
			cantidad = 0.1;
		}
		$('#txt' + rnd).val(cantidad);
		calcularDatos(rnd);
	}

	function calcularDatos(rnd, alSalir) {
		var td7 = $('#' + rnd).children('td').eq(7), costoUn = td7.attr('data-costo') !== undefined ? Number(td7.attr('data-costo')) : recNumero(td7.html());   // v1.37.12
		// v1.39.6: mientras se escribe no se cambia lo escrito (antes, al borrar ponía 0.1 y "100" quedaba "0.1100");
		// "0,5" con coma vale medio. Vacío o inválido cuenta 0 en el total; al salir del campo queda en 0.1 como mínimo.
		var cantidad = recNumero($('#txt' + rnd).val());
		if (!(cantidad > 0)) {
			cantidad = 0;
			if (alSalir) { $('#txt' + rnd).val(0.1); cantidad = 0.1; }
		}
		var totalLinea = (Number(costoUn) || 0) * cantidad;
		$('#' + rnd).children('td')[8].innerHTML = recDosDecimales(totalLinea);   // v1.37.12: 2 decimales, como el servidor
	}

	function validate(evt) {
		var theEvent = evt || window.event;

		// Handle paste
		if (theEvent.type === 'paste') {
			key = event.clipboardData.getData('text/plain');
		} else {
			// Handle key press
			var key = theEvent.keyCode || theEvent.which;
			key = String.fromCharCode(key);
		}
	}

	function actualizarCantidadTXT(rnd) {
		calcularDatos(rnd);
	}
</script>
