<script>
	//Precio Venta Producto///////////////////////
	function AbrirModalPrecioVenta(idProducto) {
		IdProducto = $('#txtID').val();
		// ObtenerPreciosDeventa(idProducto)
		$('#modalPrecioVentaProducto').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	// function ObtenerPreciosDeventa(idProducto) {
	// 	$.post(base_url + "index.php/ListaDePrecios/obtenerListayPreciosDeVenta", {
	// 		id_producto: idProducto
	// 	}, function(datos) {
	// 		var obj = $.parseJSON(datos); // lo convierto a un objeto a json
	// 		tbl = '';
	// 		$.each(obj, function(index, v) { // recorro el array
	// 			tbl = tbl + "<tr id='" + v.id_detalle_lprecio + "'>";
	// 			tbl = tbl + "<td  style= 'display:none' >" + v.id_detalle_lprecio + "</td>";
	// 			tbl = tbl + "<td  style='width:30%'' >" + v.nombre_lprecio + "</td>";
	// 			tbl = tbl + '<td style="width:30%" class="text-right" ><input class="text-right form-control form-control-line" type="text" min="0" name="" value="' + v.total + '"></td>';
	// 			tbl = tbl + "</tr>";


	// 		}); //fin each
	// 		$("#detallePrecioVenta").html(tbl);

	// 	});
	// }

	function grabarPrecios() {

		var idProducto = $("#txtID").val();
		var detallePrecios = [];

		$("#tablePrecios tbody tr").each(function() {

			var id_lprecio = $(this).find("td").eq(1).attr("data-id-lprecio"); 
			var precio = $(this).find("input").val();

			detallePrecios.push({
				id_producto: idProducto,
				id_lprecio: id_lprecio,
				precio_venta: precio
			});

		});

		console.log(detallePrecios);

		$.post(base_url + "index.php/ListaDePrecios/grabarPrecios", 
			{ detalle: detallePrecios },
			function(resp){
				console.log(resp);
			}
		).done(function(){
			$('#modalPrecioVentaProducto').modal('hide');
			swal("Listo", "Precio guardado correctamente", "success");
		});
	}

	function maxPrecioVenta() {
		var numberInput = document.getElementById('montoPrecioVenta');
		numberInput.addEventListener('input', function() {
			var value = parseInt(numberInput.value);
			if (numberInput.value.length > 7) {
				numberInput.value = numberInput.value.slice(0, 7);
			}
			if (value > 1000000) {
				numberInput.value = 1000000;
			} else if (value < 0) {
				numberInput.value = 0;
			}
		});
	}
	
</script>
