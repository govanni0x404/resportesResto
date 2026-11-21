		var base = "<?php echo base_url(); ?>index.php/Caja/";
		var catalogo = null;
		var carro = [];          // [{id_producto, nombre, precio, cantidad, agregados: [id_agregado], nota}]
		var cliente = null;      // {id_cliente, nombre_cliente, rut}
		var tipoDesc = 'monto';
		var familiaActiva = 0;
		var pagos = [];          // [{id_fpago, monto}]
		var resumen = null;
		var enviando = false;
		var propina = 0;
		var opcionEdicion = null; // {id_producto, indice} mientras el modal de opciones está abierto
		var mesa = null;         // Venta por mesa: {id_mesa, id_cotizacion, inicio (ms)}; null en venta directa
		var cobroParcial = null; // Cobro de una parte de la mesa: {id_detalle: cantidad}; null = toda la mesa
		var cerrandoCaja = false;
		var modoCaja = 'ambos';  // Configuraciones: 'directa' | 'mesas' | 'ambos'
		// Los avisos llevan nombres de mesas, productos y destinos escritos por usuarios: siempre como texto
		if (window.toastr) toastr.options.escapeHtml = true;

