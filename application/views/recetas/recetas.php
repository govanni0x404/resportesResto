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

<style>
	/* Listado: acción principal en la cabecera del panel, tabla con aire */
	.t-lista > .panel-heading .panel-title { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 12px; }
	.t-lista > .panel-heading .panel-title h4 { flex: 1 1 auto; }
	.t-lista > .panel-heading .t-acciones { margin: 0; }
	.t-lista > .panel-heading .btn i { color: #fff; margin-right: 4px; }
	.t-lista > .table-responsive { padding: 14px 18px 18px !important; margin: 0 !important; border: 0; }
	@media (max-width: 767px) { .t-lista > .panel-heading .t-acciones, .t-lista > .panel-heading .t-acciones .btn { width: 100%; } }
</style>

<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
		<div class="panel-body">
			<!---------------------------------------------TABLAS RESUMEN STOCK POR BODEGA ------------------------------------------------------------>
			<div class="panel panel-success lobidisable t-lista">
				<div class="panel-heading">
					<div class="panel-title">
						<i class="pe-7s-cash"></i>
						<h4>Recetas</h4>
						<div class="t-acciones"><button id="btnNuevaReceta" type="button" class="btn btn-primary"><i class="fa fa-plus"></i> Crear Receta</button></div>
					</div>
				</div>
				<div class="table-responsive" style="margin-top:10px; padding:5px;">
					<table id="tableRecetas" class="display responsive" style="width: 100%">
						<thead>
							<tr>
								<th class="text text-center">ID RECETA</th>
								<th class="text text-center" style="width:1px">NOMBRE DE LA RECETA</th>
								<th class="text text-left">GRUPO/FAMILIA</th>
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
