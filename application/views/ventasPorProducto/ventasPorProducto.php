<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('d-m-Y');

?>
<div class="content-header">
	<div class="header-icon">
		<i class="pe-7s-home"></i>
	</div>
	<div class="header-title">
		<h1>Ventas Por Productos</h1>
		<small id="caja"></small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
			<li class="active">Ventas Por Productos</li>
		</ol>
	</div>
</div> <!-- /.Content Header (Page header) -->

<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
		<!-- Nav tabs -->
		<ul class="nav nav-tabs">
			<div class="col-md-4">
				<!-- <br>
				<label>Bodegas:</label>
				<select class="form-control" id="cboBodegas" nombre="cboBodegas">
					<option selected value="">Todas Las Bodegas</option>
				</select> -->
			</div>
			<div class="col-md-4"></div>
			<div class="col-md-2">

			</div>
			<div class="col-md-4">
				<br>
				<div class="control-group">
					<div class="controls">
						<label> Seleccione la fecha </label>
						<div class="input-prepend input-group">
							<span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
							<input type="text" id="date-range-picker" class="form-control"
								value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" />
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-2">
				<br>
				<br>
				<button id="btnConsultar" onclick="consultarRListadoVentasPorProducto()" style="width:5px;"
					class="btn btn-success btn-rounded w-md m-b-1"><i class="hvr-buzz-out fa fa-search"></i></button>
			</div>
		</ul>
		<br>
		<div class="panel-body">
			<div class="row">
			</div>
			<!---------------------------------------------TABLAS RESUMEN STOCK POR BODEGA ------------------------------------------------------------>
			<div class="panel panel-success lobidisable">
				<div class="panel-heading">
					<div class="panel-title">
						<i class="pe-7s-cash"></i>
						<h4>Resumen Ventas Por Productos</h4>
					</div>
				</div>
				<div class="table-responsive" style="margin-top:10px; padding:5px;">
					<table id="tableVentasPorProducto" class="display responsive" style="width: 100%">
						<thead>
							<tr>
								<th class="text text-left">Producto</th>
								<th class="text text-center">Cantidad Vendida</th>
								<!--<th class="text text-center">Total Neto</th>
								<th class="text text-center">Total Iva</th>-->
								<th class="text text-center">Total Vendido</th>
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
					<br>
					<div class="panel-body" id="grafico">
						<div class="col-md-2"></div>
						<div class="col-md-8">
							<canvas id="chartProductos" style="height: auto;"></canvas>
						</div>
						<div class="col-md-2"></div>
					</div>
				</div>
			</div>

			<!---------------------------------------------FIN TAB CAJA ACTUAL ------------------------------------------------------------>
		</div>
		<!---------------------------------------------FIN TAB DETALLE ATENCION DE GARZONES  ------------------------------------------------------------>
	</div>
</div>
</div>