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
</div> <!-- /.Content Header (Page header) -->




<div class="container-padding">
	<input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
			<div class="panel-body">
				<!--
                <div class="col-md-4">
                    <br>
                    <label>Buscar Por Familias:

                        <div class="checkbox checkbox-success checkbox-inline">
                            <input type="checkbox" id="ckTodasLasFamilias" value="option1">
                            <label for="ckTodasLasFamilias"> Todas </label>
                        </div>

                    </label>
                    <select class="form-control" id="cboFamilias" nombre="cboFamilias">
                        <option selected value="">
                           
                        </option>
                    </select>
                </div>
                <br>
                <div class="col-md-4">
                    <br>
                    <button id="btnFiltrar" style="width:5px;" class="btn btn-success btn-rounded w-md m-b-1"><i class="hvr-buzz-out fa fa-search"></i></button>
                </div>
                <br>-->
				<div class="col-md-4">
					<button onclick="crearNuevoProducto()" type="button" class="btn btn-primary"></i>Nuevo Producto</button>
				</div>
				<div class="panel lobidisable">
					<div class="panel-heading">
						<div class="panel-title">

						</div>
					</div>
					<br>

				</div>
			</div>


			<div class="container-padding">
				<input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

				<div class="row">
					<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
						<div class="panel-body">

							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title">
										<i class="pe-7s-cash"></i>
										<h4>LISTADO DE PRODUCTOS</h4>
									</div>
								</div>
								<div class="table-responsive" style="margin-top:10px; padding:5px;">
									<table id="tableCartaProductos" class="display responsive" style="width: 100%">
										<thead>
											<tr>
												<th class="text text-center" style='height:10px'>N° PRODUCTO</th>
												<th class="text text-center" style='height:45px'>CODIGO DE BARRAS</th>
												<th class="text text-center" style='height:45px'>PRODUCTO</th>
												<th class="text text-center" style='height:45px'>FAMILIA</th>
												<th class="text text-center" style='height:45px'>U. MEDIDA</th>
												<th class="text text-center" style='height:45px'>IMPTO. VENTA</th>
												<th class="text text-center" style='height:45px'>IMPTO. ADICIONAL</th>
												<th class="text text-center" style='height:45px'>DESTINO DE IMPRESION</th>
												<th class="text text-center" style='height:10px'>BODEGA</th>
												<th class="text text-center" style='height:45px'>PONDERADO</th>
												<th class="text text-center" style='height:45px'>MENU</th>
												<th class="text text-center" style='height:45px'>SUB FAMILIA</th>
												<th class="text text-center" style='height:45px'>RECETA</th>
												<th class="text text-center" style='height:45px'>VIGENTE</th>
												<th class="text text-center" style='height:45px'>CON AGREGADO</th>
												<th class="text text-center" style='height:10px'>VENTA PUBLICO</th>
												<th class="text text-center" style='height:45px'>PERMITE PORCION</th>
												<th class="text text-center" style='height:45px'>VENTA X STOCK</th>
												<th class="text text-center" style='height:45px'>ELABORACION PROPIA</th>
												<th class="text text-center" style='height:45px'>VENTA SIN PRECIO</th>
												<th class="text text-center" style='height:45px'>VENTA DIRECTA</th>
												<th class="text text-center" style='height:45px'>PEDIR CANTIDAD EN VENTA</th>
												<th class="text text-center" style='height:45px'>VER DISPOSITIVO MOVIL</th>
												<th class="text text-center" style='height:45px'>PERMITE CORTESIA</th>
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
