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

	 function AbrirModalNuevoReceta() {
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
        llenarCboUnMedidaModalReceta();
        llenarFamiliaReceta(0);
        llenarSeccionReceta(0);
        llenarCboInsumos(0);
		$('#modalReceta').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

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
			$('#costoInsumoReceta').val(costo_neto_ppp);
			$('#cBarraI').val(cBarra);
		}
	});

	$("#cantidadInsumoReceta").on('keyup', function() {
		cantidad = $("#cantidadInsumoReceta").val();
		costo_unitario = $('#costoInsumoReceta').val();
		$('#costoTotalInsumoReceta').val(cantidad * costo_unitario);
	});

	$("#btnAgregarInsumoTabla").on('click', function() {
		agregarInsumoReceta();
	});

	$("#btnGrabarInsumo").on("click", function() {
		id_receta = $('#idReceta').val();
		nombre_receta = $('#nombreReceta').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		rendimiento = $('#rendimientoReceta').val();
		precio_venta = $('#brutoSugerido').val();
		id_umedida = $('#cboUnMedida').val();
		id_familia_receta = $('#cboGrupoReceta').val();
		id_seccion = $('#cboSeccionReceta').val();
		preparacion = $('#preparacionReceta').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		var msg = "";

		if ($('#nombreReceta').val() == 0) {
			msg = "-> Nombre Receta\n";
		}
		if ($('#rendimientoReceta').val() == 0) {
			msg = "-> Rendimiento Receta\n";
		}
		if ($('#brutoSugerido').val() == 0) {
			msg = "-> Precio Venta Sugerido\n";
		}
		if ($('#cboUnMedida').val() == 0) {
			msg = "-> Unidad de Medida\n";
		}
		if ($('#cboGrupoReceta').val() == 0) {
			msg = "-> Grupo Receta\n";
		}
		if ($('#cboSeccionReceta').val() == 0) {
			msg = "-> Seccion Receta\n";
		}
		if (msg.length) {
			showAppAlert('error',"- Falta Ingresar Los Siguiente(s) dato(s):\n" + msg, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else {
			//grabar datos

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
			var detalleReceta = [];
			var id_insumo = 0;

			$("#tableInsumoReceta tbody tr").each(function(index) {
				$(this).children("td").each(function(index2) {
					switch (index2) {
						case 0: //id producto
							id_detalle_receta = $(this).html();
							break;
						case 1: //id producto
							id_insumo = $(this).html();
							break;
						case 4: //es receta
							es_receta = $(this).html();
							break;
						case 5: // cantidad
							var row = $(this);
							if (row.find('input[type="text"]').val()) {
								cantidad = row.find('input[type="text"]').val().replace(/,./g, '');
							}
							break;
						case 7: //precio un
							costo_pp = $(this).html().replace(/,./g, '');
							break;
						case 8: //total
							costo_total = $(this).html().replace(/,./g, '');

							detalleReceta.push({
								'id_detalle_receta': id_detalle_receta,
								'id_receta': id_receta,
								'id_insumo': id_insumo,
								'es_receta': es_receta,
								'cantidad': cantidad,
								'costo_pp': costo_pp,
								'costo_total': costo_total,
							});
							break;
					}
				});
			});
			var id_receta = 0;
			$.post(base_url + "index.php/Recetas/grabarReceta", {
					'prod': miJson
				}, function() {}).done(function(datos) {
					var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
					$.each(obj, function(index, v) { // recorro el array
						id_receta = v.id_receta;
					});
					listaInsumos = "";
					listaDetalles = "";
					if (id_receta > 0) {
						try {
							$.post(base_url + "index.php/Recetas/grabarDetalleReceta", {
									'detalleReceta': detalleReceta,
								}, function() {}).done(function(datos) {
									var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
									$.each(obj, function(index, v) { // recorro el array
										id_insumos = v.id_insumo;
										id_detalle_recetas = v.id_detalle_receta

										if (listaDetalles.length > 0) {
											listaDetalles += ",";
										}
										listaDetalles += id_detalle_recetas;

										if (listaInsumos.length > 0) {
											listaInsumos += ",";
										}
										listaInsumos += id_insumos;
									});
									$.post(base_url + "index.php/Recetas/updateInsumoDelete", {
											'id_receta': id_receta,
											'detalleReceta': listaDetalles,
											'id_insumo': listaInsumos
										}, function() {}).done(function(datos) {
											$('#modalReceta').modal('hide');
											
											if (typeof ListadoDeReceta === 'function') {
												ListadoDeReceta(nombre_receta);
											}
											
											if (typeof tableRecetas !== 'undefined') {
												tableRecetas.ajax.reload();
											}
											
											showAppAlert('success', "Se registro Exitosamente La Receta!", {
                                    			duration: 4000,
                                    			image: base_url + 'assets/img/success.gif'
                                			});
										})
										.fail(function() {
											showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                                    			duration: 5000,
                                    			image: base_url+ 'assets/img/errorGif.gif'
                                			});
										})
								})
								.fail(function() {
									showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                                    	duration: 5000,
                                    	image: base_url+ 'assets/img/errorGif.gif'
                                	});
								})
						} catch (e) {
							let positionError = datos.search("ERROR:");
							showAppAlert('error',"- Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), {
                                	duration: 5000,
                                    image: base_url+ 'assets/img/errorGif.gif'
                                });
						} finally {}
					}
				})
				.fail(function() {
					showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
				})
		}
	});

	function agregarInsumoReceta() {
		var id_detalle_receta = 0;
		var cbarra = $('#cBarraI').val();
		var id_insumo = $('#idInsumoReceta').val();
		var nombre_insumo = $('#nombreInsumoReceta').val();
		var subreceta = $('#esSubreceta').val();
		var cantidad_insumo = $('#cantidadInsumoReceta').val();
		var uMedida = $('#unMedidaInsumoReceta').val();
		var costoInsumo = $('#costoInsumoReceta').val();
		var costoTotal = $('#costoTotalInsumoReceta').val();
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
			showAppAlert('error',"- Elija un insumo por favor", {
            	duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else if (cantidad_insumo == '') {
			showAppAlert('error',"- Ingrese una cantidad", {
            	duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else if (errorexiste == 1) {
			showAppAlert('error',"- El producto ya se encuentra en la lista", {
            	duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
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
		tbl = tbl + "<td style='vertical-align:middle; height:50px;' class='text-left'> <a  onclick='aumentarCantidad(" + rnd + ")' href='#'  class='btn_cantidad'><i class='fa fa-plus-square' style='font-size: 18px;'></i></a>   <input style='width:70px; margin-right:4px' class='text-right' onpaste='return false;' ondrop='return false;' autocomplete='off' onkeyup='actualizarCantidadTXT(" + rnd + ")'  id='txt" + rnd + "' type='text' pattern='\d*' maxlength='5' style='width: 100px;  margin: 10px;' class='input-sm' name='' onkeypress='validate(event)' value='" + cantidad_insumo + "'><a  onclick='disminuirCantidad(" + rnd + ")' href='#' class='btn_cantidad'><i class='fa fa-minus-square' style='font-size: 18px;'></i></a></td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-left'>" + uMedida + "</td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-right'>" + costoInsumo + "</td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-right montoTotal'>" + costoTotal + "</td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-center'><a  onclick='removeRow(" + rnd + ")' href='#' class='btn btn-danger btn-icon'><i class='fa fa-trash-o'></i></a></td>";
		tbl = tbl + "</tr>";
		$('#tableInsumoReceta').append(tbl);
		$("#contador").val(parseInt(rnd + 1));
		calcularDatos(rnd);
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
				closeOnConfirm: false
			},
			function() {
				var cant_lineas = $('#cant_lineas').val();
				$('#' + rnd).remove();
				$('#cant_lineas').val(parseInt(cant_lineas) - 1);
				swal("Eliminado!", "", "success");
			});
	}

	function aumentarCantidad(rnd) {
		var cantidad = parseFloat($('#txt' + rnd).val());
		if (isNaN(cantidad) || cantidad == null || cantidad == "") {
			$('#txt' + rnd).val(0.1);
			cantidad = 0.1;
		}
		cantidad++;
		$('#txt' + rnd).val(cantidad);
		calcularDatos(rnd);

	}

	function disminuirCantidad(rnd) {
		var cantidad = parseFloat($('#txt' + rnd).val());
		if (isNaN(cantidad) || cantidad == null || cantidad == "") {
			$('#txt' + rnd).val(0.1);
			cantidad = 0.1;
		}
		cantidad--;
		if (cantidad < 0.1) {
			cantidad = 0.1;
		}
		$('#txt' + rnd).val(cantidad);
		calcularDatos(rnd);
	}

	function calcularDatos(rnd) {
		var costoUn = parseFloat($('#' + rnd).children('td')[7].innerHTML.replace(/,./g, ''));
		var cantidad = parseFloat($('#txt' + rnd).val());

		if (isNaN(cantidad) || cantidad == null || cantidad == "" || parseFloat(cantidad) < 0) {
			$('#txt' + rnd).val(0.1); //monto minimo
			cantidad = 0.1;
		}
		var totalLinea = costoUn * cantidad;
		$('#' + rnd).children('td')[8].innerHTML = Math.round(totalLinea);
	}

	function actualizarCantidadTXT(rnd) {
		calcularDatos(rnd);
	}
</script>
