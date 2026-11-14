<?php
date_default_timezone_set('America/Santiago');
$desde = date('d-m-Y', strtotime('-6 days'));
$hasta = date('d-m-Y');
?>
<link href="<?php echo base_url() ?>assets/dist/css/reportes.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url() ?>assets/dist/css/auditoria.css?v=1" rel="stylesheet" type="text/css" />
<div class="content-header rep-encabezado">
	<div class="header-icon"><i class="pe-7s-search"></i></div>
	<div class="header-title">
		<h1>Auditoría de caja</h1>
		<small>Todo lo que pasó en una caja: arqueo, anulaciones, devoluciones, descuentos, ventas en espera, productos omitidos y movimientos</small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
			<li class="active">Auditoría de caja</li>
		</ol>
	</div>
</div>

<div class="row aud">
	<div class="col-xs-12 m-b-20">
		<ul class="nav nav-tabs t-filtros reporte-filtros rep-panel-filtros">
			<div class="col-md-3">
				<label for="date-range-picker">Cajas abiertas entre</label>
				<div class="input-prepend input-group">
					<span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
					<input type="text" id="date-range-picker" class="form-control" value="<?php echo $desde; ?> - <?php echo $hasta; ?>" />
				</div>
			</div>
			<div class="col-md-2">
				<button id="btnConsultar" class="btn btn-success btn-rounded w-md m-b-1 reporte-buscar"><i class="fa fa-search"></i> Consultar</button>
			</div>
			<div class="col-md-6 col-sm-12 rep-exportar">
				<button id="btnImprimir" class="btn btn-default w-md m-b-1" disabled><i class="fa fa-print"></i> Imprimir</button>
				<button id="btnCsv" class="btn btn-default w-md m-b-1" disabled><i class="fa fa-file-excel-o"></i> Exportar (CSV)</button>
			</div>
		</ul>

		<div class="rep-cuerpo">
			<section class="aud-card" id="audCajasCard">
				<header><h4>Cajas del período</h4><small id="audCajasResumen"></small></header>
				<div class="aud-scroll" id="audCajasScroll" style="max-height: 320px">
					<table class="aud-tabla" id="audCajas">
						<thead><tr><th>Caja</th><th>Cajero</th><th>Apertura</th><th>Cierre</th><th class="num">Ventas</th><th class="num">Diferencia</th><th>Alertas</th></tr></thead>
						<tbody><tr><td colspan="7" class="aud-vacio">Cargando…</td></tr></tbody>
					</table>
				</div>
				<p class="text-muted" id="audSinBitacora" style="display:none; margin: 10px 0 0; font-size: 12px">
					<i class="fa fa-info-circle"></i> Esta base aún no tiene la bitácora de la Caja web (ventas en espera, productos omitidos): se activa sola al ingresar de nuevo al sistema.
				</p>
			</section>

			<div id="audDetalle" style="display:none">
				<div class="aud-cabeza">
					<h3 id="audTitulo"></h3>
					<span id="audEstado"></span>
					<span class="meta" id="audMeta"></span>
				</div>
				<div class="aud-kpis" id="audKpis"></div>

				<div class="aud-cuadro">
					<section class="aud-card">
						<header><h4>Arqueo</h4><small id="audArqueoSub">Esperado por el sistema contra lo rendido al cerrar</small></header>
						<div class="aud-x">
							<table class="aud-tabla" id="audArqueo">
								<thead><tr><th>Forma de pago</th><th class="num">Esperado</th><th class="num">Rendido</th><th class="num">Diferencia</th></tr></thead>
								<tbody></tbody>
								<tfoot></tfoot>
							</table>
						</div>
						<p class="text-muted" style="margin: 10px 0 0; font-size: 12px">El efectivo esperado incluye el fondo inicial, ingresos y retiros, ya pagadas las propinas.</p>
					</section>
					<section class="aud-card">
						<header>
							<h4>Línea de tiempo</h4>
							<div class="aud-filtro-linea no-imprimir" id="audFiltroLinea">
								<button type="button" class="activo" data-filtro="todo">Todo</button>
								<button type="button" data-filtro="alerta">Alertas</button>
								<button type="button" data-filtro="espera">Ventas en espera</button>
								<button type="button" data-filtro="caja">Caja y gaveta</button>
								<button type="button" data-filtro="documentos">Descuentos y cortesías</button>
							</div>
						</header>
						<ul class="aud-linea" id="audLinea"></ul>
					</section>
				</div>

				<section class="aud-card">
					<header><h4>Detalle</h4><small>Cada hecho con su usuario, hora y monto</small></header>
					<ul class="nav nav-tabs aud-tabs" id="audTabs">
						<li class="active"><a href="#audTabAnulados" data-toggle="tab">Anulaciones<span class="badge" data-n="anulados"></span></a></li>
						<li><a href="#audTabEsperas" data-toggle="tab">Ventas en espera<span class="badge" data-n="esperas"></span></a></li>
						<li><a href="#audTabOmitidos" data-toggle="tab">Omitidos y vaciadas<span class="badge" data-n="omitidos"></span></a></li>
						<li><a href="#audTabDescuentos" data-toggle="tab">Descuentos, cortesías y NC<span class="badge" data-n="descuentos"></span></a></li>
						<li><a href="#audTabMovimientos" data-toggle="tab">Ingresos y retiros<span class="badge" data-n="movimientos"></span></a></li>
						<li><a href="#audTabDocumentos" data-toggle="tab">Todos los documentos<span class="badge" data-n="documentos"></span></a></li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="audTabAnulados"></div>
						<div class="tab-pane" id="audTabEsperas"></div>
						<div class="tab-pane" id="audTabOmitidos"></div>
						<div class="tab-pane" id="audTabDescuentos"></div>
						<div class="tab-pane" id="audTabMovimientos"></div>
						<div class="tab-pane" id="audTabDocumentos"></div>
					</div>
				</section>
			</div>
		</div>
	</div>
</div>
