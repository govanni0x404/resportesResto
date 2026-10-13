<div class="content-header">
	<div class="header-icon">
		<i class="pe-7s-cart"></i>
	</div>
	<div class="header-title">
		<h1>Caja</h1>
		<small id="infoCaja"></small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Procesos</a></li>
			<li class="active">Caja</li>
		</ol>
	</div>
</div>

<style>
	#panelCaja .nav-tabs { margin-bottom: 15px; }
	.caja-buscar { display: flex; gap: 8px; margin-bottom: 10px; }
	.caja-buscar input { font-size: 16px; height: 40px; }
	.caja-familias { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
	.caja-familias .btn { border-radius: 16px; }
	.caja-productos { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 8px; max-height: 62vh; overflow-y: auto; padding: 2px; }
	.caja-producto { border: 1px solid #ddd; border-radius: 6px; padding: 10px 8px; background: #fff; cursor: pointer; min-height: 78px;
		display: flex; flex-direction: column; justify-content: space-between; text-align: left; transition: border-color .15s, box-shadow .15s; }
	.caja-producto:hover, .caja-producto:focus { border-color: #37a000; box-shadow: 0 0 0 2px rgba(55,160,0,.15); outline: none; }
	.caja-producto .nombre { font-size: 13px; line-height: 1.25; color: #333; word-break: break-word; }
	.caja-producto .precio { font-weight: bold; color: #37a000; margin-top: 6px; }
	.caja-vacio { color: #999; padding: 30px; text-align: center; grid-column: 1 / -1; }
	.caja-carro { border: 1px solid #ddd; border-radius: 6px; background: #fff; padding: 12px; }
	.caja-carro .form-group { margin-bottom: 8px; }
	.caja-lineas { max-height: 34vh; overflow-y: auto; margin: 8px 0; }
	.caja-lineas table { margin-bottom: 0; }
	.caja-lineas td { vertical-align: middle !important; }
	.caja-lineas .cant { width: 70px; text-align: center; padding: 2px 4px; height: 28px; }
	.caja-totales td { padding: 2px 0; }
	.caja-totales .total td { font-size: 22px; font-weight: bold; }
	.caja-cobrar { font-size: 20px; padding: 12px; }
	.caja-cliente { display: flex; gap: 6px; }
	.caja-cliente input { background: #f9f9f9; }
	.pago-fila { display: flex; gap: 6px; align-items: center; margin-bottom: 6px; }
	.pago-fila .nombre { flex: 1; }
	.pago-fila input { width: 140px; text-align: right; }
	.pago-resumen { font-size: 16px; }
	.pago-resumen .monto { font-weight: bold; }
	.caja-num { text-align: right; white-space: nowrap; }
	#tablaVentas tr.anulada td { color: #aaa; text-decoration: line-through; }
	#tablaVentas tr.anulada td.acciones { text-decoration: none; }
	.cierre-diferencia { font-size: 18px; font-weight: bold; }
	.caja-producto .opciones { font-size: 11px; color: #888; margin-top: 2px; }
	.caja-lineas .detalle-linea { font-size: 12px; color: #777; }
	.caja-lineas a.editar { color: inherit; border-bottom: 1px dashed #aaa; text-decoration: none; }
	.opcion-lista .checkbox { margin: 4px 0; }
	.opcion-lista .valor { color: #37a000; font-weight: bold; }
	.pago-propina { background: #f7f7f7; border-radius: 6px; padding: 8px 10px; margin-bottom: 12px; }
	.pago-propina input[type=number] { width: 120px; display: inline-block; text-align: right; }
	#modalAutorizacion { z-index: 1060; }
	@media (max-width: 991px) {
		.caja-productos { max-height: 40vh; }
		.caja-carro { margin-top: 15px; }
	}
</style>

<div class="row">
	<div class="col-xs-12">

		<!-- Sin caja abierta -->
		<div id="panelApertura" class="panel panel-success" style="display:none; max-width: 480px; margin: 20px auto;">
			<div class="panel-heading">
				<div class="panel-title"><h4>Abrir caja</h4></div>
			</div>
			<div class="panel-body">
				<p>No tienes una caja abierta. Ingresa el fondo inicial (sencillo) para comenzar a vender.</p>
				<div class="form-group">
					<label for="fondoInicial">Fondo inicial</label>
					<input type="number" min="0" step="1" class="form-control input-lg" id="fondoInicial" value="0">
				</div>
				<button id="btnAbrirCaja" class="btn btn-success btn-lg btn-block">Abrir caja</button>
			</div>
		</div>

		<!-- Caja abierta -->
		<div id="panelCaja" style="display:none;">
			<ul class="nav nav-tabs">
				<li class="active"><a href="#tabVenta" data-toggle="tab"><i class="pe-7s-cart"></i> Venta</a></li>
				<li><a href="#tabVentas" data-toggle="tab" id="lnkVentas"><i class="pe-7s-note2"></i> Ventas de la caja</a></li>
				<li><a href="#tabMovimientos" data-toggle="tab" id="lnkMovimientos"><i class="pe-7s-cash"></i> Ingresos / Retiros</a></li>
				<li><a href="#tabCierre" data-toggle="tab" id="lnkCierre"><i class="pe-7s-lock"></i> Cierre</a></li>
			</ul>

			<div class="tab-content">
				<!-- ===================== VENTA ===================== -->
				<div class="tab-pane fade active in" id="tabVenta">
					<div class="row">
						<div class="col-md-7">
							<div class="caja-buscar">
								<input type="text" id="buscarProducto" class="form-control" placeholder="Buscar producto o escanear código de barras" autocomplete="off">
							</div>
							<div class="caja-familias" id="familias"></div>
							<div class="caja-productos" id="productos"></div>
						</div>

						<div class="col-md-5">
							<div class="caja-carro">
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
									<label>Cliente</label>
									<div class="caja-cliente">
										<input type="text" id="txtCliente" class="form-control" readonly placeholder="Público general">
										<button class="btn btn-default" id="btnBuscarCliente" title="Buscar cliente"><i class="fa fa-search"></i></button>
										<button class="btn btn-default" id="btnQuitarCliente" title="Quitar cliente"><i class="fa fa-times"></i></button>
									</div>
								</div>

								<div class="caja-lineas">
									<table class="table table-condensed">
										<thead>
											<tr><th>Producto</th><th class="text-center">Cant.</th><th class="caja-num">Total</th><th></th></tr>
										</thead>
										<tbody id="lineas"></tbody>
									</table>
								</div>

								<div class="row">
									<div class="col-xs-7 form-group">
										<label for="txtDescuento">Descuento</label>
										<div class="input-group">
											<input type="number" min="0" step="1" id="txtDescuento" class="form-control" value="0">
											<span class="input-group-btn">
												<button class="btn btn-default active" data-tipo-desc="monto" type="button">$</button>
												<button class="btn btn-default" data-tipo-desc="porc" type="button">%</button>
											</span>
										</div>
									</div>
									<div class="col-xs-5 form-group">
										<label for="txtObservacion">Observación</label>
										<input type="text" id="txtObservacion" maxlength="150" class="form-control">
									</div>
								</div>

								<table class="caja-totales" style="width:100%">
									<tr><td>Subtotal</td><td class="caja-num" id="lblSubtotal">$0</td></tr>
									<tr><td>Descuento</td><td class="caja-num" id="lblDescuento">$0</td></tr>
									<tr><td>Neto</td><td class="caja-num" id="lblNeto">$0</td></tr>
									<tr><td>IVA</td><td class="caja-num" id="lblIva">$0</td></tr>
									<tr class="total"><td>Total</td><td class="caja-num" id="lblTotal">$0</td></tr>
								</table>
								<br>
								<div class="row">
									<div class="col-xs-4">
										<button class="btn btn-default btn-block caja-cobrar" id="btnLimpiar" title="Vaciar venta"><i class="fa fa-trash"></i></button>
									</div>
									<div class="col-xs-8">
										<button class="btn btn-success btn-block caja-cobrar" id="btnCobrar" disabled>Cobrar</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- ===================== VENTAS ===================== -->
				<div class="tab-pane fade" id="tabVentas">
					<div class="table-responsive">
						<table class="table table-striped table-hover" id="tablaVentas">
							<thead>
								<tr><th>Hora</th><th>Documento</th><th>Folio</th><th>Cliente</th><th>Pago</th><th class="caja-num">Total</th><th></th></tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</div>

				<!-- ===================== MOVIMIENTOS ===================== -->
				<div class="tab-pane fade" id="tabMovimientos">
					<div class="row">
						<div class="col-md-4">
							<div class="panel panel-default">
								<div class="panel-body">
									<div class="form-group">
										<label>Tipo</label><br>
										<label class="radio-inline"><input type="radio" name="tipoMov" value="ingreso" checked> Ingreso</label>
										<label class="radio-inline"><input type="radio" name="tipoMov" value="egreso"> Retiro / Gasto</label>
									</div>
									<div class="form-group">
										<label for="montoMov">Monto</label>
										<input type="number" min="1" step="1" class="form-control" id="montoMov">
									</div>
									<div class="form-group">
										<label for="obsMov">Motivo</label>
										<input type="text" maxlength="100" class="form-control" id="obsMov">
									</div>
									<button class="btn btn-primary btn-block" id="btnMovimiento">Registrar</button>
								</div>
							</div>
						</div>
						<div class="col-md-8">
							<div class="table-responsive">
								<table class="table table-striped" id="tablaMovimientos">
									<thead><tr><th>Fecha</th><th>Motivo</th><th class="caja-num">Ingreso</th><th class="caja-num">Retiro</th></tr></thead>
									<tbody></tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<!-- ===================== CIERRE ===================== -->
				<div class="tab-pane fade" id="tabCierre">
					<div class="row">
						<div class="col-md-6">
							<table class="table table-condensed">
								<tbody id="resumenCierre"></tbody>
							</table>
						</div>
						<div class="col-md-6">
							<div class="panel panel-default">
								<div class="panel-heading"><h4 style="margin:0">Lo que hay en caja</h4></div>
								<div class="panel-body">
									<div class="form-group">
										<label for="efectivoContado">Efectivo contado (incluye el fondo inicial)</label>
										<input type="number" min="0" step="1" class="form-control input-lg" id="efectivoContado">
									</div>
									<div id="declaradoOtros"></div>
									<p>Esperado: <b id="lblEsperado">$0</b> &nbsp; Declarado: <b id="lblDeclarado">$0</b></p>
									<p class="cierre-diferencia">Diferencia: <span id="lblDiferencia">$0</span></p>
									<button class="btn btn-danger btn-lg btn-block" id="btnCerrarCaja"><i class="pe-7s-lock"></i> Cerrar caja</button>
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
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Cobrar <span id="pagoTotal"></span></h4>
			</div>
			<div class="modal-body">
				<div class="pago-propina" id="bloquePropina" style="display:none">
					<label class="checkbox-inline" style="font-weight:bold">
						<input type="checkbox" id="chkPropina"> Propina <span id="lblPorPropina"></span>
					</label>
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
					<button type="button" class="btn btn-info btn-lg" id="btnPagoExacto" title="El último pago cubre exactamente el total, sin vuelto">
						<i class="fa fa-check"></i> Pago exacto
					</button>
				</div>
			</div>
			<div class="modal-footer">
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
			<div class="modal-header">
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
				<div class="modal-header">
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
				<h3 style="color:#37a000; margin-top:5px;"><i class="fa fa-check-circle"></i> Venta registrada</h3>
				<p id="ventaOkDoc" style="font-size:16px"></p>
				<p id="ventaOkVuelto" style="font-size:22px; font-weight:bold"></p>
				<div class="btn-group btn-group-justified" style="margin-bottom:10px">
					<a class="btn btn-default" id="ventaOkTicket" target="_blank"><i class="fa fa-print"></i> Ticket</a>
					<a class="btn btn-default" id="ventaOkPdf" target="_blank"><i class="fa fa-file-pdf-o"></i> PDF</a>
				</div>
				<button class="btn btn-success btn-block" data-dismiss="modal" id="btnNuevaVenta">Nueva venta</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Cliente</h4>
			</div>
			<div class="modal-body">
				<ul class="nav nav-tabs">
					<li class="active"><a href="#tabBuscarCliente" data-toggle="tab">Buscar</a></li>
					<li><a href="#tabNuevoCliente" data-toggle="tab">Nuevo</a></li>
				</ul>
				<div class="tab-content" style="padding-top:12px">
					<div class="tab-pane active" id="tabBuscarCliente">
						<input type="text" class="form-control" id="txtBuscarCliente" placeholder="Nombre o RUT" autocomplete="off">
						<div class="list-group" id="resultadosCliente" style="max-height:300px; overflow-y:auto; margin-top:8px"></div>
					</div>
					<div class="tab-pane" id="tabNuevoCliente">
						<form id="frmCliente">
							<div class="row">
								<div class="col-sm-7 form-group"><label>Nombre / Razón social *</label><input name="nombre_cliente" class="form-control" maxlength="100" required></div>
								<div class="col-sm-5 form-group"><label>RUT</label><input name="rut" class="form-control" placeholder="12.345.678-9" maxlength="12"></div>
								<div class="col-sm-6 form-group"><label>Giro</label><input name="giro" class="form-control" maxlength="100"></div>
								<div class="col-sm-6 form-group"><label>Dirección</label><input name="direccion" class="form-control" maxlength="100"></div>
								<div class="col-sm-6 form-group"><label>Teléfono</label><input name="telefono" class="form-control" maxlength="30"></div>
								<div class="col-sm-6 form-group"><label>Correo</label><input name="mail" type="email" class="form-control" maxlength="100"></div>
							</div>
							<p class="text-muted">Para facturas el cliente necesita RUT, giro y dirección.</p>
							<button type="submit" class="btn btn-primary">Guardar y usar</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
