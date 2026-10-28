<div class="content-header">
  <div class="header-icon">
    <i class="pe-7s-home"></i>
  </div>
  <div class="header-title">
    <h1>Proveedores</h1>
    <small id="caja"></small>
    <ol class="breadcrumb">
      <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
      <li class="active">Proveedor</li>
    </ol>
  </div>
</div>


<style>
	/* Listado: acción principal en la cabecera del panel, tabla con aire */
	.t-lista > .panel-heading .panel-title { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 12px; }
	.t-lista > .panel-heading .panel-title h4 { flex: 1 1 auto; }
	.t-lista > .panel-heading .t-acciones { margin: 0; }
	.t-lista > .panel-heading .btn i { color: #fff; margin-right: 4px; }
	.t-lista > .table-responsive { padding: 14px 18px 18px !important; margin: 0 !important; border: 0; }
	@media (max-width: 767px) { .t-lista > .panel-heading .t-acciones, .t-lista > .panel-heading .t-acciones .btn { width: 100%; } }
</style>

<div class="container-padding">
  <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
      <div class="panel-body">

        <div class="panel panel-success lobidisable t-lista">
          <div class="panel-heading">
            <div class="panel-title">
              <i class="pe-7s-cash"></i>
              <h4>Listado De Proveedores</h4>
              <div class="t-acciones"><button onclick="AbrirModalNuevoProveedor()" type="button" class="btn btn-primary"><i class="fa fa-plus"></i> Crear Nuevo Proveedor</button></div>
            </div>
          </div>
          <div class="table-responsive" style="margin-top:10px; padding:5px;">
            <table id="tablaProveedor" class="display responsive" style="width: 100%">
              <thead>
                <tr>
                  <th class="text text-center" style='height:10px'>N° PROVEEDOR</th>
                  <th class="text text-left" style='height:10px'>RUT</th>
                  <th class="text text-center" style='height:10px'>DV</th>
                  <th class="text text-left" style='height:10px'>RUT</th>
                  <th class="text text-left" style='height:45px'>PROVEEDOR</th>
                  <th class="text text-center" style='height:10px'>DIRECCION</th>
                  <th class="text text-center" style='height:10px'>TELEFONO</th>
                  <th class="text text-center" style='height:10px'>MAIL</th>
                  <th class="text text-left" style='height:10px'>GIRO</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>