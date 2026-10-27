<style>
	/* Colores del tema (component_ui): verde #558B2F, bordes #e1e6ef, texto #374767 */
	.caja .panel { margin-bottom: 20px; border: 1px solid #e6eaf0; border-radius: 10px; box-shadow: 0 1px 3px rgba(55,71,103,.06); overflow: hidden; }
	/* Cabeceras blancas: el verde queda en íconos y acciones, no en bloques */
	.caja .panel-success > .panel-heading { background-color: #fff !important; color: #374767; border-color: #eef1f5; padding: 12px 18px; }
	.caja .panel-heading .panel-title { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
	.caja .panel-heading h4 { margin: 0; font-size: 16px; font-weight: 600; color: #374767; }
	.caja .panel-heading i { margin-right: 6px; color: #558B2F; }
	.caja .panel-heading .badge { background: #558B2F; color: #fff; font-size: 12px; }
	.caja .panel-body { padding: 18px; }

	/* Pestañas y resumen de la caja en una sola franja */
	.caja-barra { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 20px; background: #fff; border: 1px solid #e6eaf0; border-radius: 10px;
		padding: 0 16px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(55,71,103,.06); }
	.caja-tabs { border: 0; margin: 0; display: flex; flex-wrap: wrap; }
	.caja-tabs > li { float: none; margin-bottom: 0; }
	.caja-tabs > li > a { color: #6b7a90; padding: 15px 14px 13px; margin: 0; border: 0 !important; border-bottom: 2px solid transparent !important; border-radius: 0; background: none !important; }
	.caja-tabs > li > a:hover { color: #374767; }
	.caja-tabs > li.active > a, .caja-tabs > li.active > a:hover, .caja-tabs > li.active > a:focus { color: #558B2F; font-weight: 600; border-bottom-color: #558B2F !important; }
	.caja-estado { display: flex; flex-wrap: wrap; gap: 6px 22px; margin-left: auto; padding: 8px 0; }
	.caja-estado > div { white-space: nowrap; }
	.caja-estado small { display: block; color: #8a94a6; text-transform: uppercase; font-size: 10px; letter-spacing: .4px; line-height: 1.2; }
	.caja-estado b { font-size: 14px; color: #374767; }
	.caja-estado .ok b { color: #558B2F; }
	.caja-contenido { border: 0; }
	/* El tema le pone borde propio a .tab-content .panel-body: dentro de un panel queda doble */
	.caja .tab-content .panel-body { border: 0; border-radius: 0; }

	/* Venta: la venta en tabla al centro y el panel de cliente / cobro a la derecha. En escritorio ocupa el alto
	   visible (ajustarAltoVenta en js.php) y solo las líneas hacen scroll. */
	.caja-venta { display: flex; gap: 16px; }
	.caja-venta-principal { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 12px; }
	/* Panel lateral proporcional a la pantalla: ~30% del ancho, entre 360 y 560 px */
	.caja-venta-lateral { flex: 0 0 30%; min-width: 360px; max-width: 560px; display: flex; flex-direction: column; gap: 12px; min-height: 0; overflow-y: auto; }
	.caja-venta .panel { margin: 0; }
	.caja-tarjeta { background: #fff; border: 1px solid #e6eaf0; border-radius: 10px; box-shadow: 0 1px 3px rgba(55,71,103,.06); }
	.caja-tarjeta-titulo { font-size: 12px; color: #6b7a90; text-transform: uppercase; letter-spacing: .4px; font-weight: 700; margin-bottom: 8px; }

	/* Buscador con resultados desplegables */
	.caja-busqueda { display: flex; gap: 10px; align-items: center; padding: 12px; }
	.caja-buscar { position: relative; flex: 1; min-width: 0; }
	.caja-buscar > i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #8a94a6; font-size: 15px; z-index: 3; }
	.caja-buscar input { font-size: 15px; height: 44px; padding-left: 40px; border-radius: 8px; border-color: #dfe4ec; box-shadow: none; }
	.caja-buscar input:focus { border-color: #558B2F; box-shadow: 0 0 0 3px rgba(85,139,47,.12); }
	.caja-resultados { display: none; position: absolute; left: 0; right: 0; top: 48px; z-index: 1000; background: #fff; border: 1px solid #e1e6ef; border-radius: 8px;
		box-shadow: 0 8px 24px rgba(55,71,103,.15); max-height: 360px; overflow-y: auto; }
	.caja-resultados.visible { display: block; }
	.caja-resultado { display: flex; align-items: center; gap: 10px; padding: 9px 14px; cursor: pointer; border-bottom: 1px solid #f2f4f7; }
	.caja-resultado:last-child { border-bottom: 0; }
	.caja-resultado.activo, .caja-resultado:hover { background: #f1f7ec; }
	.caja-resultado .nombre { flex: 1; min-width: 0; font-weight: 600; color: #374767; font-size: 13px; }
	.caja-resultado .familia { font-size: 11px; color: #8a94a6; font-weight: 400; display: block; }
	.caja-resultado .precio { font-weight: 700; color: #558B2F; white-space: nowrap; }
	.caja-resultados .sin-resultados { padding: 14px; color: #8a94a6; text-align: center; }
	.caja-busqueda .btn-catalogo { height: 44px; border-radius: 8px; font-weight: 600; padding: 0 16px; white-space: nowrap; }
	.caja-busqueda select { height: 44px; border-radius: 8px; width: 190px; flex: 0 0 190px; }

	/* Líneas de la venta */
	.caja-carro { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
	.caja-linea-cab { display: flex; align-items: center; gap: 10px; padding: 10px 18px; background: #f7f9fa; border-bottom: 1px solid #eef1f5;
		font-size: 13px; font-weight: 700; color: #6b7a90; text-transform: uppercase; letter-spacing: .3px; }
	.caja-lineas { flex: 1; min-height: 160px; max-height: 45vh; overflow-y: auto; }
	.caja-linea { display: flex; align-items: center; gap: 10px; padding: 12px 18px; border-bottom: 1px solid #f2f4f7; }
	.caja-linea .info, .caja-linea-cab .info { flex: 1; min-width: 0; }
	.caja-linea-cab .info { white-space: nowrap; }
	.caja-linea-cab .badge { background-color: #558B2F !important; color: #fff !important; font-size: 12px; margin-left: 4px; vertical-align: 1px; }
	.caja-linea .nombre { font-weight: 700; color: #2b3a55; font-size: 16px; line-height: 1.3; }
	.caja-linea a.editar { color: #374767; border-bottom: 1px dashed #8a94a6; text-decoration: none; }
	.caja-linea .detalle-linea { font-size: 13px; color: #6b7a90; }
	.caja-linea .unitario { font-size: 12px; color: #8a94a6; display: none; }
	.caja-linea .precio, .caja-linea-cab .precio { width: 110px; text-align: right; color: #6b7a90; white-space: nowrap; }
	.caja-linea .precio { font-size: 16px; font-weight: 600; }
	.caja-linea .subtotal, .caja-linea-cab .subtotal { width: 110px; text-align: right; white-space: nowrap; }
	.caja-linea .subtotal { font-weight: 700; color: #2b3a55; font-size: 17px; }
	.caja-linea .cantidad, .caja-linea-cab .cantidad { width: 132px; flex: 0 0 132px; text-align: center; }
	.caja-linea .cantidad .form-control, .caja-linea .cantidad .btn { height: 36px; }
	.caja-linea .cantidad .btn { font-size: 13px; }
	.caja-linea .cantidad .btn { border-color: #e1e6ef; color: #6b7a90; }
	.caja-linea .cantidad input { text-align: center; padding: 4px; font-size: 17px; font-weight: 700; color: #2b3a55; border-color: #e1e6ef; -moz-appearance: textfield; }
	.caja-linea .cantidad input::-webkit-inner-spin-button, .caja-linea .cantidad input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
	.caja-linea .quitar, .caja-linea-cab .quitar { width: 28px; flex: 0 0 28px; text-align: center; }
	.caja-linea .quitar { color: #b8c0cc; background: none; border: 0; padding: 4px; font-size: 16px; }
	.caja-linea .quitar:hover { color: #c9302c; }
	.caja-carro-vacio { text-align: center; color: #8a94a6; padding: 60px 10px; }
	.caja-carro-vacio i { display: block; font-size: 44px; margin-bottom: 8px; color: #c5ccd6; }
	.caja-carro-pie { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 18px; border-top: 1px solid #eef1f5; background: #fafbfc; }
	.caja-carro-pie select { width: 260px; max-width: 60%; border-radius: 6px; }
	.caja-carro-pie .total { font-size: 17px; font-weight: 600; color: #6b7a90; white-space: nowrap; }
	.caja-carro-pie .total b { color: #558B2F; font-size: 32px; font-weight: 800; margin-left: 6px; vertical-align: middle; }
	.caja-carro-pie select { height: 42px; font-size: 15px; font-weight: 600; }

	/* Panel lateral */
	.caja-venta-lateral .caja-tarjeta { padding: 16px 18px; }
	.caja-venta-lateral .form-control, .caja-venta-lateral .input-group-btn .btn { height: 44px; font-size: 16px; font-weight: 600; color: #2b3a55; }
	.caja-venta-lateral .form-control::placeholder { font-weight: 400; color: #a3acba; }
	.caja-venta-lateral .caja-cliente .btn { height: 44px; flex-basis: 44px; font-size: 15px; }
	/* El resumen ocupa el resto del alto y deja Cobrar al fondo, alineado con el total de la venta */
	.caja-venta-lateral .caja-resumen { flex: 1; display: flex; flex-direction: column; }
	.caja-resumen .caja-acciones { margin-top: auto; padding-top: 14px; }
	.caja-venta-lateral .form-group { margin-bottom: 10px; }
	.caja-venta-lateral .form-group:last-child { margin-bottom: 0; }
	.caja-venta-lateral label { font-size: 12px; color: #6b7a90; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; font-weight: 700; }
	.caja-venta-lateral .form-control { border-radius: 6px; }
	.caja-cliente { display: flex; gap: 6px; }
	.caja-cliente input { flex: 1; min-width: 0; background: #fff !important; cursor: pointer; }
	.caja-cliente .btn { flex: 0 0 38px; border-radius: 6px; }
	.caja-resumen .fila { display: flex; justify-content: space-between; color: #4a5872; font-size: 17px; line-height: 2; }
	.caja-resumen .fila span:last-child { font-weight: 700; color: #2b3a55; }
	.caja-resumen .fila.descuento, .caja-resumen .fila.descuento span:last-child { color: #c9302c; }
	.caja-resumen .fila.total { border-top: 1px solid #eef1f5; margin-top: 6px; padding-top: 6px; color: #374767; font-size: 16px; font-weight: 700; }
	.caja-acciones { display: flex; gap: 10px; }
	.caja-acciones #btnLimpiar { flex: 0 0 54px; border-radius: 8px; color: #8a94a6; }
	.caja-acciones #btnLimpiar { height: 56px; }
	.caja-acciones #btnCobrar { flex: 1; font-size: 20px; font-weight: 600; padding: 13px; border-radius: 8px; height: 56px; }
	.caja-num { text-align: right; white-space: nowrap; }

	/* Modal de productos por familia */
	.caja-familias { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 0; }
	.caja-familias .btn { white-space: nowrap; border-radius: 18px; font-size: 12px; padding: 6px 14px; }
	.caja-familias .btn-default { color: #374767; border-color: #e1e6ef; background: #f7f9fa; }
	.caja-familias .btn-default:hover { background: #eef2f6; }
	.caja-productos { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); grid-auto-rows: min-content; gap: 12px;
		overflow-y: auto; padding: 2px 4px 4px 2px; max-height: 58vh; }
	.caja-producto { border: 1px solid #e6eaf0; border-radius: 10px; background: #fff; cursor: pointer; min-height: 92px; padding: 12px 14px; position: relative;
		display: flex; flex-direction: column; justify-content: space-between; text-align: left; transition: border-color .15s, box-shadow .15s, transform .1s; }
	.caja-producto:hover, .caja-producto:focus { border-color: #558B2F; box-shadow: 0 4px 12px rgba(55,71,103,.10); outline: none; }
	.caja-producto:active { transform: scale(.97); }
	.caja-producto.agregado { border-color: #558B2F; background: #f1f7ec; }
	.caja-producto .nombre { font-size: 12px; font-weight: 600; line-height: 1.35; color: #374767; word-break: break-word; text-transform: uppercase; }
	.caja-producto .opciones { font-size: 11px; color: #8a94a6; margin-top: 3px; }
	.caja-producto .precio { font-weight: 700; font-size: 15px; color: #558B2F; margin-top: 8px; }
	.caja-producto .en-venta { position: absolute; top: 6px; right: 6px; background: #558B2F; color: #fff; border-radius: 10px; font-size: 11px; font-weight: 700; padding: 1px 7px; }
	.caja-vacio { color: #8a94a6; padding: 40px 10px; text-align: center; grid-column: 1 / -1; }
	.caja-vacio i { display: block; font-size: 36px; margin-bottom: 6px; }
	#modalProductos .modal-dialog { width: 92%; max-width: 1100px; }
	#modalProductos .modal-body { padding-top: 10px; }
	.modal-productos-pie { display: flex; align-items: center; justify-content: space-between; text-align: left; }
	/* El clearfix de Bootstrap (::before/::after) cuenta como elemento flex y descuadra el pie */
	.modal-productos-pie::before, .modal-productos-pie::after { display: none; }

	/* Celular: barra fija para ir a la venta cuando el carro queda abajo de los productos */
	.caja-barra-movil { display: none; }
	@media (max-width: 991px) {
		.caja-barra-movil.visible { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 1030; align-items: center; justify-content: space-between;
			background: #558B2F; color: #fff; padding: 12px 16px; font-size: 16px; font-weight: 600; box-shadow: 0 -2px 8px rgba(0,0,0,.15); cursor: pointer; }
		.caja.con-barra { padding-bottom: 60px; }
	}

	/* Tablas y paneles de las otras pestañas */
	.caja .table > thead > tr > th { background: #f7f9fa; color: #374767; font-weight: 600; border-bottom-width: 1px; }
	.caja .table-bordered, .caja .table-bordered > thead > tr > th, .caja .table-bordered > tbody > tr > td { border-left: 0; border-right: 0; border-color: #eef1f5; }
	.caja .table > tbody > tr > td { vertical-align: middle; }
	#tablaVentas tr.anulada td { color: #aab2c0; text-decoration: line-through; }
	#tablaVentas tr.anulada td.acciones { text-decoration: none; }
	#tablaVentas tr.nc td.caja-num { color: #d9534f; }
	#tabVentas .panel-heading, #tabCierre .panel-heading { position: relative; }
	.caja-btn-titulo { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); }
	.dev-info { background: #f7f9fc; border: 1px solid #e1e6ef; padding: 10px 12px; margin-bottom: 12px; color: #374767; }
	.dev-info b { font-size: 15px; }
	#devLineas td { vertical-align: middle; }
	#devLineas input { text-align: center; }
	#devLineas .dev-col-cant { width: 130px; }
	@media (max-width: 767px) { #devLineas .dev-col-cant { width: 80px; } }
	#devLineas tr.agotada td { color: #aab2c0; }
	.caja-chk-imprimir { float: left; margin: 8px 0 0; }
	.caja-chk-imprimir .cbo-papel { margin-left: 8px; height: 24px; padding: 0 4px; }
	.caja-frame-impresion { position: absolute; left: -10000px; top: 0; width: 400px; height: 600px; border: 0; }
	.caja-apertura { max-width: 460px; margin: 30px auto; }
	.caja-apertura .icono { font-size: 54px; color: #558B2F; text-align: center; display: block; }
	.caja-tipo-mov { display: flex; gap: 6px; }
	.caja-tipo-mov label { flex: 1; text-align: center; margin: 0; }
	.cierre-tabla td:last-child { text-align: right; white-space: nowrap; }
	.cierre-diferencia { font-size: 20px; font-weight: 700; text-align: center; padding: 10px; background: #f7f9fa; border: 1px solid #e1e6ef; margin: 10px 0 15px; }

	/* Modales */
	/* Cobro: montos arriba, formas de pago abajo a la izquierda y el monto a cargar a la derecha */
	#modalPago .modal-dialog { width: 94%; max-width: 860px; }
	#modalPago .modal-body { padding: 18px 20px; }
	.pago-montos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; }
	.pago-montos > div { background: #f7f9fa; border: 1px solid #e6eaf0; border-radius: 10px; padding: 10px 14px; }
	.pago-montos small { display: block; font-size: 12px; font-weight: 700; color: #6b7a90; text-transform: uppercase; letter-spacing: .3px; }
	.pago-montos b { font-size: 26px; font-weight: 800; color: #2b3a55; white-space: nowrap; }
	.pago-montos .total { background: #f1f7ec; border-color: #cfe3bf; }
	.pago-montos .total b { color: #558B2F; }
	.pago-montos .falta b { color: #c9302c; }
	.pago-montos .falta.cubierto b { color: #8a94a6; }
	.pago-montos .vuelto b { color: #2e7d32; }
	.pago-propina { background: #f7f9fa; border: 1px solid #e6eaf0; border-radius: 10px; padding: 8px 12px; margin-bottom: 12px; }
	.pago-propina input[type=number] { width: 120px; display: inline-block; text-align: right; }
	.pago-agregados { border: 1px dashed #d5dce6; border-radius: 10px; padding: 6px 12px; margin-bottom: 16px; min-height: 52px; }
	.pago-agregados .vacio { color: #8a94a6; text-align: center; padding: 10px 0; }
	.pago-fila { display: flex; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px solid #f0f2f5; }
	.pago-fila:last-child { border-bottom: 0; }
	.pago-fila .nombre { flex: 1; font-size: 16px; font-weight: 700; color: #2b3a55; }
	.pago-fila input { width: 150px; text-align: right; font-size: 18px; font-weight: 700; height: 40px; }
	.pago-cuerpo { display: flex; gap: 18px; }
	.pago-formas { flex: 0 0 38%; display: flex; flex-direction: column; gap: 8px; }
	.pago-formas .btn { text-align: left; font-size: 15px; font-weight: 700; padding: 11px 14px; border-radius: 8px; background: #fff; color: #374767; border: 1px solid #dfe4ec; white-space: normal; }
	.pago-formas .btn:hover { border-color: #558B2F; }
	.pago-formas .btn.activa { background: #558B2F; border-color: #558B2F; color: #fff; }
	.pago-formas .btn.cortesia { border-style: dashed; }
	.pago-formas .btn.cortesia.activa { background: #f0ad4e; border-color: #f0ad4e; }
	.pago-formas .btn .fa { width: 22px; }
	.pago-monto { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 10px; }
	.pago-monto label { font-size: 12px; font-weight: 700; color: #6b7a90; text-transform: uppercase; letter-spacing: .3px; margin: 0; }
	.pago-monto .input-group-addon { font-size: 20px; font-weight: 700; }
	#montoPago { height: 56px; font-size: 28px; font-weight: 800; text-align: right; color: #2b3a55; }
	.pago-billetes { display: flex; flex-wrap: wrap; gap: 6px; }
	.pago-billetes .btn { flex: 1 1 70px; font-weight: 700; }
	#btnAgregarPago { font-size: 16px; font-weight: 700; padding: 10px; border-radius: 8px; }
	.btn-pago-exacto { font-size: 20px; font-weight: 800; padding: 14px; border-radius: 8px; margin-top: auto; }
	#modalPago .modal-footer .btn { border-radius: 8px; }
	@media (max-width: 767px) {
		.pago-montos { grid-template-columns: repeat(2, 1fr); }
		.pago-cuerpo { flex-direction: column; }
		.pago-montos b { font-size: 20px; }
	}
	.opcion-lista .checkbox { margin: 6px 0; }
	.opcion-lista .checkbox label { font-size: 14px; }
	.opcion-lista .valor { color: #558B2F; font-weight: bold; }
	#modalAutorizacion { z-index: 1060; }

	/* Venta registrada */
	#modalVentaOk .modal-dialog { width: 92%; max-width: 440px; margin-top: 12vh; }
	.venta-ok { border: 0; border-radius: 14px; padding: 30px 28px 20px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
	.venta-ok-icono { width: 76px; height: 76px; margin: 0 auto 14px; border-radius: 50%; background: #e8f3e0; color: #558B2F; font-size: 38px; line-height: 76px; }
	.venta-ok h3 { margin: 0 0 6px; font-size: 26px; font-weight: 800; color: #2b3a55; }
	.venta-ok-doc { font-size: 16px; font-weight: 600; color: #6b7a90; margin-bottom: 16px; }
	.venta-ok-vuelto { background: #f1f7ec; border: 1px solid #cfe3bf; border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 26px; font-weight: 800; color: #2e7d32; }
	.venta-ok-vuelto:empty { display: none; }
	.venta-ok-acciones { display: flex; gap: 10px; margin-bottom: 10px; }
	.venta-ok-acciones .btn { flex: 1; padding: 10px; font-weight: 700; border-radius: 8px; border-color: #dfe4ec; color: #374767; }
	.venta-ok-acciones .btn:hover { border-color: #558B2F; color: #558B2F; background: #fff; }
	.venta-ok-acciones .fa { margin-right: 4px; }
	#btnNuevaVenta { font-size: 18px; font-weight: 700; padding: 13px; border-radius: 8px; }
	#btnNuevaVenta:focus { outline: none; box-shadow: 0 0 0 4px rgba(85,139,47,.3); }
	.venta-ok-ayuda { font-size: 12px; color: #a3acba; margin-top: 8px; }
	.modal-header.btn-success .close { color: #fff; opacity: .85; text-shadow: none; }

	@media (max-width: 991px) {
		.caja-venta { flex-direction: column; }
		.caja-venta-lateral { flex-basis: auto; min-width: 0; max-width: none; overflow: visible; }
		.caja-busqueda { flex-wrap: wrap; }
		.caja-busqueda .caja-buscar { flex-basis: 100%; }
		.caja-busqueda select { flex: 1; width: auto; }
		.caja-productos { max-height: 55vh; }
		.caja-estado { margin-left: 0; width: 100%; border-top: 1px solid #eef1f5; }
	}
	@media (min-width: 992px) {
		/* El alto lo fija ajustarAltoVenta() en js.php */
		.caja-venta.alto-fijo .caja-lineas { max-height: none; }
	}
	@media (max-width: 480px) {
		.caja-tabs > li > a { padding: 10px 8px; font-size: 12px; }
		.caja-productos { grid-template-columns: repeat(2, 1fr); }
		.caja-linea .subtotal { width: 64px; }
		.caja-linea-cab .precio, .caja-linea .precio { display: none; }
		.caja-linea .unitario { display: block; }
		.caja-linea .cantidad, .caja-linea-cab .cantidad { width: 96px; flex-basis: 96px; }
		.caja-carro-pie { flex-wrap: wrap; }
		.caja-carro-pie select { max-width: none; width: 100%; }
	}
</style>

<div class="row caja">
	<div class="col-xs-12">

		<!-- Sin caja abierta -->
		<div id="panelApertura" class="panel panel-success lobidisable caja-apertura" style="display:none;">
			<div class="panel-heading">
				<div class="panel-title"><h4><i class="pe-7s-unlock"></i>Abrir caja</h4></div>
			</div>
			<div class="panel-body">
				<i class="pe-7s-calculator icono"></i>
				<p class="text-center text-muted">No tienes una caja abierta.<br>Ingresa el fondo inicial (sencillo) para comenzar a vender.</p>
				<div class="form-group">
					<label for="fondoInicial">Fondo inicial</label>
					<div class="input-group">
						<span class="input-group-addon">$</span>
						<input type="number" min="0" step="1" class="form-control input-lg" id="fondoInicial" value="0">
					</div>
					<p class="help-block" id="ayudaFondo" style="display:none">Sencillo que quedó en el último cierre.</p>
				</div>
				<button id="btnAbrirCaja" class="btn btn-success btn-lg btn-block"><i class="fa fa-unlock"></i> Abrir caja</button>
			</div>
		</div>

		<!-- Caja abierta -->
		<div id="panelCaja" style="display:none;">
			<div class="caja-barra-movil" id="barraMovil">
				<span><i class="pe-7s-cart"></i> Ver venta (<span id="barraItems">0</span>)</span>
				<span id="barraTotal">$0</span>
			</div>
			<div class="caja-barra">
				<ul class="nav nav-tabs caja-tabs">
					<li class="active"><a href="#tabVenta" data-toggle="tab" id="lnkVenta"><i class="pe-7s-cart"></i> Venta</a></li>
					<li><a href="#tabVentas" data-toggle="tab" id="lnkVentas"><i class="pe-7s-note2"></i> Ventas</a></li>
					<li><a href="#tabMovimientos" data-toggle="tab" id="lnkMovimientos"><i class="pe-7s-cash"></i> Ingresos / Retiros</a></li>
					<li><a href="#tabCierre" data-toggle="tab" id="lnkCierre"><i class="pe-7s-lock"></i> Cierre</a></li>
				</ul>
				<div class="caja-estado">
					<div><small>Caja</small><b id="estCaja">-</b></div>
					<div><small>Apertura</small><b id="estApertura">-</b></div>
					<div><small>Fondo inicial</small><b id="estFondo">$0</b></div>
					<div class="ok"><small>Ventas</small><b id="estVentas">$0</b></div>
				</div>
			</div>

			<div class="tab-content caja-contenido">
				<!-- ===================== VENTA ===================== -->
				<div class="tab-pane fade active in" id="tabVenta">
					<div class="caja-venta">
						<div class="caja-venta-principal">
							<div class="caja-tarjeta caja-busqueda">
								<div class="caja-buscar">
									<i class="fa fa-search"></i>
									<input type="text" id="buscarProducto" class="form-control" placeholder="Buscar producto por nombre o código de barras" autocomplete="off">
									<div class="caja-resultados" id="resultados"></div>
								</div>
								<button type="button" class="btn btn-success btn-catalogo" id="btnCatalogo" title="Ver todos los productos por familia (F2)"><i class="fa fa-th-large"></i> Productos</button>
								<select id="cboLista" class="form-control" title="Lista de precios"></select>
							</div>

							<div class="caja-tarjeta caja-carro">
								<div class="caja-linea-cab">
									<span class="info">Producto <span class="badge" id="cantItems">0</span></span>
									<span class="precio">Precio</span>
									<span class="cantidad">Cantidad</span>
									<span class="subtotal">Total</span>
									<span class="quitar"></span>
								</div>
								<div class="caja-lineas" id="lineas"></div>
								<div class="caja-carro-pie">
									<select id="cboDocumento" class="form-control" title="Documento"></select>
									<div class="total">Total <b id="lblTotal">$0</b></div>
								</div>
							</div>
						</div>

						<div class="caja-venta-lateral">
							<div class="caja-tarjeta">
								<div class="caja-tarjeta-titulo">Cliente</div>
								<div class="caja-cliente">
									<input type="text" id="txtCliente" class="form-control" readonly placeholder="Público general">
									<button class="btn btn-default" id="btnBuscarCliente" title="Buscar o crear cliente"><i class="fa fa-search"></i></button>
									<button class="btn btn-default" id="btnQuitarCliente" title="Quitar cliente"><i class="fa fa-times"></i></button>
								</div>
							</div>

							<div class="caja-tarjeta">
								<div class="form-group">
									<label for="txtDescuento">Descuento</label>
									<div class="input-group">
										<input type="number" min="0" step="1" id="txtDescuento" class="form-control" value="0">
										<span class="input-group-btn">
											<button class="btn btn-default active" data-tipo-desc="monto" type="button">$</button>
											<button class="btn btn-default" data-tipo-desc="porc" type="button">%</button>
										</span>
									</div>
								</div>
								<div class="form-group">
									<label for="txtObservacion">Observación</label>
									<input type="text" id="txtObservacion" maxlength="150" class="form-control" placeholder="Opcional">
								</div>
							</div>

							<div class="caja-tarjeta caja-resumen">
								<div class="fila"><span>Subtotal</span><span id="lblSubtotal">$0</span></div>
								<div class="fila descuento" id="filaDescuento" style="display:none"><span>Descuento</span><span id="lblDescuento">$0</span></div>
								<div class="fila"><span>Neto</span><span id="lblNeto">$0</span></div>
								<div class="fila"><span>IVA (19%)</span><span id="lblIva">$0</span></div>
								<div class="fila" id="filaExento" style="display:none"><span>Exento</span><span id="lblExento">$0</span></div>
								<div class="caja-acciones">
									<button class="btn btn-default btn-lg" id="btnLimpiar" title="Vaciar venta"><i class="fa fa-trash"></i></button>
									<button class="btn btn-success btn-lg" id="btnCobrar" disabled><i class="fa fa-money"></i> Cobrar <span id="lblCobrar"></span></button>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="tab-pane fade" id="tabVentas">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title"><h4><i class="pe-7s-note2"></i>Ventas de la caja</h4></div>
							<button class="btn btn-default btn-sm caja-btn-titulo" id="btnDevolucionBuscar" title="Devolver productos de una venta de cualquier caja">
								<i class="fa fa-undo"></i> Devolución
							</button>
						</div>
						<div class="panel-body table-responsive">
							<table class="table table-bordered table-hover" id="tablaVentas">
								<thead>
									<tr><th>Hora</th><th>Documento</th><th>Folio</th><th>Cliente</th><th>Pago</th><th class="caja-num">Total</th><th class="text-center">Acciones</th></tr>
								</thead>
								<tbody></tbody>
							</table>
						</div>
					</div>
				</div>

				<!-- ===================== MOVIMIENTOS ===================== -->
				<div class="tab-pane fade" id="tabMovimientos">
					<div class="row">
						<div class="col-md-4">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-plus"></i>Nuevo movimiento</h4></div>
								</div>
								<div class="panel-body">
									<div class="form-group">
										<div class="btn-group caja-tipo-mov" data-toggle="buttons">
											<label class="btn btn-default active"><input type="radio" name="tipoMov" value="ingreso" checked> <i class="fa fa-arrow-down"></i> Ingreso</label>
											<label class="btn btn-default"><input type="radio" name="tipoMov" value="egreso"> <i class="fa fa-arrow-up"></i> Retiro / Gasto</label>
										</div>
									</div>
									<div class="form-group">
										<label for="montoMov">Monto</label>
										<div class="input-group">
											<span class="input-group-addon">$</span>
											<input type="number" min="1" step="1" class="form-control" id="montoMov">
										</div>
									</div>
									<div class="form-group">
										<label for="obsMov">Motivo</label>
										<input type="text" maxlength="100" class="form-control" id="obsMov" placeholder="Ej: pago proveedor de pan">
									</div>
									<button class="btn btn-success btn-block" id="btnMovimiento"><i class="fa fa-check"></i> Registrar</button>
								</div>
							</div>
						</div>
						<div class="col-md-8">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-cash"></i>Movimientos de la caja</h4></div>
								</div>
								<div class="panel-body table-responsive">
									<table class="table table-bordered table-hover" id="tablaMovimientos">
										<thead><tr><th>Fecha</th><th>Motivo</th><th class="caja-num">Ingreso</th><th class="caja-num">Retiro</th></tr></thead>
										<tbody></tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- ===================== CIERRE ===================== -->
				<div class="tab-pane fade" id="tabCierre">
					<div class="row">
						<div class="col-md-6">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-graph1"></i>Resumen del sistema</h4></div>
									<button class="btn btn-default btn-sm caja-btn-titulo" id="btnInformeX" title="Imprimir el resumen del turno sin cerrar la caja">
										<i class="fa fa-print"></i> Informe X
									</button>
								</div>
								<div class="panel-body">
									<table class="table table-condensed cierre-tabla">
										<tbody id="resumenCierre"></tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-calculator"></i>Lo que hay en caja</h4></div>
								</div>
								<div class="panel-body">
									<div class="form-group">
										<label for="efectivoContado">Efectivo contado (incluye el fondo inicial, ya pagadas las propinas)</label>
										<div class="input-group">
											<span class="input-group-addon">$</span>
											<input type="number" min="0" step="1" class="form-control input-lg" id="efectivoContado">
										</div>
									</div>
									<div class="row">
										<div class="col-xs-6 form-group">
											<label for="fondoSiguiente">Fondo para el próximo turno</label>
											<div class="input-group">
												<span class="input-group-addon">$</span>
												<input type="number" min="0" step="1" class="form-control" id="fondoSiguiente">
											</div>
										</div>
										<div class="col-xs-6 form-group">
											<label>Efectivo a entregar</label>
											<p class="form-control-static"><b id="lblEntregar">$0</b></p>
										</div>
									</div>
									<div id="declaradoOtros"></div>
									<div class="caja-totales">
										<div class="fila"><span>Esperado</span><b id="lblEsperado">$0</b></div>
										<div class="fila"><span>Declarado</span><b id="lblDeclarado">$0</b></div>
									</div>
									<div class="cierre-diferencia">Diferencia: <span id="lblDiferencia">$0</span></div>
									<button class="btn btn-danger btn-lg btn-block" id="btnCerrarCaja"><i class="fa fa-lock"></i> Cerrar caja</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modal pago -->
<!-- Productos por familia -->
<div class="modal fade" id="modalProductos" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"><i class="fa fa-th-large"></i> Productos</h4>
			</div>
			<div class="modal-body">
				<div class="caja-buscar">
					<i class="fa fa-filter"></i>
					<input type="text" id="filtroProductos" class="form-control" placeholder="Filtrar en la familia elegida" autocomplete="off">
				</div>
				<div class="caja-familias" id="familias"></div>
				<div class="caja-productos" id="productos"></div>
			</div>
			<div class="modal-footer modal-productos-pie">
				<span class="text-muted">Toca un producto para agregarlo. En la venta: <b id="modalProductosItems">0</b></span>
				<button type="button" class="btn btn-success" data-dismiss="modal"><i class="fa fa-check"></i> Listo</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalPago" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Cobrar <span id="pagoTotal"></span></h4>
			</div>
			<div class="modal-body">
				<div class="pago-montos">
					<div class="total"><small>Total a pagar</small><b id="pagoTotalConPropina">$0</b></div>
					<div><small>Pagado</small><b id="pagoPagado">$0</b></div>
					<div class="falta" id="cajaFalta"><small>Falta</small><b id="pagoFalta">$0</b></div>
					<div class="vuelto"><small>Vuelto</small><b id="pagoVuelto">$0</b></div>
				</div>
				<div class="pago-propina" id="bloquePropina" style="display:none">
					<div class="checkbox checkbox-success" style="display:inline-block; margin:0 10px 0 0">
						<input type="checkbox" id="chkPropina"><label for="chkPropina" style="font-weight:bold">Propina <span id="lblPorPropina"></span></label>
					</div>
					<input type="number" min="0" step="1" class="form-control input-sm" id="txtPropina" value="0">
				</div>
				<div class="pago-agregados" id="filasPago"></div>

				<div class="pago-cuerpo">
					<div class="pago-formas" id="botonesFpago"></div>
					<div class="pago-monto">
						<label for="montoPago">Monto en <span id="nombreFpagoSel"></span></label>
						<div class="input-group">
							<span class="input-group-addon">$</span>
							<input type="number" min="0" step="1" class="form-control" id="montoPago" value="0">
						</div>
						<div class="pago-billetes" id="billetesPago"></div>
						<button type="button" class="btn btn-default" id="btnAgregarPago"><i class="fa fa-plus"></i> Agregar pago <small class="text-muted">(Enter)</small></button>
						<button type="button" class="btn btn-success btn-pago-exacto" id="btnPagoExacto" title="Completa lo que falta con la forma de pago elegida y registra la venta">
							<i class="fa fa-bolt"></i> Pago exacto
						</button>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<div class="checkbox checkbox-success caja-chk-imprimir">
					<input type="checkbox" id="chkImprimirVenta" class="chk-imprimir"><label for="chkImprimirVenta"><i class="fa fa-print"></i> Imprimir ticket</label>
					<select class="cbo-papel" title="Ancho del papel de la impresora"><option value="80">80 mm</option><option value="58">58 mm</option></select>
				</div>
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary btn-lg" id="btnConfirmarPago" disabled><i class="fa fa-check"></i> Confirmar venta</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal agregados / mensajes del producto -->
<div class="modal fade" id="modalOpciones" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title" id="opcionesTitulo"></h4>
			</div>
			<div class="modal-body">
				<div id="opcionesAgregados" style="display:none">
					<h5><b>Agregados</b> <small id="opcionesMaxAgregados" class="text-muted"></small></h5>
					<div class="opcion-lista" id="listaAgregados"></div>
					<hr>
				</div>
				<div id="opcionesMensajes" style="display:none">
					<h5><b>Indicaciones</b></h5>
					<div class="opcion-lista" id="listaMensajes"></div>
				</div>
				<div class="form-group" style="margin-top:8px">
					<label for="txtNotaProducto">Nota</label>
					<input type="text" class="form-control" id="txtNotaProducto" maxlength="100" placeholder="Ej: sin cebolla, bien cocido">
				</div>
			</div>
			<div class="modal-footer">
				<span class="pull-left" style="font-size:16px; padding-top:6px">Precio: <b id="opcionesPrecio"></b></span>
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-success" id="btnConfirmarOpciones">Agregar</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal autorización de administrador -->
<div class="modal fade" id="modalAutorizacion" tabindex="-1" role="dialog" data-backdrop="static">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<form id="frmAutorizacion" autocomplete="off">
				<div class="modal-header btn-success">
					<h4 class="modal-title"><i class="fa fa-lock"></i> Autorización</h4>
				</div>
				<div class="modal-body">
					<p id="autorizacionMotivo"></p>
					<p class="text-danger" id="autorizacionError" style="display:none"></p>
					<div class="form-group">
						<label for="cboAutorizador">Administrador</label>
						<select id="cboAutorizador" class="form-control"></select>
					</div>
					<div class="form-group">
						<label for="txtClaveAdmin">Clave</label>
						<input type="password" id="txtClaveAdmin" class="form-control" autocomplete="new-password">
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary">Autorizar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Modal venta registrada -->
<div class="modal fade" id="modalVentaOk" tabindex="-1" role="dialog" data-backdrop="static">
	<div class="modal-dialog" role="document">
		<div class="modal-content venta-ok">
			<div class="venta-ok-icono"><i class="fa fa-check"></i></div>
			<h3 id="ventaOkTitulo">Venta registrada</h3>
			<p class="venta-ok-doc" id="ventaOkDoc"></p>
			<div class="venta-ok-vuelto" id="ventaOkVuelto"></div>
			<p id="ventaOkAviso" class="alert alert-danger" style="display:none; text-align:left; padding:8px 10px"></p>
			<div class="venta-ok-acciones">
				<a class="btn btn-default" id="ventaOkTicket" target="_blank"><i class="fa fa-print"></i> Imprimir ticket</a>
				<a class="btn btn-default" id="ventaOkPdf" target="_blank"><i class="fa fa-file-pdf-o"></i> Ver PDF</a>
			</div>
			<button class="btn btn-success btn-lg btn-block" data-dismiss="modal" id="btnNuevaVenta">Nueva venta</button>
			<div class="venta-ok-ayuda">Enter para continuar</div>
		</div>
	</div>
</div>

<!-- Modal devolución (nota de crédito interna) -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"><i class="fa fa-undo"></i> Devolución · Nota de crédito interna</h4>
			</div>
			<div class="modal-body">
				<form id="devBusqueda" class="row">
					<div class="col-sm-6 form-group">
						<label for="devDocumento">Documento de la venta</label>
						<select id="devDocumento" class="form-control"></select>
					</div>
					<div class="col-sm-3 form-group">
						<label for="devFolio">Folio</label>
						<input type="number" min="1" step="1" id="devFolio" class="form-control">
					</div>
					<div class="col-sm-3 form-group">
						<label class="hidden-xs">&nbsp;</label>
						<button type="submit" class="btn btn-success btn-block"><i class="fa fa-search"></i> Buscar</button>
					</div>
				</form>
				<div id="devContenido" style="display:none">
					<div class="dev-info" id="devInfo"></div>
					<div class="table-responsive">
						<table class="table table-bordered table-condensed" id="devLineas">
							<thead>
								<tr><th>Producto</th><th class="text-center hidden-xs">Vendido</th><th class="text-center hidden-xs">Ya devuelto</th>
									<th class="text-center dev-col-cant">Devolver</th><th class="caja-num">Monto</th></tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
					<button type="button" class="btn btn-default btn-sm" id="btnDevTodo"><i class="fa fa-check-square-o"></i> Devolver todo</button>
					<div class="row" style="margin-top:12px">
						<div class="col-sm-7 form-group">
							<label for="devMotivo">Motivo</label>
							<input type="text" id="devMotivo" class="form-control" maxlength="150" placeholder="Ej: producto en mal estado">
						</div>
						<div class="col-sm-5 form-group">
							<label for="devFpago">Devolver el dinero en</label>
							<select id="devFpago" class="form-control"></select>
						</div>
					</div>
					<div class="caja-totales">
						<div class="fila total"><span>Total a devolver</span><b id="devTotal">$0</b></div>
					</div>
					<p class="text-muted small" style="margin:6px 0 0">Se devuelve lo pagado (con el descuento de la venta, sin propina) y el stock vuelve a bodega.</p>
				</div>
			</div>
			<div class="modal-footer">
				<div class="checkbox checkbox-success caja-chk-imprimir">
					<input type="checkbox" id="chkImprimirDev" class="chk-imprimir"><label for="chkImprimirDev"><i class="fa fa-print"></i> Imprimir ticket</label>
					<select class="cbo-papel" title="Ancho del papel de la impresora"><option value="80">80 mm</option><option value="58">58 mm</option></select>
				</div>
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-danger" id="btnConfirmarDevolucion" disabled><i class="fa fa-undo"></i> Registrar devolución</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Cliente</h4>
			</div>
			<div class="modal-body">
				<ul class="nav nav-tabs">
					<li class="active"><a href="#tabBuscarCliente" data-toggle="tab">Buscar</a></li>
					<li><a href="#tabNuevoCliente" data-toggle="tab" id="tabClienteForm">Nuevo</a></li>
				</ul>
				<div class="tab-content" style="padding-top:12px">
					<div class="tab-pane active" id="tabBuscarCliente">
						<input type="text" class="form-control" id="txtBuscarCliente" placeholder="Nombre o RUT" autocomplete="off">
						<div class="list-group" id="resultadosCliente" style="max-height:300px; overflow-y:auto; margin-top:8px"></div>
					</div>
					<div class="tab-pane" id="tabNuevoCliente">
						<form id="frmCliente">
							<input type="hidden" name="id_cliente" value="">
							<div class="row">
								<div class="col-sm-7 form-group"><label>Nombre / Razón social *</label><input name="nombre_cliente" class="form-control" maxlength="100" required></div>
								<div class="col-sm-5 form-group"><label>RUT *</label><input name="rut" class="form-control" placeholder="12.345.678-9" maxlength="12" required></div>
								<div class="col-sm-6 form-group"><label>Giro</label><input name="giro" class="form-control" maxlength="100"></div>
								<div class="col-sm-6 form-group"><label>Dirección</label><input name="direccion" class="form-control" maxlength="100"></div>
								<div class="col-sm-6 form-group"><label for="cboComuna">Comuna</label><select name="id_comuna" id="cboComuna" class="form-control"></select></div>
								<div class="col-sm-6 form-group"><label>Teléfono</label><input name="telefono" class="form-control" maxlength="30"></div>
								<div class="col-sm-6 form-group"><label>Correo</label><input name="mail" type="email" class="form-control" maxlength="100"></div>
							</div>
							<p class="text-muted">Para facturas el cliente necesita RUT, giro, dirección y comuna.</p>
							<button type="submit" class="btn btn-primary">Guardar y usar</button>
							<button type="button" class="btn btn-default" id="btnCancelarEdicion" style="display:none">Cancelar edición</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
