<script>
	function AbrirModalPrecioVenta(idProducto) {

		// 1. Si no llega parámetro, usamos el input correcto
		if (!idProducto || idProducto === "0") {
			idProducto = $("#txtID").val();
		}

		// 2. Validación
		if (!idProducto || idProducto === "0" || idProducto === "") {
			console.error("ID vacío → no se puede abrir modal de precios");
			return;
		}

		// 3. Cargar lista de precios y usar los precios existentes
		CargarListasDePreciosVacias();

		// 4. Abrir modal
		$('#modalPrecioVentaProducto').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function cargarPreciosYComisionesParaEdicion(idProducto) {

		if (!idProducto || idProducto === "0") {
			console.error("ID vacío → evitando llamada");
			return;
		}

		$.post(base_url + "index.php/Productos/obtenerPreciosYComisiones", {
			id_producto: idProducto
		}, function(datos) {
		});
	}

	function cancelarEdicionPrecios() {
		$('#modalPrecioVentaProducto').modal('hide');
	}
</script>