<div class="content-header">
  <div class="header-icon">
    <i class="pe-7s-home"></i>
  </div>
  <div class="header-title">
    <h1>Proveedor</h1>
    <small id="caja"></small>
    <ol class="breadcrumb">
      <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
      <li class="active">Proveedor</li>
    </ol>
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
              <h4>Listado De Proveedor</h4>
            </div>
          </div>
          <br>
          <div class="col-xs-12 text-end" style="display: flex; justify-content: flex-end; width: 100%;">
            <button onclick="AbrirModalNuevoProveedor()" type="button" style="margin-right: 20px; margin-bottom:20px;" class="btn btn-primary"></i>Habilitar Nuevo Proveedor</button>
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