<div class="content-header">
	<div class="header-icon">
		<i class="pe-7s-cart"></i>
	</div>
	<div class="header-title">
		<h1>Caja</h1>
		<small id="infoCaja">Punto de venta</small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Procesos</a></li>
			<li class="active">Caja</li>
		</ol>
	</div>
</div>

<style>
	/* Colores del tema (component_ui): verde #558B2F, bordes #e1e6ef, texto #374767 */
	.caja .panel { margin-bottom: 20px; }
	.caja .panel-heading .panel-title { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
	.caja .panel-heading h4 { margin: 0; }
	.caja .panel-heading i { margin-right: 6px; }
	.caja .panel-heading .badge { background: #fff; color: #558B2F; font-size: 13px; }

	/* Resumen de la caja abierta */
	.caja-estado { display: flex; flex-wrap: wrap; background: #fff; border: 1px solid #e1e6ef; margin-bottom: 20px; }
	.caja-estado > div { flex: 1 1 150px; padding: 10px 15px; border-right: 1px solid #e1e6ef; }
	.caja-estado > div:last-child { border-right: 0; }
	.caja-estado small { display: block; color: #8a94a6; text-transform: uppercase; font-size: 11px; letter-spacing: .3px; }
	.caja-estado b { font-size: 16px; color: #374767; }
	.caja-estado .ok b { color: #558B2F; }

	/* Pestañas */
	.caja-tabs { background: #fff; border: 1px solid #e1e6ef; border-bottom: 0; margin-bottom: 0; padding: 0 10px; }
	.caja-tabs > li > a { color: #374767; padding: 12px 16px; }
	.caja-tabs > li.active > a, .caja-tabs > li.active > a:hover, .caja-tabs > li.active > a:focus { color: #558B2F; font-weight: 600; border-top: 2px solid #558B2F; }
	.caja-contenido { padding-top: 20px; border-left: 0; }
	/* El tema le pone borde propio a .tab-content .panel-body: dentro de un panel queda doble */
	.caja .tab-content .panel-body { border: 0; border-radius: 0; }

	/* Productos */
	.caja-buscar .input-group-addon { background: #fff; color: #558B2F; }
	.caja-buscar input { font-size: 15px; height: 42px; }
	.caja-familias { display: flex; gap: 6px; overflow-x: auto; padding: 12px 0 10px; scrollbar-width: thin; }
	.caja-familias .btn { white-space: nowrap; border-radius: 15px; font-size: 12px; padding: 4px 12px; }
	.caja-familias .btn-default { color: #374767; border-color: #e1e6ef; }
	.caja-productos { display: grid; grid-template-columns: repeat(auto-fill, minmax(135px, 1fr)); gap: 10px; max-height: 60vh; overflow-y: auto; padding: 2px; }
	.caja-producto { border: 1px solid #e1e6ef; border-left: 3px solid #558B2F; background: #fff; cursor: pointer; min-height: 84px; padding: 10px;
		display: flex; flex-direction: column; justify-content: space-between; text-align: left; transition: background .15s, box-shadow .15s; }
	.caja-producto:hover, .caja-producto:focus { background: #f4f8f0; box-shadow: 0 2px 6px rgba(55,71,103,.12); outline: none; }
	.caja-producto:active { transform: scale(.98); }
	.caja-producto .nombre { font-size: 12px; font-weight: 600; line-height: 1.3; color: #374767; word-break: break-word; text-transform: uppercase; }
	.caja-producto .opciones { font-size: 11px; color: #8a94a6; margin-top: 2px; }
	.caja-producto .precio { font-weight: 700; font-size: 14px; color: #558B2F; margin-top: 6px; }
	.caja-vacio { color: #8a94a6; padding: 30px 10px; text-align: center; grid-column: 1 / -1; }
	.caja-vacio i { display: block; font-size: 36px; margin-bottom: 6px; }

	/* Venta actual */
	.caja-carro .form-group { margin-bottom: 10px; }
	.caja-carro label { font-size: 12px; color: #8a94a6; text-transform: uppercase; margin-bottom: 3px; }
	.caja-lineas { border-top: 1px solid #e1e6ef; border-bottom: 1px solid #e1e6ef; max-height: 36vh; min-height: 90px; overflow-y: auto; margin: 6px -15px 12px; }
	.caja-linea { display: flex; align-items: center; gap: 8px; padding: 8px 15px; border-bottom: 1px solid #f0f2f5; }
	.caja-linea:last-child { border-bottom: 0; }
	.caja-linea .info { flex: 1; min-width: 0; }
	.caja-linea .nombre { font-weight: 600; color: #374767; font-size: 13px; }
	.caja-linea a.editar { color: #374767; border-bottom: 1px dashed #8a94a6; text-decoration: none; }
	.caja-linea .detalle-linea { font-size: 12px; color: #6b7a90; }
	.caja-linea .unitario { font-size: 12px; color: #8a94a6; }
	.caja-linea .subtotal { width: 80px; text-align: right; font-weight: 600; white-space: nowrap; }
	.caja-linea .cantidad { width: 108px; flex: 0 0 108px; }
	.caja-linea .cantidad input { text-align: center; padding: 4px; -moz-appearance: textfield; }
	.caja-linea .cantidad input::-webkit-inner-spin-button, .caja-linea .cantidad input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
	.caja-linea .quitar { color: #c9302c; background: none; border: 0; padding: 4px 6px; }
	.caja-linea .quitar:hover { color: #a02622; }
	.caja-carro-vacio { text-align: center; color: #8a94a6; padding: 22px 10px; }
	.caja-carro-vacio i { display: block; font-size: 34px; margin-bottom: 4px; }
	.caja-totales { background: #f7f9fa; border: 1px solid #e1e6ef; padding: 10px 12px; margin-bottom: 12px; }
	.caja-totales .fila { display: flex; justify-content: space-between; color: #6b7a90; font-size: 13px; }
	.caja-totales .total { border-top: 1px solid #e1e6ef; margin-top: 6px; padding-top: 6px; color: #374767; font-size: 22px; font-weight: 700; }
	.caja-totales .total span:last-child { color: #558B2F; }
	.caja-acciones { display: flex; gap: 10px; }
	.caja-acciones #btnLimpiar { flex: 0 0 56px; }
	.caja-acciones #btnCobrar { flex: 1; font-size: 18px; font-weight: 600; padding: 12px; }
	.caja-num { text-align: right; white-space: nowrap; }
	.caja-cliente { display: flex; gap: 6px; }
	.caja-cliente input { flex: 1; min-width: 0; background: #fff !important; cursor: pointer; }
	.caja-cliente .btn { flex: 0 0 40px; }

	/* Celular: barra fija para ir a la venta cuando el carro queda abajo de los productos */
	.caja-barra-movil { display: none; }
	@media (max-width: 991px) {
		.caja-barra-movil.visible { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 1030; align-items: center; justify-content: space-between;
			background: #558B2F; color: #fff; padding: 12px 16px; font-size: 16px; font-weight: 600; box-shadow: 0 -2px 8px rgba(0,0,0,.15); cursor: pointer; }
		.caja.con-barra { padding-bottom: 60px; }
	}

	/* Tablas y paneles de las otras pestañas */
	.caja .table > thead > tr > th { background: #f7f9fa; color: #374767; font-weight: 600; border-bottom-width: 1px; }
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
	.pago-fila { display: flex; gap: 6px; align-items: center; margin-bottom: 6px; }
	.pago-fila .nombre { flex: 1; font-weight: 600; }
	.pago-fila input { width: 140px; text-align: right; }
	.pago-resumen { font-size: 16px; }
	.pago-resumen .monto { font-weight: bold; }
	.pago-propina { background: #f7f9fa; border: 1px solid #e1e6ef; padding: 8px 10px; margin-bottom: 12px; }
	.pago-propina input[type=number] { width: 120px; display: inline-block; text-align: right; }
	#botonesFpago .btn { min-width: 110px; }
	.btn-pago-exacto { color: #558B2F; border-color: #558B2F; font-weight: 600; }
	.btn-pago-exacto:hover, .btn-pago-exacto:focus { color: #fff; background: #558B2F; border-color: #3C6F18; }
	.pago-fila input { font-size: 16px; font-weight: 600; }
	.opcion-lista .checkbox { margin: 6px 0; }
	.opcion-lista .checkbox label { font-size: 14px; }
	.opcion-lista .valor { color: #558B2F; font-weight: bold; }
	#modalAutorizacion { z-index: 1060; }
	.modal-header.btn-success .close { color: #fff; opacity: .85; text-shadow: none; }

	@media (max-width: 991px) {
		.caja-productos { max-height: 42vh; }
	}
	@media (max-width: 480px) {
		.caja-tabs > li > a { padding: 10px 8px; font-size: 12px; }
		.caja-productos { grid-template-columns: repeat(2, 1fr); }
		.caja-linea .subtotal { width: 64px; }
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
			<div class="caja-estado">
				<div><small>Caja</small><b id="estCaja">-</b></div>
				<div><small>Apertura</small><b id="estApertura">-</b></div>
				<div><small>Fondo inicial</small><b id="estFondo">$0</b></div>
				<div class="ok"><small>Ventas</small><b id="estVentas">$0</b></div>
			</div>

			<ul class="nav nav-tabs caja-tabs">
				<li class="active"><a href="#tabVenta" data-toggle="tab"><i class="pe-7s-cart"></i> Venta</a></li>
				<li><a href="#tabVentas" data-toggle="tab" id="lnkVentas"><i class="pe-7s-note2"></i> Ventas</a></li>
				<li><a href="#tabMovimientos" data-toggle="tab" id="lnkMovimientos"><i class="pe-7s-cash"></i> Ingresos / Retiros</a></li>
				<li><a href="#tabCierre" data-toggle="tab" id="lnkCierre"><i class="pe-7s-lock"></i> Cierre</a></li>
			</ul>

			<div class="tab-content caja-contenido">
				<!-- ===================== VENTA ===================== -->
				<div class="tab-pane fade active in" id="tabVenta">
					<div class="row">
						<div class="col-md-7">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-search"></i>Productos</h4></div>
								</div>
								<div class="panel-body">
									<div class="input-group caja-buscar">
										<span class="input-group-addon"><i class="fa fa-search"></i></span>
										<input type="text" id="buscarProducto" class="form-control" placeholder="Buscar por nombre o escanear código de barras" autocomplete="off">
									</div>
									<div class="caja-familias" id="familias"></div>
									<div class="caja-productos" id="productos"></div>
								</div>
							</div>
						</div>

						<div class="col-md-5">
							<div class="panel panel-success lobidisable caja-carro">
								<div class="panel-heading">
									<div class="panel-title"><h4><i class="pe-7s-cart"></i>Venta actual</h4><span class="badge" id="cantItems">0</span></div>
								</div>
								<div class="panel-body">
									<div class="row">
										<div class="col-xs-7 form-group">
											<label for="cboDocumento">Documento</label>
											<select id="cboDocumento" class="form-control"></select>
										</div>
										<div class="col-xs-5 form-group">
											<label for="cboLista">Lista de precios</label>
											<select id="cboLista" class="form-control"></select>
										</div>
									</div>
									<div class="form-group">
										<label for="txtCliente">Cliente</label>
										<div class="caja-cliente">
											<input type="text" id="txtCliente" class="form-control" readonly placeholder="Público general">
											<button class="btn btn-default" id="btnBuscarCliente" title="Buscar o crear cliente"><i class="fa fa-search"></i></button>
											<button class="btn btn-default" id="btnQuitarCliente" title="Quitar cliente"><i class="fa fa-times"></i></button>
										</div>
									</div>

									<div class="caja-lineas" id="lineas"></div>

									<div class="row">
										<div class="col-xs-6 form-group">
											<label for="txtDescuento">Descuento</label>
											<div class="input-group">
												<input type="number" min="0" step="1" id="txtDescuento" class="form-control" value="0">
												<span class="input-group-btn">
													<button class="btn btn-default active" data-tipo-desc="monto" type="button">$</button>
													<button class="btn btn-default" data-tipo-desc="porc" type="button">%</button>
												</span>
											</div>
										</div>
										<div class="col-xs-6 form-group">
											<label for="txtObservacion">Observación</label>
											<input type="text" id="txtObservacion" maxlength="150" class="form-control" placeholder="Opcional">
										</div>
									</div>

									<div class="caja-totales">
										<div class="fila"><span>Subtotal</span><span id="lblSubtotal">$0</span></div>
										<div class="fila"><span>Descuento</span><span id="lblDescuento">$0</span></div>
										<div class="fila"><span>Neto</span><span id="lblNeto">$0</span></div>
										<div class="fila"><span>IVA</span><span id="lblIva">$0</span></div>
										<div class="fila total"><span>Total</span><span id="lblTotal">$0</span></div>
									</div>

									<div class="caja-acciones">
										<button class="btn btn-default btn-lg" id="btnLimpiar" title="Vaciar venta"><i class="fa fa-trash"></i></button>
										<button class="btn btn-success btn-lg" id="btnCobrar" disabled><i class="fa fa-money"></i> Cobrar <span id="lblCobrar"></span></button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- ===================== VENTAS ===================== -->
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
<div class="modal fade" id="modalPago" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Cobrar <span id="pagoTotal"></span></h4>
			</div>
			<div class="modal-body">
				<div class="pago-propina" id="bloquePropina" style="display:none">
					<div class="checkbox checkbox-success" style="display:inline-block; margin:0 10px 0 0">
						<input type="checkbox" id="chkPropina"><label for="chkPropina" style="font-weight:bold">Propina <span id="lblPorPropina"></span></label>
					</div>
					<input type="number" min="0" step="1" class="form-control input-sm" id="txtPropina" value="0">
					<span class="text-muted" style="margin-left:8px">Total a pagar: <b id="pagoTotalConPropina"></b></span>
				</div>
				<p class="text-muted">Toca una forma de pago para cargar el saldo pendiente. Puedes combinar varias.</p>
				<div id="botonesFpago" style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px;"></div>
				<div id="filasPago"></div>
				<hr>
				<div class="pago-resumen" style="display:flex; justify-content:space-between; align-items:center; gap:10px">
					<div>
						<div>Pagado: <span class="monto" id="pagoPagado">$0</span></div>
						<div>Falta: <span class="monto text-danger" id="pagoFalta">$0</span></div>
						<div>Vuelto: <span class="monto text-success" id="pagoVuelto">$0</span></div>
					</div>
					<button type="button" class="btn btn-default btn-lg btn-pago-exacto" id="btnPagoExacto" title="El último pago cubre exactamente el total, sin vuelto">
						<i class="fa fa-check"></i> Pago exacto
					</button>
				</div>
			</div>
			<div class="modal-footer">
				<div class="checkbox checkbox-success caja-chk-imprimir">
					<input type="checkbox" id="chkImprimirVenta" class="chk-imprimir"><label for="chkImprimirVenta"><i class="fa fa-print"></i> Imprimir ticket</label>
					<select class="cbo-papel" title="Ancho del papel de la impresora"><option value="80">80 mm</option><option value="58">58 mm</option></select>
				</div>
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-success btn-lg" id="btnConfirmarPago" disabled>Confirmar venta</button>
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
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-body text-center">
				<h3 style="color:#37a000; margin-top:5px;"><i class="fa fa-check-circle"></i> <span id="ventaOkTitulo">Venta registrada</span></h3>
				<p id="ventaOkDoc" style="font-size:16px"></p>
				<p id="ventaOkVuelto" style="font-size:22px; font-weight:bold"></p>
				<p id="ventaOkAviso" class="alert alert-danger" style="display:none; text-align:left; padding:8px 10px"></p>
				<div class="btn-group btn-group-justified" style="margin-bottom:10px">
					<a class="btn btn-default" id="ventaOkTicket" target="_blank"><i class="fa fa-print"></i> Ticket</a>
					<a class="btn btn-default" id="ventaOkPdf" target="_blank"><i class="fa fa-file-pdf-o"></i> PDF</a>
				</div>
				<button class="btn btn-success btn-block" data-dismiss="modal" id="btnNuevaVenta">Nueva venta</button>
			</div>
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
