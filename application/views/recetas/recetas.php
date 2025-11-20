<div class="content-header">
	<div class="header-icon">
		<i class="pe-7s-home"></i>
	</div>
	<div class="header-title">
		<h1>Recetas</h1>
		<small id="caja"></small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantenedores</a></li>
			<li class="active">Recetas</li>
		</ol>
	</div>
</div> <!-- /.Content Header (Page header) -->

<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
		<div class="panel-body">
			<!---------------------------------------------TABLAS RESUMEN STOCK POR BODEGA ------------------------------------------------------------>
			<div class="panel panel-success lobidisable">
				<div class="panel-heading">
					<div class="panel-title">
						<i class="pe-7s-cash"></i>
						<h4>Recetas</h4>
					</div>
				</div>
				<br>
				<button style="margin-left:10px;" id="btnNuevaReceta" type="button" class="btn btn-success"></i>Crear Receta</button>

				<div class="table-responsive" style="margin-top:10px; padding:5px;">
					<table id="tableRecetas" class="display responsive" style="width: 100%">
						<thead>
							<tr>
								<th class="text text-center">ID RECETA</th>
								<th class="text text-center" style="width:1px">NOMBRE DE LA RECETA</th>
								<th class="text text-center">GRUPO/FAMILIA</th>
								<th class="text text-center">COSTO INSUMOS</th>
								<th class="text text-center">MARGEN GANANCIA</th>
								<th class="text text-center">% COSTO</th>
								<th class="text text-center">% GANANCIA</th>
								<th class="text text-center">$ SUGERIDO</th>
								<th class="text text-center">$ VENTA</th>
								<th class="text text-center">CANT. PRODUCIDO</th>
								<th class="text text-center">UMEDIDA</th>
								<th class="text text-center">FAMILIA</th>
								<th class="text text-center">SECCION</th>
								<th class="text text-center"></th>
							</tr>
						</thead>
						<tbody id="detalleTableRecetas">
							<!-- detalle de la tabla -->
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
