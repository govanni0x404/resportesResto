<script>
	function AbrirModalPrecioVenta(idProducto) {
		var IdProducto = $('#txtID').val();
		
		// Cargar precios Y COMISIONES existentes del producto
		cargarPreciosYComisionesParaEdicion(IdProducto);
		
		$('#modalPrecioVentaProducto').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function cargarPreciosYComisionesParaEdicion(idProducto) {
		$.post(base_url + "index.php/Productos/obtenerPreciosYComisiones", {
			id_producto: idProducto
		}, function(datos) {
			var obj = $.parseJSON(datos);
			var tbl = '';
			
			$.each(obj, function(index, v) {
				tbl += "<tr>";
				tbl += "<td style='display:none'>" + v.id_lprecio + "</td>";
				tbl += "<td style='width:30%'>" + v.nombre_lprecio + "</td>";
				
				// Precio
				tbl += '<td style="width:30%" class="text-right">';
				tbl += '<input class="text-right form-control precio-input" type="number" min="0" max="1000000" value="' + (v.total || 0) + '">';
				tbl += '</td>';
				
				// Comisión
				tbl += '<td style="width:30%" class="text-right">';
				tbl += '<input class="text-right form-control comision-input" type="number" min="0" max="100" step="0.01" value="' + (v.monto_comision || 0) + '" placeholder="0.00">';
				tbl += '</td>';
				
				tbl += "</tr>";
			});
			
			$("#detallePrecioVenta").html(tbl);
		});
	}

	function grabarPreciosYComisiones() {
		var idProducto = $("#txtID").val();
		var detallePrecios = [];
		var tienePrecio = false;

		if (!idProducto || idProducto === '0') {
        	showAppAlert('error', "Error: El ID del producto no está disponible. Por favor, seleccione un producto válido.", {
            	duration: 5000,
            	image: base_url + 'assets/img/errorGif.gif'
        	}); 
        	return; // Detener la ejecución si no hay ID
    	}

		$("#detallePrecioVenta tr").each(function() {
			var id_lprecio = $(this).find("td").eq(0).text().trim();
			var precio = parseInt($(this).find(".precio-input").val()) || 0;
			var comision = parseFloat($(this).find(".comision-input").val()) || 0;

			if (precio > 0) {
				tienePrecio = true;
				detallePrecios.push({
					id_lprecio: id_lprecio,
					total: precio,
					monto_comision: comision
				});
			}
		});

		if (!$('#ckPermitirVentaSiempre').is(':checked')) {
        	if (!tienePrecio) {
            	showAppAlert('error', "Debe ingresar al menos un precio mayor a cero", {
	                duration: 5000,
    	            image: base_url+ 'assets/img/errorGif.gif'
        	    }); 
            	return;
        	}
    	}

		console.log("Actualizando precios y comisiones:", detallePrecios);

		$.ajax({
			url: base_url + "index.php/Productos/actualizarPreciosYComisiones",
			type: "POST",
			data: { 
				id_producto: idProducto,
				precios: detallePrecios 
			},
			dataType: "json",
			success: function(response) {
				console.log("✓ Respuesta:", response);
				
				if (response.error) {
					showAppAlert('error', response.error, {
						duration: 5000,
						image: base_url + 'assets/img/errorGif.gif'
					});
					return;
				}
				
				$('#modalPrecioVentaProducto').modal('hide');
				
				showAppAlert('success', "Precios y comisiones actualizados correctamente!", {
					duration: 4000,
					image: base_url + 'assets/img/success.gif'
				});
				
				if (typeof tablaCartaProductos !== 'undefined') {
					tablaCartaProductos.ajax.reload();
				}
			},
			error: function(xhr, status, error) {
				console.error("✗ Error:", xhr.responseText);
				showAppAlert('error', "Error al actualizar: " + error, {
					duration: 5000,
					image: base_url + 'assets/img/errorGif.gif'
				});
			}
		});
	}

	function cancelarEdicionPrecios() {
		swal({
			title: "¿Está seguro?",
			text: "Si cancela, se perderán los cambios realizados",
			type: "warning",
			showCancelButton: true,
			confirmButtonText: "Sí, cancelar",
			cancelButtonText: "No, seguir editando",
			closeOnConfirm: true,
			closeOnCancel: true
		}, function(isConfirm) {
			if (isConfirm) {
				$('#modalPrecioVentaProducto').modal('hide');
			}
		});
	}
</script>