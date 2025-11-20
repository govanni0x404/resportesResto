<?php
date_default_timezone_set('America/Santiago');
$fechaActual = date('d/m/Y');
$date = new DateTime('now');
$date->modify('last day of this month');
?>

<div class="content-header">
	<div class="header-icon">
		<i class="pe-7s-home"></i>
	</div>
	<div class="header-title">
		<h1>Cierres de Caja</h1>
		<small id="caja"></small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
			<li class="active">Cierres De Caja</li>
		</ol>
	</div>
</div> <!-- /.Content Header (Page header) -->

<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
		<!-- Nav tabs -->
		<ul class="nav nav-tabs">
			<div class="col-md-7"></div>
			<div class="col-md-3">
				<br>
				<label>Fecha Caja</label>
				<div class="input-prepend input-group">
					<span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
					<input type="text" id="fechaCaja" class="form-control" value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" />
				</div>
			</div>
			<div class="col-md-2">
				<br>
				<br>
				<button id="btnTest" style="width:5px;" class="btn btn-success btn-rounded w-md m-b-1"><i class="hvr-buzz-out fa fa-search"></i></button>
			</div>
		</ul>
		<br>
		<!-- Tab panels -->
		<div class="tab-content">
			<!---------------------------------------------TAB CAJA ACTUAL ------------------------------------------------------------>
			<div class="tab-pane fade active in" id="cajaActual">
				<div class="panel-body">
					<div class="row">
					</div>
					<!---------------------------------------------TABLAS RESUMEN ------------------------------------------------------------>
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title">
								<i class="pe-7s-cash"></i>
								<h4>Resumen Cierres De Caja</h4>
							</div>
						</div>
						<div class="table-responsive" style="margin-top:10px; padding:5px;">
							<table id="tableCierresDeCaja" class="display responsive" style="width: 100%">
								<thead>
									<tr>
										<th class="text text-center">N° Caja</th>
										<th class="text text-center">Fecha Apertura</th>
										<th class="text text-center">Fecha Rendicion</th>
										<th class="text text-center">Ventas Local</th>
										<th class="text text-center">Fondo Fijo</th>
										<th class="text text-center">Total Sistema</th>
										<th class="text text-center">Descuentos</th>
										<th class="text text-center">Ventas</th>
										<th class="text text-center">Cortesias</th>
										<th class="text text-center">Ingresos</th>
										<th class="text text-center">Egresos</th>
										<th class="text text-center">Subtotal</th>
										<th class="text text-center">Efectivo</th>
										<th class="text text-center">Efectivo Entregado</th>
										<th class="text text-center">Diferencia</th>
										<th class="text text-center">Propina</th>
										<th class="text text-center">Estado Caja</th>
										<th class="text text-center">Cajero</th>
										<th class="text text-center">PDF CIERRE</th>
										<th class="text text-center"></th>
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
										<th></th>
										<th></th>
										<th></th>
										<th></th>
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
			<!---------------------------------------------FIN TAB CAJA ACTUAL ------------------------------------------------------------>
		</div>
		<!---------------------------------------------FIN TAB DETALLE ATENCION DE GARZONES  ------------------------------------------------------------>
	</div>
</div>
</div>