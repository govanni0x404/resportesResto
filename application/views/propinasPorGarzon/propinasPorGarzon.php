<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
//$fechaActual = date('Y-m-d');
$fechaActual = date('d-m-Y');
?>
<link href="<?php echo base_url() ?>assets/dist/css/reportes.css" rel="stylesheet" type="text/css" />
<div class="content-header rep-encabezado">
  <div class="header-icon">
    <i class="pe-7s-home"></i>
  </div>
  <div class="header-title">
    <h1>Propinas Por Garzon</h1>
    <small id="caja"></small>
    <ol class="breadcrumb">
      <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
      <li class="active">Propinas Por Garzon</li>
    </ol>
  </div>
</div> <!-- /.Content Header (Page header) -->


<div class="container-padding rep-propinas">
  <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

  <style>
  /* Filtros, tarjetas y tablas: assets/dist/css/reportes.css */
  .rep-panel-vacio { border: 0; box-shadow: none; background: transparent; margin-bottom: 0; }
  .rep-panel-vacio > .panel-heading, .rep-panel-vacio > br { display: none; }
  .rep-panel-vacio:after { content: ""; display: table; clear: both; }
  .rep-panel-vacio.rep-fila { margin-left: -15px; margin-right: -15px; }
  .rep-propinas .panel-body { padding-left: 0; padding-right: 0; }
  </style>

  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
      <div class="panel-body">
<div id="filtroPropinasGarzon" class="row t-filtros rep-filtros rep-panel-filtros">
<div class="col-md-4 col-sm-6 col-xs-12">
				<div class="control-group">
					<div class="controls">
						<label> Seleccione la fecha </label>
						<div class="input-prepend input-group">
							<span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
							<input type="text" class="hidden" id="pruebaFecha" value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>">
							<input type="text" id="date-range-picker" class="form-control" value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" />
						</div>
					</div>
				</div>
			</div>
            <div class="col-md-3 col-sm-6 col-xs-12">
                <button id="btnFiltrar" style="width:5px;" class="btn btn-success btn-rounded w-md m-b-1"><i class="hvr-buzz-out fa fa-search"></i> Consultar</button>
            </div>
             <div class="col-md-4 col-sm-12 col-xs-12 rep-espacio">
            </div>
</div>
        <div class="panel lobidisable rep-panel-vacio">
          <div class="panel-heading">
            <div class="panel-title">
              
            </div>
          </div>
          <br>
          
        </div>
      </div>


<div class="container-padding rep-propinas">
  <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
      <div class="panel-body">

        <div class="panel panel-success lobidisable">
          <div class="panel-heading">
            <div class="panel-title">
              <i class="pe-7s-cash"></i>
              <h4>Propinas Entre Fechas</h4>
            </div>
          </div>
          <div class="table-responsive" style="margin-top:10px; padding:5px;">
            <table id="tablePropinasEntreFechas" class="display responsive" style="width: 100%">
              <thead>
                <tr>
                  <th class="text text-left" style='height:10px'>N° CAJA</th>
                  <th class="text text-center" style='height:45px'>FECHA</th>
                 <th class="text text-center" style='height:45px'>GARZON</th>
                  <th class="text text-center" style='height:45px'>PROPINA</th>
                  <th class="text text-center" style='height:45px'>FORMA DE PAGO</th>

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


      <div class="container-padding rep-propinas">
  <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
      <div class="panel-body">

        <div class="panel  lobidisable rep-panel-vacio rep-fila">
          <div class="panel-heading">
            <div class="panel-title">
             
            </div>
          </div>
            <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-cash"></i>
									<h4>Total Propina Por Garzon</h4>
								</div>
							</div>
							<div class="table-responsive">
								<table id="tableTotalPorGarzon" class="table">
									<thead>
										<tr>
											<th class="text text-center">GARZON</th>
											<th class="text text-center">TOTAL</th>
										</tr>
									</thead>
									<tbody id="resumenTotalPorGarzon">

									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-cash"></i>
									<h4>Total Propina Por Forma De Pago</h4>
								</div>
							</div>
							<div class="table-responsive">
								<table id="tablaTotalPorFormaDePago" class="table">
									<thead>
										<tr>
											<th class="text text-center">FORMA DE PAGO</th>
											<th class="text text-center">TOTAL</th>
										</tr>
									</thead>
									<tbody id="resumenTotalPorFormaDePago">
									</tbody>
								</table>
							</div>
						</div>
					</div>
        </div>
      </div>
