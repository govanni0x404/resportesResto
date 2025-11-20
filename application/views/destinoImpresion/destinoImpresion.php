<div class="content-header">
  <div class="header-icon">
    <i class="pe-7s-home"></i>
  </div>
  <div class="header-title">
    <h1>Destino De Impresion</h1>
    <small id="caja"></small>
    <ol class="breadcrumb">
      <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
      <li class="active">Destino De Impresion</li>
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
              <h4>Listado De Destino De Impresion</h4>
            </div>
          </div>
          <br>
          <button onclick="AbrirModalNuevoDestinoDeImpresion()" type="button" class="btn btn-primary"></i>Habilitar Nuevo Destino De Impresion</button>
          <div class="table-responsive" style="margin-top:10px; padding:5px;">
            <table id="tablaDestinoImpresion" class="display responsive" style="width: 100%">
              <thead>
                <tr>
                <th class="text text-center" style='height:10px'>N° DESTINO DE IMPRESION</th>
                <th class="text text-center">DESTINO DE IMPRESION</th>
                <th  style= 'display:none' class="text text-center">IP</th>
                <th  style= 'display:none' class="text text-center">PUERTO</th>
                <th  style= 'display:none' class="text text-center">RUTA WINDOWS</th>
                <th  style= 'display:none' class="text text-center">IMPRESION GRAFICA</th>
                <th  style= 'display:none' class="text text-center">PERMITE IMPRIMIR LOGO</th>
                <th  style= 'display:none' class="text text-center">LOGO POS</th>
                <th  style= 'display:none' class="text text-center">SIZE FONT</th>
                <th  style= 'display:none' class="text text-center">SIN IMPRESION</th>
                <th  style= 'display:none' class="text text-center">USAR D. RECINTO</th>
                 <th  style= 'display:none' class="text text-center">PERMITE IMPRESION PRECUENTA</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>