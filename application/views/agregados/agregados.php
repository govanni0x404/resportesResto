<div class="content-header">
  <div class="header-icon">
    <i class="pe-7s-home"></i>
  </div>
  <div class="header-title">
    <h1>Agregados</h1>
    <small id="caja"></small>
    <ol class="breadcrumb">
      <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
      <li class="active">Agregados</li>
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
              <h4>Listado De Agregados</h4>
            </div>
          </div>
          <br>
          <button onclick="AbrirModalNuevoAgregados()" type="button" class="btn btn-primary gap-2" style="margin-left:15px;"></i>Habilitar Nuevo Agregado</button>
          <div class="table-responsive" style="margin-top:10px; padding:5px;">
            <table id="tablaAgregados" class="display responsive" style="width: 100%">
              <thead>
                <tr>
                  <th class="text text-center" style='height:10px'>N° AGREGADO</th>
                  <th class="text text-center" style='height:45px'>PRODUCTO DE VENTA C/AGREGADO</th>
                  <th class="text text-center" style='height:10px'>AGREGADO</th>
                  <th class="text text-center" style='height:10px'>CANTIDAD</th>
                  <th class="text text-center" style='height:10px'>VALOR</th>
                  <th class="text text-center" style='height:10px'>ACTIVO</th>
                  <th class="text text-center" style='height:10px'>USAR DESTINO DE IMPRESION</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>