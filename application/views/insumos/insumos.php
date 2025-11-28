<div class="content-header">
	<div class="header-icon">
		<i class="pe-7s-home"></i>
	</div>
	<div class="header-title">
		<h1>Insumos</h1>
		<small id="caja"></small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantenedores</a></li>
			<li class="active">Insumos</li>
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
						<h4>Insumos</h4>
					</div>
				</div>
				<br>
				<button style="margin-left:10px;" id="btnNuevoInsumo" type="button" class="btn btn-primary"></i>Crear Insumo</button>

				<div class="table-responsive" style="margin-top:10px; padding:5px;">
					<table id="tableInsumos" class="display responsive" style="width: 100%">
						<thead>
							<tr>
								<th class="text text-center">ID INSUMO</th>
								<th class="text text-center" style="width:1px">CODIGO DE BARRA</th>
								<th class="text text-center" style="width:100%">NOMBRE INSUMO</th>
								<th class="text text-center" style="width:100%">GRUPO INSUMO</th>
								<th class="text text-center">COSTO NETO PP</th>
								<th class="text text-center">COSTO BRUTO PP</th>
								<th class="text text-center">MED. INV</th>
								<th class="text text-center">UN. MEDIDA INV.</th>
								<th class="text text-center">MED. RECET</th>
								<th class="text text-center">UN. MED. RECETA</th>
								<th class="text text-center">IVA</th>
								<th class="text text-center">IMPTO. ADICIONAL</th>
								<th class="text text-center">% MERMA</th>
								<th class="text text-center">ES RECETA</th>
								<th class="text text-center">INVENTARIO CIERRE</th>
								<th class="text text-center">PRECIO U. COMPRA</th>
								<th class="text text-center">INVENTARIO C. CAJA</th>
								<th class="text text-center">OBSERVACION</th>
								<th class="text text-center"></th>
							</tr>
						</thead>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
