<?php
date_default_timezone_set('America/Santiago');
$desde = date('d-m-Y', strtotime('-6 days'));
$hasta = date('d-m-Y');
?>
<link href="<?php echo base_url() ?>assets/dist/css/reportes.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url() ?>assets/dist/css/auditoria.css?v=1" rel="stylesheet" type="text/css" />
<div class="content-header rep-encabezado">
	<div class="header-icon"><i class="pe-7s-close-circle"></i></div>
	<div class="header-title">
		<h1>Anulación de ventas</h1>
		<small>Ventas en espera anuladas con su justificación y documentos anulados</small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
			<li class="active">Anulación de ventas</li>
		</ol>
	</div>
</div>

<div class="row aud">
	<div class="col-xs-12 m-b-20">
		<ul class="nav nav-tabs t-filtros reporte-filtros rep-panel-filtros">
			<div class="col-md-3">
				<label for="date-range-picker">Período</label>
				<div class="input-prepend input-group">
					<span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
					<input type="text" id="date-range-picker" class="form-control" value="<?php echo $desde; ?> - <?php echo $hasta; ?>" />
				</div>
			</div>
			<div class="col-md-2">
				<label for="cboTipo">Tipo</label>
				<select id="cboTipo" class="form-control">
					<option value="todas">Todas</option>
					<option value="espera">Ventas en espera</option>
					<option value="documento">Documentos</option>
				</select>
			</div>
			<div class="col-md-2">
				<button id="btnConsultar" class="btn btn-success btn-rounded w-md m-b-1 reporte-buscar"><i class="fa fa-search"></i> Consultar</button>
			</div>
			<div class="col-md-5 col-sm-12 rep-exportar">
				<button id="btnImprimir" class="btn btn-default w-md m-b-1" disabled><i class="fa fa-print"></i> Imprimir</button>
				<button id="btnCsv" class="btn btn-default w-md m-b-1" disabled><i class="fa fa-file-excel-o"></i> Exportar (CSV)</button>
			</div>
		</ul>

		<div class="rep-cuerpo">
			<div class="aud-kpis" id="anKpis"></div>
			<div class="aud-cuadro">
				<section class="aud-card">
					<header><h4>Por usuario</h4><small>Monto anulado por quien anuló</small></header>
					<div class="aud-rank" id="anUsuarios"></div>
				</section>
				<section class="aud-card">
					<header>
						<h4>Anulaciones</h4>
						<div class="aud-buscar no-imprimir"><i class="fa fa-search"></i><input type="text" id="anBuscar" class="form-control" placeholder="Buscar por nombre, folio, motivo o usuario" autocomplete="off"></div>
					</header>
					<div class="aud-scroll" style="max-height: 560px">
						<table class="aud-tabla" id="anTabla">
							<thead><tr><th>Fecha</th><th>Qué se anuló</th><th>Justificación</th><th>Usuario</th><th class="num">Monto</th></tr></thead>
							<tbody><tr><td colspan="5" class="aud-vacio">Cargando…</td></tr></tbody>
						</table>
					</div>
					<p class="text-muted" style="margin: 10px 0 0; font-size: 12px">Los productos anulados en mesas están en Consultas &gt; Resumen De Anulaciones/Eliminaciones.</p>
				</section>
			</div>
		</div>
	</div>
</div>
