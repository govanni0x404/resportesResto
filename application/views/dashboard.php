<style>
	/* ===== Dashboard "Mis Ventas" (solo estilos, prefijo .dash-) ===== */
	.dash-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 14px;
		background: transparent !important; border: 0 !important; box-shadow: none !important; padding: 6px 0 4px !important; margin-bottom: 18px !important; }
	.dash-header .header-icon { display: none; }
	.dash-header .header-title { flex: 1 1 260px; min-width: 0; }
	.dash-header .dash-eyebrow { display: block; margin: 0 0 4px; font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--t-texto-tenue, #A89A8C); }
	.dash-header .header-title h1 { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -.3px; line-height: 1.2; color: var(--t-texto, #3A2A22); }
	.dash-header .header-title h1:empty::before { content: "Mis ventas"; }
	.dash-header .header-title h4 { margin: 4px 0 0; font-size: 14px; font-weight: 400; color: var(--t-texto-suave, #7A6A5E); font-family: inherit; }
	.dash-header .header-title h4:empty { display: none; }
	.dash-header .breadcrumb:empty { display: none; }
	.dash-header .dash-header-acciones { flex: 0 0 auto; }
	.dash-btn-exportar { display: inline-flex; align-items: center; gap: 7px; height: 38px; padding: 0 16px; border-radius: 9px;
		background: #fff !important; border: 1px solid var(--t-borde, #EADFD2) !important; color: var(--t-texto, #3A2A22) !important; font-weight: 600; box-shadow: none; }
	.dash-btn-exportar:hover, .dash-btn-exportar:focus { border-color: var(--t-primario-borde, #EFD2C6) !important; color: var(--t-primario, #B4432E) !important; background: #fff !important; }
	.dash-btn-exportar i { color: var(--t-texto-suave, #7A6A5E); font-size: 15px; }
	.dash-btn-exportar:hover i { color: var(--t-primario, #B4432E); }

	/* Filtros: tarjeta blanca con grilla de 2 columnas */
	.dash .dash-filtros { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.2fr) auto; gap: 14px 16px; align-items: end;
		margin: 0 0 22px; padding: 18px 20px; background: #fff; border: 1px solid var(--t-borde, #EADFD2); border-radius: 14px; box-shadow: var(--t-sombra, 0 1px 2px rgba(58, 42, 34, .05)); }
	.dash .dash-filtros .dash-campo { min-width: 0; }
	.dash .dash-filtros label { display: block; margin: 0 0 6px; font-size: 12px; font-weight: 600; color: var(--t-texto-suave, #7A6A5E); }
	.dash .dash-filtros .control-group, .dash .dash-filtros .controls { margin: 0; }
	.dash .dash-filtros .form-control, .dash .dash-filtros .select2-container .select2-selection--single { height: 40px !important; border-radius: 9px; }
	.dash .dash-filtros .input-group-addon { border-radius: 9px 0 0 9px; }
	.dash .dash-filtros .input-group .form-control { border-radius: 0 9px 9px 0; }
	.dash .dash-filtros #btnTest { display: flex; align-items: center; justify-content: center; gap: 8px; width: auto !important; min-width: 130px; padding: 0 22px; height: 40px; margin: 0; box-shadow: none;
		border-radius: 9px; font-weight: 600; font-size: 14px; background: var(--t-primario, #B4432E); border-color: var(--t-primario, #B4432E); color: #fff; }
	.dash .dash-filtros #btnTest:hover, .dash .dash-filtros #btnTest:focus { background: var(--t-primario-osc, #933524); border-color: var(--t-primario-osc, #933524); }

	/* Título de sección */
	.dash .dash-seccion-titulo { display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 6px 14px; margin: 0 0 12px; }
	.dash .dash-seccion-titulo h3 { margin: 0; font-size: 17px; font-weight: 700; color: var(--t-texto, #3A2A22); font-family: inherit; }
	.dash .dash-seccion-titulo small { font-size: 12px; color: var(--t-texto-suave, #7A6A5E); }

	/* KPI: grilla de 3 columnas */
	.dash .dash-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin: 0 0 22px; }
	.dash .dash-kpi { position: relative; min-width: 0; padding: 16px 18px; background: #fff; border: 1px solid var(--t-borde, #EADFD2); border-radius: 14px;
		box-shadow: var(--t-sombra, 0 1px 2px rgba(58, 42, 34, .05)); }
	.dash .dash-kpi[onclick] { cursor: pointer; transition: border-color .15s, box-shadow .15s; }
	.dash .dash-kpi[onclick]:hover { border-color: var(--t-primario-borde, #EFD2C6); box-shadow: var(--t-sombra-alta, 0 12px 32px rgba(58, 42, 34, .14)); }
	.dash .dash-kpi-etiqueta { margin: 0 0 8px; padding-right: 34px; font-size: 12px; font-weight: 500; color: var(--t-texto-suave, #7A6A5E); }
	.dash .dash-kpi-monto { margin: 0; font-size: 24px; font-weight: 700; line-height: 1.2; color: var(--t-texto, #3A2A22); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
	.dash .dash-kpi-icono { position: absolute; top: 14px; right: 16px; font-size: 22px; line-height: 1; color: #C9A27A; }
	.dash .dash-kpi.destacado { background: var(--t-primario-suave, #FBEEE8); border-color: var(--t-primario-suave, #FBEEE8); box-shadow: none; }
	.dash .dash-kpi.destacado .dash-kpi-monto { color: var(--t-primario, #B4432E); }
	.dash .dash-kpi.destacado .dash-kpi-icono { color: var(--t-primario, #B4432E); }

	/* Paneles de resumen */
	.dash .dash-resumen { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin: 0 0 16px; }
	.dash .dash-resumen > .panel, .dash .dash-ancho > .panel { margin: 0; min-width: 0; }
	.dash .dash-ancho { margin: 0 0 16px; }
	.dash .dash-panel > .panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 18px; }
	.dash .dash-panel > .panel-heading .panel-title { display: flex; align-items: center; gap: 8px; }
	.dash .dash-panel > .panel-heading .panel-title i { margin: 0; font-size: 18px; color: var(--t-texto-tenue, #A89A8C) !important; }
	.dash .dash-panel > .panel-heading h4 { margin: 0; font-size: 15px; font-weight: 600; color: var(--t-texto, #3A2A22); font-family: inherit; }
	.dash .dash-cuerpo .panel.dash-panel > .table-responsive { margin: 0 !important; padding: 4px 14px 10px !important; border: 0; }
	.dash .dash-panel .table { margin: 0; }
	.dash .dash-tabla > thead > tr > th { padding: 10px 8px; font-size: 11px; font-weight: 600; letter-spacing: .6px; text-transform: uppercase;
		color: var(--t-texto-suave, #7A6A5E) !important; background: #fff !important; border-bottom: 1px solid var(--t-borde, #EADFD2) !important; }
	.dash .dash-tabla > tbody > tr > td { padding: 10px 8px; height: auto !important; vertical-align: middle; color: var(--t-texto, #3A2A22); border-top: 1px solid var(--t-borde-suave, #F0E8DD); }
	.dash .dash-tabla > thead > tr > th, .dash .dash-tabla > tbody > tr > td { text-align: right !important; }
	.dash .dash-tabla > thead > tr > th:first-child, .dash .dash-tabla > tbody > tr > td:first-child { text-align: left !important; }
	.dash .dash-tabla > tbody > tr > td { font-variant-numeric: tabular-nums; }
	.dash .dash-tabla > tbody > tr > td h4 { margin: 0; font-size: 14px; font-family: inherit; color: var(--t-texto, #3A2A22); }
	.dash .dash-tabla > tbody > tr:last-child > td[colspan] ~ td h4 { color: var(--t-texto, #3A2A22); }
	.dash .dash-tabla > tbody > tr > td[colspan] h4 { font-size: 12px; letter-spacing: .6px; color: var(--t-texto-suave, #7A6A5E); }

	/* Chips y badges */
	.dash .dash-chip { display: inline-block; padding: 3px 9px; border-radius: 7px; background: var(--t-primario-suave, #FBEEE8); color: var(--t-primario, #B4432E); font-size: 12px; font-weight: 600; line-height: 1.5; }
	.dash .dash-badge { display: inline-block; padding: 3px 10px; border-radius: 7px; background: var(--t-primario-suave, #FBEEE8); color: var(--t-primario, #B4432E); font-size: 12px; font-weight: 600; white-space: nowrap; }
	.dash .dash-badge:empty { display: none; }

	/* Ventas por pagar: botón "Ver detalle" pequeño azul suave */
	.dash #tableVentasPorPagar .btn { display: inline-flex; align-items: center; margin: 0 !important; padding: 4px 11px; border-radius: 7px; font-size: 12px; font-weight: 600;
		background: var(--t-primario-suave, #FBEEE8) !important; border: 1px solid var(--t-primario-suave, #FBEEE8) !important; color: var(--t-primario, #B4432E) !important; box-shadow: none; }
	.dash #tableVentasPorPagar .btn:hover, .dash #tableVentasPorPagar .btn:focus { background: #F7E0D6 !important; border-color: var(--t-primario-borde, #EFD2C6) !important; }
	.dash #tableVentasPorPagar .btn .btn-label { display: none; }

	/* Resto del contenido (familias, mesas, atención) */
	.dash .tab-content .panel-body { border: 0; border-radius: 0; }
	.dash .dash-cuerpo { background: transparent; padding: 0; margin: 0 -15px; }
	.dash .dash-cuerpo > .dash-bloque { margin-left: 15px; margin-right: 15px; }
	.dash .dash-cuerpo .panel > .table-responsive { padding: 10px 14px !important; margin-top: 0 !important; }
	.dash .dash-cuerpo #grafico { padding: 18px; }
	.dash .dash-cuerpo #grafico canvas { max-width: 100%; }
	.rep-sin-marco { border: 0; box-shadow: none; background: transparent; margin-bottom: 0; }
	.rep-sin-marco > .panel-body { padding: 0; }
	.dash #totalFamilia, .dash #montoMesa, .dash #totalVentasGarzon, .dash #totalPropinasGarzon {
		display: inline-block; margin: 4px 0 8px; padding: 6px 12px; border-radius: 7px; background: var(--t-primario-suave, #FBEEE8);
		color: var(--t-primario, #B4432E); font-weight: 700; font-size: 13px; }
	.dash #totalFamilia:empty, .dash #montoMesa:empty, .dash #totalVentasGarzon:empty, .dash #totalPropinasGarzon:empty { display: none; }

	@media (max-width: 1199px) {
		.dash .dash-filtros { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		.dash .dash-filtros #btnTest { width: 100% !important; }
	}
	@media (max-width: 991px) {
		.dash .dash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
		.dash .dash-resumen { grid-template-columns: minmax(0, 1fr); }
	}
	@media (max-width: 767px) {
		.dash-header .header-title h1 { font-size: 22px; }
		.dash-header .dash-header-acciones, .dash-btn-exportar { width: 100%; justify-content: center; }
		.dash .dash-filtros { grid-template-columns: minmax(0, 1fr); padding: 16px; }
		.dash .dash-kpi { padding: 14px 16px; }
		.dash .dash-kpi-monto { font-size: 21px; }
	}
	@media (max-width: 479px) {
		.dash .dash-kpis { grid-template-columns: minmax(0, 1fr); }
	}
</style>
<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
//$fechaActual = date('Y-m-d');
$fechaActual = date('d-m-Y');
?>
<div class="content-header dash-header">
	<div class="header-icon">
		<i class="pe-7s-home"></i>
	</div>
	<div class="header-title">
		<span class="dash-eyebrow">Panel de administración</span>
		<h1 id="local"></h1>
		<!--nombre fantasia-->
		<h4 id="lblNombreCliente"></h4>
		<ol class="breadcrumb"></ol>
		<!--<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Home</a></li>
			<li class="active">Dashboard</li>-->
	</div>
	<div class="dash-header-acciones">
		<!--
		<label>Opciones:</label>
		<select onclick="VercierreCajaPDF()" style="width:100%; height:34px;" id="opciones" nombre="opciones" class="form-control select2">
			<option value="1"><a onclick="VercierreCajaPDF()" href="#ExportarPdf" data-toggle="tab"><i class="pe-7s-monitor"></i> Exportar PDF</a></option>
		</select>-->
		<button onclick="PDFDetalleCaja()" class="btn btn-default dash-btn-exportar"><i
				class="fa fa-file-pdf-o"></i> Exportar PDF</button>
	</div>
</div> <!-- /.Content Header (Page header) -->

<div class="row dash">

	<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
		<!-- Filtros -->

		<div class="dash-filtros">
			<div class="dash-campo">
				<label>Sección</label>
				<select style="width:100%; height:34px;" id="cboGraficos" nombre="cboGraficos"
					class="form-control select2">
					<option value="1"><a href="#cajaActual" data-toggle="tab"><i class="pe-7s-monitor"></i> Detalle
							Caja</a></option>

				</select>
			</div>
			<div class="dash-campo">
				<label>Cajas</label>
				<select class="form-control" id="cboCajas" nombre="cboCajas">
					<option selected value="">Todas Las Cajas Del Día</option>
				</select>
			</div>
			<!-- <div class="col-md-4">
				<br>
				<label>Cajas:</label>
				<select style="width:100%; height:34px;" id="cboCajas" nombre="cboCajas" class="form-control" aria-label="Default select example">
					<option selected value="">Seleccione</option>
				</select>
			</div>
			<div class="col-md-4" id="divBodegas">
				<br>
				<label>Bodegas:</label>
				<select style="width:100%; height:34px;" id="cboBodega" nombre="cboBodega" class="form-control">
					<option selected value="">Seleccione</option>
				</select>
			</div> -->
			<!--
			<div class="col-md-2">
				<input type="date" class="hidden" id="pruebaFecha" value="<?php echo $fechaActual ?>" >
				<br>

				<label>Fecha Caja:</label>
				<input onpaste="return false;" value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" ondrop="return false;" autocomplete="off" type="date" name="fechaCaja" id="fechaCaja" onkeydown="return false" class="form-control">
			</div>-->
			<div class="dash-campo">
				<div class="control-group">
					<div class="controls">
						<label>Período</label>
						<div class="input-prepend input-group">
							<span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
							<input type="text" class="hidden" id="pruebaFecha"
								value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>">
							<input type="text" id="date-range-picker" class="form-control"
								value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="dash-campo">
				<button id="btnTest" style="width:5px;" class="btn btn-success btn-rounded w-md m-b-1"><i
						class="hvr-buzz-out fa fa-search"></i> Consultar</button>
			</div>

		</div>

		<!-- Tab panels -->
		<div class="tab-content">
			<!---------------------------------------------TAB CAJA ACTUAL ------------------------------------------------------------>
			<div class="tab-pane fade active in" id="cajaActual">
				<div class="panel-body dash-cuerpo">
					<div class="dash-bloque">
						<div class="dash-seccion-titulo">
							<h3>Resumen de caja</h3>
							<small id="caja"></small>
						</div>
						<div class="dash-kpis">
							<div class="dash-kpi destacado">
								<div class="dash-kpi-etiqueta">Total ventas</div>
								<div class="dash-kpi-monto">$<span class="count-number1" id="totalVentas">0</span></div>
								<i class="pe-7s-cash dash-kpi-icono"></i>
								<!-- <div id="divTotalVenta1"><label id="lblTotalVenta1"></label> <label>test</label></div>
								<div id="divTotalVenta2"><label id="lblTotalVenta2"></label> <label>test</label></div> -->
							</div>

							<div class="dash-kpi">
								<div class="dash-kpi-etiqueta">Fondo fijo</div>
								<div class="dash-kpi-monto">$<span class="count-number2" id="fondoFijo">0</span></div>
								<i class="pe-7s-wallet dash-kpi-icono"></i>
								<!-- <div id="divCaja1"><label id="lblTest1"></label> <label>test</label></div>
								<div id="divCaja2"><label>test:</label> <label>test</label></div> -->
							</div>

							<div onclick="AbrirModalDetalleDescuentosDeCaja()" class="dash-kpi">
								<div class="dash-kpi-etiqueta">Descuentos</div>
								<div class="dash-kpi-monto">$<span class="count-number3" id="descuentos">0</span></div>
								<i class="pe-7s-ticket dash-kpi-icono"></i>
							</div>

							<div onclick="AbrirModalDetallePropinasDeCaja()" class="dash-kpi">
								<div class="dash-kpi-etiqueta">Propinas</div>
								<div class="dash-kpi-monto">$<span class="count-number4" id="propinas">0</span></div>
								<i class="pe-7s-gift dash-kpi-icono"></i>
							</div>

							<div onclick="AbrirModalDetalleDeIngresosDeCaja()" class="dash-kpi">
								<div class="dash-kpi-etiqueta">Ingresos</div>
								<div class="dash-kpi-monto">$<span class="count-number5" id="ingresos">0</span></div>
								<i class="pe-7s-download dash-kpi-icono"></i>
							</div>

							<div onclick="AbrirModalDetalleRetiroDeCaja()" class="dash-kpi">
								<div class="dash-kpi-etiqueta">Retiros</div>
								<div class="dash-kpi-monto">$<span class="count-number6" id="egresos">0</span></div>
								<i class="pe-7s-upload dash-kpi-icono"></i>
							</div>
						</div>
					</div>
					<!---------------------------------------------TABLAS RESUMEN ------------------------------------------------------------>

					<div class="dash-bloque dash-resumen">
						<div class="panel panel-success lobidisable dash-panel">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-file"></i>
									<h4>Por tipo de documento</h4>
								</div>
							</div>
							<div class="table-responsive">
								<table id="tableTipoDocumento" class="table dash-tabla">
									<thead>
										<tr>
											<th class="text text-center">Tipo De Documento</th>
											<th class="text text-center">Cantidad</th>
											<!--<th class="text text-center ocultarNetoTdocumento">Neto</th>-->
											<!--<th class="text text-center ocultarIvaTdocumento">Iva</th>-->
											<th class="text text-center">Total</th>
										</tr>
									</thead>
									<tbody id="resumenTipoDocumento">

									</tbody>
								</table>
							</div>
						</div>
						<div class="panel panel-success lobidisable dash-panel">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-credit"></i>
									<h4>Por forma de pago</h4>
								</div>
							</div>
							<div class="table-responsive">
								<table id="tablaFormaDePago" class="table dash-tabla">
									<thead>
										<tr>
											<th class="text text-center">Forma De Pago</th>
											<th class="text text-center">Cantidad</th>
											<!--<th class="text text-center ocultarNetoFormaDePago">Neto</th>-->
											<!--<th class="text text-center ocultarIvaFormaDePago">Iva</th>-->
											<th class="text text-center">Total</th>
											<th class="text text-center">Propina</th>
										</tr>
									</thead>
									<tbody id="resumenFormaPago">
									</tbody>
								</table>
							</div>
						</div>
						<div class="panel panel-success lobidisable dash-panel">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-gift"></i>
									<h4>Cortesías</h4>
								</div>
							</div>
							<div class="table-responsive">
								<table class="table dash-tabla">
									<thead>
										<tr>
											<th class="text text-center">Tipo Cortesia</th>
											<th class="text text-center">Cantidad</th>
											<!--<th class="text text-center ocultarNetoCortesias">Neto</th>-->
											<!--<th class="text text-center ocultarIvaCortesias">Iva</th>-->
											<th class="text text-center">Total</th>
										</tr>
									</thead>
									<tbody id="resumenCortesias">
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="dash-bloque dash-ancho">
						<div class="panel panel-success lobidisable dash-panel">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-clock"></i>
									<h4>Ventas por pagar</h4>
								</div>
								<span class="dash-badge" id="dashPendientes"></span>
							</div>
							<div class="table-responsive" style="margin-top:10px; padding:5px;">
								<table id="tableVentasPorPagar" class="display responsive" style="width: 100%">
									<thead>
										<tr>
											<th class="text text-center">N° Pedido</th>
											<th class="text text-center">Mesa</th>
											<th class="text text-center">Fecha/Hora</th>
											<th class="text text-center">Garzon/a</th>
											<th class="text text-center">Cliente</th>
											<th class="text text-center">Cuenta</th>
											<th class="text text-center">Descuento</th>
											<!--<th class="text text-center">Neto</th>
											<th class="text text-center">Iva</th>-->
											<th class="text text-center">Total</th>
											<th class="text text-center">Div.</th>
											<th class="text text-center">Detalle</th>
											<th class="text text"></th>
										</tr>
									</thead>
									<tfoot>
										<tr>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<!--<th></th>
											<th></th>-->
											<th></th>
											<th></th>
											<th></th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
					<script>
						/* Solo visual: muestra en la cabecera cuántas ventas por pagar trae la tabla (no altera su carga) */
						document.addEventListener('DOMContentLoaded', function () {
							if (!window.jQuery) { return; }
							jQuery(document).on('draw.dt', '#tableVentasPorPagar', function () {
								try {
									var n = jQuery('#tableVentasPorPagar').DataTable().page.info().recordsTotal;
									jQuery('#dashPendientes').text(n > 0 ? n + (n === 1 ? ' pendiente' : ' pendientes') : '');
								} catch (e) { }
							});
						});
					</script>
					<div class="modal fade modal-success" id="modal-success" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
											aria-hidden="true">&times;</span></button>
									<h1 class="modal-title">Consumo Pedido</h1>
								</div>
								<div class="modal-body">
									<!--
									<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
										<div class="panel panel-success lobidisable">
											<div class="panel-heading">
												<div class="panel-title">
													<i class="pe-7s-news-paper"></i>
													<h4>Productos</h4>
												</div>
											</div>
											<div class="panel-body">
											</div>
										</div>
									</div>-->
									<div class="panel panel-success lobidisable">
										<div class="panel-heading">
											<div class="panel-title">
												<i class="pe-7s-news-paper"></i>
												<h4>Productos</h4>
											</div>
										</div>
										<div class="table-responsive" style="margin-top:10px; padding:5px;">
											<table id="tableDetallePedido" class="display responsive"
												style="width: 100%">
												<thead>
													<tr>
														<th class="text text-center">Producto</th>
														<th class="text text-center">Cantidad</th>
														<th class="text text-center">Precio Unitario</th>
														<!--<th class="text text-center">Precio Neto</th>
														<th class="text text-center">Iva</th>-->
														<th class="text text-center">Total</th>
														<th class="text text-center">Hora Pedido</th>
														<th class="text text-center"></th>
													</tr>
												</thead>
												<tfoot>
													<tr>
														<th></th>
														<th></th>
														<th></th>
														<!--<th></th>
														<th></th>-->
														<th></th>
														<th></th>
														<th></th>
													</tr>
												</tfoot>
											</table>
										</div>
									</div>
								</div>
								<div class="modal-footer">
									<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
									<button type="button" class=" btn btn-labeled btn-danger m-b-5"
										data-dismiss="modal"><span class="btn-label">
											<i class="pe-7s-close-circle"></i></span> Cerrar</button>
								</div>
							</div><!-- /.modal-content -->
						</div><!-- /.modal-dialog -->
					</div><!-- /.modal -->

					<!-- CAMBIOS NUEVOS----------------------------------------------------------------------------------------------------------------->
					<!------------------------------------------------VENTAS POR FAMILIA (GRAFICO Y TABLA)---------------------------------------------------------------------->
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<h4>Ventas Por Familia</h4>
								</div>
							</div>
							<div class="panel-body" id="grafico">
								<div class="col-md-2"></div>
								<div class="col-md-8">
									<canvas id="chartFamilias" style="height: auto;"></canvas>
								</div>
								<div class="col-md-2"></div>
							</div>
							<div class="panel panel-success lobidisable rep-sin-marco">
								<div class="table-responsive" style="margin-top:10px; padding:5px;">
									<table id="tableVentasPorFamilia" class="display responsive" style="width: 100%">
										<thead>
											<tr>
												<th class="hidden">Familia</th>
												<th class="text text-center">Familia</th>
												<th class="text text-center">Cantidad</th>
												<!--<th class="text text-center">Total Neto</th>
												<th class="text text-center">Total Iva</th>-->
												<th class="text text-center">Total</th>
												<th class="text text-center">Detalles</th>
												<th class="text text"></th>
											</tr>
											<div class="col-md-9"></div>
											<div class="col-md-3">
												<label id="totalFamilia"></label>
											</div>
										</thead>
										<tfoot>
											<tr>
												<th></th>
												<th></th>
												<th></th>
												<!--<th></th>
												<th></th>-->
												<th></th>
												<th></th>
												<th></th>
											</tr>
										</tfoot>
									</table>
								</div>
							</div>


							<div class="modal fade modal-success" id="modalProductos" tabindex="-1" role="dialog">
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal"
												aria-label="Close"><span aria-hidden="true">&times;</span></button>
											<h1 class="modal-title">Productos</h1>
										</div>
										<div class="modal-body">
											<div class="panel panel-success lobidisable">
												<div class="panel-heading">
													<div class="panel-title">
														<i class="pe-7s-news-paper"></i>
														<h4>Productos</h4>
													</div>
												</div>
												<div class="table-responsive" style="margin-top:10px; padding:5px;">
													<table id="tableVentasPorProducto" class="display responsive"
														style="width: 100%">
														<thead>
															<tr>
																<th class="text text-center">Producto</th>
																<th class="text text-center">Cantidad</th>
																<!--<th class="text text-center">Total Neto</th>
																<th class="text text-center">Iva</th>-->
																<th class="text text-center">Total</th>
															</tr>
														</thead>
														<tfoot>
															<tr>
																<th></th>
																<th></th>
																<!--<th></th>
																<th></th>-->
																<th></th>
															</tr>
														</tfoot>
													</table>
												</div>
											</div>
										</div>
										<div class="modal-footer">
											<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
											<button type="button" class=" btn btn-labeled btn-danger m-b-5"
												data-dismiss="modal"><span class="btn-label">
													<i class="pe-7s-close-circle"></i></span> Cerrar</button>
										</div>
									</div><!-- /.modal-content -->
								</div><!-- /.modal-dialog -->
							</div><!-- /.modal -->
						</div>
					</div>


					<!-----------------------------------------------------VENTAS POR MESAS------------------------------------------->
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-diskette"></i>
									<h4>Ventas Por Mesas</h4>
								</div>
							</div>
							<div class="table-responsive" style="margin-top:10px; padding:5px;">
								<table id="tableVentasPorMesa" class="display responsive" style="width: 100%">
									<thead>
										<tr>
											<th class="text text-center">Mesa</th>
											<th class="text text-center">Total Ventas</th>
											<!--<th class="text text-center">Neto</th>
											<th class="text text-center">Iva</th>-->
											<th class="text text-center">Total</th>
										</tr>
										<div class="col-md-6"></div>
										<div class="col-md-3"></div>
										<div class="col-md-3">
											<label id="montoMesa"></label>
										</div>
									</thead>
									<tfoot>
										<tr>
											<th></th>
											<th></th>
											<!--<th></th>
											<th></th>-->
											<th></th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>


					<!-----------------------------------------------------------DETALLE ATENCION GARZONES ---------------------------------------------------------------------------------------->
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-diskette"></i>
									<h4>Detalle De Atencion</h4>
								</div>
							</div>
							<div class="table-responsive" style="margin-top:10px; padding:5px;">
								<table id="tableDetalleAtencion" class="display responsive" style="width: 100%">
									<thead>
										<tr>
											<th class="text text-center">Garzon</th>
											<th class="text text-center">Mesas Atendidas</th>
											<!--<th class="text text-center">Total Neto</th>
											<th class="text text-center">Total Iva</th>-->
											<th class="text text-center">Total Ventas</th>
											<th class="text text-center">Propinas</th>
											<th class="text text"></th>
											<!-- <th class="text text-center">Tiempo Promedio</th> -->
										</tr>
										<div class="col-md-6"></div>
										<div class="col-md-3">
											<label id="totalVentasGarzon"></label>
										</div>
										<div class="col-md-3">
											<label id="totalPropinasGarzon"></label>
										</div>
									</thead>
									<tfoot>
										<tr>
											<th></th>
											<th></th>
											<!--<th></th>
											<th></th>-->
											<th></th>
											<th></th>
											<th></th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>




				</div>
			</div>
			<!---------------------------------------------FIN TAB CAJA ACTUAL ------------------------------------------------------------>
			<!---------------------------------------------TAB VENTAS POR FAMILIAS ------------------------------------------------------------>
			<!--
			<div class="tab-pane fade" id="ventasPorFamilias" style="height: auto;">
				<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title">
								<h4>Ventas Por Familia</h4>
							</div>
						</div>
						<div class="panel-body" id="grafico">
							<div class="col-md-2"></div>
							<div class="col-md-8">
								<canvas id="chartFamilias" style="height: auto;"></canvas>
							</div>
							<div class="col-md-2"></div>
						</div>
						<div class="panel panel-success lobidisable">
							<div class="table-responsive" style="margin-top:10px; padding:5px;">
								<table id="tableVentasPorFamilia" class="display responsive" style="width: 100%">
									<thead>
										<tr>
											<th class="hidden">Familia</th>
											<th class="text text-center">Familia</th>
											<th class="text text-center">Cantidad</th>
											<th class="text text-center">Total Neto</th>
											<th class="text text-center">Total Iva</th>
											<th class="text text-center">Total</th>
											<th class="text text-center">Detalles</th>
											<th class="text text"></th>
										</tr>
										<div class="col-md-9"></div>
										<div class="col-md-3">
											<label id="totalFamilia"></label>
										</div>
									</thead>
									<tfoot>
										<tr>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div class="modal fade modal-success" id="modalProductos" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h1 class="modal-title">Productos</h1>
							</div>
							<div class="modal-body">
								<div class="panel panel-success lobidisable">
									<div class="panel-heading">
										<div class="panel-title">
											<i class="pe-7s-news-paper"></i>
											<h4>Productos</h4>
										</div>
									</div>
									<div class="table-responsive" style="margin-top:10px; padding:5px;">
										<table id="tableVentasPorProducto" class="display responsive" style="width: 100%">
											<thead>
												<tr>
													<th class="text text-center">Producto</th>
													<th class="text text-center">Cantidad</th>
													<th class="text text-center">Total Neto</th>
													<th class="text text-center">Iva</th>
													<th class="text text-center">Total</th>
												</tr>
											</thead>
											<tfoot>
												<tr>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
												</tr>
											</tfoot>
										</table>
									</div>
								</div>
							</div>
							<div class="modal-footer">
								
								<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span class="btn-label">
										<i class="pe-7s-close-circle"></i></span> Cerrar</button>
							</div>
						</div>
					</div>
				</div>
			</div>-->
			<!---------------------------------------------FIN TAB VENTAS POR FAMILIAS  ------------------------------------------------------------>

			<!---------------------------------------------TAB VENTAS POR PRODUCTO ------------------------------------------------------------>
			<div class="tab-pane fade" id="ventasPorProducto" style="height: auto;">
				<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title">
								<i class="pe-7s-cash"></i>
								<h4>Resumen Ventas Por Producto</h4>
							</div>
						</div>
						<div class="table-responsive" style="margin-top:10px; padding:5px;">
							<table id="tableVentasPorProducto" class="display responsive" style="width: 100%">
								<thead>
									<tr>
										<th class="text text-center">Producto</th>
										<th class="text text-center">Cantidad</th>
										<th class="text text-center">Total</th>
										<th class="text text"></th>
									</tr>
								</thead>
							</table>
						</div>
					</div>
				</div>
			</div>
			<!---------------------------------------------FIN TAB VENTAS POR PRODUCTO  ------------------------------------------------------------>

			<!---------------------------------------------TAB VENTAS POR MESAS ------------------------------------------------------------>
			<!--
			<div class="tab-pane fade" id="detalleDeMesas">
				<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title">
								<i class="pe-7s-diskette"></i>
								<h4>Ventas Por Mesas</h4>
							</div>
						</div>
						<div class="table-responsive" style="margin-top:10px; padding:5px;">
							<table id="tableVentasPorMesa" class="display responsive" style="width: 100%">
								<thead>
									<tr>
										<th class="text text-center">Mesa</th>
										<th class="text text-center">Total Ventas</th>
										<th class="text text-center">Neto</th>
										<th class="text text-center">Iva</th>
										<th class="text text-center">Total</th>
									</tr>
									<div class="col-md-6"></div>
									<div class="col-md-3"></div>
									<div class="col-md-3">
										<label id="montoMesa"></label>
									</div>
								</thead>
								<tfoot>
									<tr>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
			</div>
			<!---------------------------------------------FIN TAB VENTAS POR MESAS  ------------------------------------------------------------>

			<!---------------------------------------------TAB DETALLE ATENCION DE GARZONES ------------------------------------------------------------>
			<div class="tab-pane fade" id="detalleAtencionAgarzones">
				<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title">
								<i class="pe-7s-diskette"></i>
								<h4>Detalle De Atencion</h4>
							</div>
						</div>
						<div class="table-responsive" style="margin-top:10px; padding:5px;">
							<table id="tableDetalleAtencion" class="display responsive" style="width: 100%">
								<thead>
									<tr>
										<th class="text text-center">Garzon</th>
										<th class="text text-center">Mesas Atendidas</th>
										<th class="text text-center">Total Neto</th>
										<th class="text text-center">Total Iva</th>
										<th class="text text-center">Total Ventas</th>
										<th class="text text-center">Propinas</th>
										<th class="text text"></th>
										<!-- <th class="text text-center">Tiempo Promedio</th> -->
									</tr>
									<div class="col-md-6"></div>
									<div class="col-md-3">
										<label id="totalVentasGarzon"></label>
									</div>
									<div class="col-md-3">
										<label id="totalPropinasGarzon"></label>
									</div>
								</thead>
								<tfoot>
									<tr>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
			</div>
			<!---------------------------------------------FIN DETALLE ATENCION DE GARZONES  ------------------------------------------------------------>
		</div>
		<!---------------------------------------------FIN TAB DETALLE ATENCION DE GARZONES  ------------------------------------------------------------>
	</div>
</div>
</div>

<div class="modal fade modal-success" id="modalDetalleIngresos" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h1 class="modal-title">Detalle De Ingresos</h1>
			</div>
			<div class="modal-body">
				<div class="panel panel-success lobidisable">
					<div class="panel-heading">
						<div class="panel-title">
							<i class="pe-7s-news-paper"></i>
							<h4>Ingresos</h4>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th class="text text-center">N°</th>
									<th class="text text-center">Motivo Ingreso</th>
									<th class="text text-center">Monto</th>
									<th class="text text-center">Fecha/Hora</th>
									<th class="text text-center">Descripcion</th>
									<th class="text text-center">Centro De Costo</th>
								</tr>
							</thead>
							<tbody id="resumenDetalleIngresoDeCaja">
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
				<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span
						class="btn-label">
						<i class="pe-7s-close-circle"></i></span> Cerrar</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal fade modal-success" id="modalDetalleRetiros" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h1 class="modal-title">Detalle De Retiro</h1>
			</div>
			<div class="modal-body">
				<div class="panel panel-success lobidisable">
					<div class="panel-heading">
						<div class="panel-title">
							<i class="pe-7s-news-paper"></i>
							<h4> Retiros</h4>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th class="text text-center">N°</th>
									<th class="text text-center">Motivo Retiro</th>
									<th class="text text-center">Monto</th>
									<th class="text text-center">Fecha/Hora</th>
									<th class="text text-center">Descripcion Retiro</th>
									<th class="text text-center">Centro De Costo</th>
								</tr>
							</thead>
							<tbody id="resumenDetalleRetiroDeCaja">
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
				<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span
						class="btn-label">
						<i class="pe-7s-close-circle"></i></span> Cerrar</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal fade modal-success" id="modalDetalleDeDescuentos" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h1 class="modal-title">Detalle De Descuentos</h1>
			</div>
			<div class="modal-body">
				<div class="panel panel-success lobidisable">
					<div class="panel-heading">
						<div class="panel-title">
							<i class="pe-7s-news-paper"></i>
							<h4> Descuentos</h4>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th class="text text-center">N° Venta</th>
									<th class="text text-center">Cliente</th>
									<th class="text text-center">Pedido</th>
									<th class="text text-center">Total Sin Descuento</th>
									<th class="text text-center">% De Descuento</th>
									<th class="text text-center">Monto De Descuento</th>
									<th class="text text-center">Total Con Descuento</th>
								</tr>
							</thead>
							<tbody id="resumenDetalleDescuentos">
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
				<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span
						class="btn-label">
						<i class="pe-7s-close-circle"></i></span> Cerrar</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<div class="modal fade modal-success" id="modalDetalleDePropinas" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h1 class="modal-title">Detalle De Propinas</h1>
			</div>
			<div class="modal-body">
				<div class="panel panel-success lobidisable">
					<div class="panel-heading">
						<div class="panel-title">
							<i class="pe-7s-news-paper"></i>
							<h4> Propinas</h4>
						</div>
					</div>
					<fieldset>
						<div class="col-md-8">
							<br>
							<label>Garzon:
								<div class="checkbox checkbox-success checkbox-inline">
									<input type="checkbox" id="ckTodosLosUsuarios" value="option1">
									<label for="ckTodosLosUsuarios"> Todos </label>
								</div>
							</label>
							<select class="form-control" id="cboUsuarios" nombre="cboUsuarios">
								<option selected value="">
									<!--Todos Los Usuarios-->
								</option>
							</select>
						</div>
						<div class="col-md-4">
							<br>
							<br>
							<button id="btnFiltrarPropinasPorUsuario" style="width:5px;"
								class="btn btn-success btn-rounded w-md m-b-1"><i
									class="hvr-buzz-out fa fa-search"></i></button>
						</div>
					</fieldset>
					<div class="table-responsive" style="margin-top:10px; padding:5px;">
						<table id="tableDetallePropinas" class="display responsive" style="width: 100%">
							<thead>
								<tr>
									<th class="text text-center">Id Garzon</th>
									<th class="text text-center">Garzon</th>
									<th class="text text-center">Forma De Pago</th>
									<th class="text text-center">Propina</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
				<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span
						class="btn-label">
						<i class="pe-7s-close-circle"></i></span> Cerrar</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->