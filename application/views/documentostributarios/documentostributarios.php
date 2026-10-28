<link href="<?php echo base_url() ?>assets/dist/css/reportes.css" rel="stylesheet" type="text/css" />
<div class="content-header rep-encabezado">
	<div class="header-icon">
		<i class="pe-7s-file"></i>
	</div>
	<div class="header-title">
		<h1>Tipo de Documentos</h1>
		<small id="caja"></small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
			<li class="active">Tipo de Documentos</li>
		</ol>
	</div>
</div>


<style>
	.reporte-sin-marco { padding: 0 !important; border: 0 !important; background: none !important; }
	.reporte-sin-marco .panel-heading .panel-title { display: flex; align-items: center; }
	.reporte-tabla { padding: 12px 16px 16px; }
	.reporte-tabla table.dataTable { margin-top: 6px !important; }
</style>

<div class="container-padding">
	<input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
			<div class="panel-body reporte-sin-marco">

				<div class="panel panel-success lobidisable">
					<div class="panel-heading">
						<div class="panel-title">
							<i class="pe-7s-cash"></i>
							<h4>Tipo de Documentos</h4>
						</div>
					</div>
					<div class="table-responsive reporte-tabla">
						<table id="tableDocumentosTributarios" class="display responsive" style="width: 100%">
							<thead>
								<tr>
									<th class="text text-center" style='height:10px'>ID</th>
									<th class="text text-center" style='height:45px'>TIPO DOCUMENTO</th>
									<th class="text text-center" style='height:10px'>CODIGO SII</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
