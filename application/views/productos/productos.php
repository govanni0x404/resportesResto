<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('Y-m-d');
?>
<div class="content-header">
	<div class="header-icon">
		<i class="pe-7s-home"></i>
	</div>
	<div class="header-title">
		<h1>Carta De Productos</h1>
		<small id="caja"></small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
			<li class="active">Carta De Productos</li>
		</ol>
	</div>
</div>

<div class="container-padding">
	<input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">
	<div class="row">
		<div class="col-xs-12">
			<div class="panel panel-success lobidisable" style="margin-top: 20px;">
				<div class="panel-heading">
					<div class="panel-title">
						<i class="pe-7s-cash"></i>
						<h4>LISTADO DE PRODUCTOS</h4>
					</div>
				</div>
				<div class="row " style="margin-bottom: 5px; margin-top: 20px; margin-right: 50px;">
				<div class="col-xs-12 text-end" style="display: flex; justify-content: flex-end; width: 100%;">
					<button onclick="crearNuevoProducto()" type="button" class="btn btn-primary">
						<i class="fa fa-plus"></i> Nuevo Producto
					</button>
				</div>
				</div>
				<div class="panel-body">
					<div class="table-responsive">
						<table id="tableCartaProductos" class="display responsive" style="width: 100%">
							<thead>
								<tr>
									<th class="text text-center">N° PRODUCTO</th>
									<th class="text text-center">CODIGO DE BARRAS</th>
									<th class="text text-left">PRODUCTO</th>
									<th class="text text-left">FAMILIA</th>
									<th class="text text-left">U. MEDIDA</th>
									<th class="text text-center">IMPTO. VENTA</th>
									<th class="text text-center">IMPTO. ADICIONAL</th>
									<th class="text text-center">DESTINO DE IMPRESION</th>
									<th class="text text-left">BODEGA</th>
									<th class="text text-center">PONDERADO</th>
									<th class="text text-center">MENU</th>
									<th class="text text-center">SUB FAMILIA</th>
									<th class="text text-center">RECETA</th>
									<th class="text text-center">VIGENTE</th>
									<th class="text text-center">CON AGREGADO</th>
									<th class="text text-center">VENTA PUBLICO</th>
									<th class="text text-center">PERMITE PORCION</th>
									<th class="text text-center">VENTA X STOCK</th>
									<th class="text text-center">ELABORACION PROPIA</th>
									<th class="text text-center">VENTA SIN PRECIO</th>
									<th class="text text-center">VENTA DIRECTA</th>
									<th class="text text-center">PEDIR CANTIDAD EN VENTA</th>
									<th class="text text-center">VER DISPOSITIVO MOVIL</th>
									<th class="text text-center">PERMITE CORTESIA</th>
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
	</div>
</div>