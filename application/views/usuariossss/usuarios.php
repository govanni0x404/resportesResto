<div class="content-header">
  <div class="header-icon">
    <i class="pe-7s-home"></i>
  </div>
  <div class="header-title">
    <h1>Usuarios</h1>
    <small id="caja"></small>
    <ol class="breadcrumb">
      <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Configuracion</a></li>
      <li class="active">Usuarios</li>
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
            <h4>Control De Usuarios</h4>
          </div>
        </div>
        <br>
        <button onclick="crearNuevoUsuario()" type="button" class="btn btn-success"></i>Habilitar Nuevo Usuario</button>

        <div class="table-responsive" style="margin-top:10px; padding:5px;">
          <table id="tableUsuarios" class="display responsive" style="width: 100%">
            <thead>
              <tr>
                <th class="text text-right">ID</th>
                <th class="text text-center">USUARIO</th>
                <th class="text text-center">CLAVE</th>
                <th class="text text-center">ES ADMIN</th>
                <th class="text text-center">GARZON</th>
                <th class="text text-center">ROL</th>
                <th class="text text-center">VIGENTE</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>



      <!--  MODAL NUEVO REGISTRO  -->
      <div id="ModalUsuario" class="modal fade" role="dialog" style="overflow-y: scroll;">
        <div class="modal-dialog">
          <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalUsuario" class="modal-header btn-success">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title text text-center">REGISTRO USUARIOS
              </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
              <div class="h-panel">
                <ul id="myTab" class="nav nav-with title font">
                  <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1" class="text color1">USUARIOS</a></li>

                </ul>
                <div class="tab-pane fade active in" id="tabDatosUsuarios">
                  <!-- CAMPOS DE REGISTRO  -->
                  <fieldset>
                    <div class="col-md-6 ">
                      <label for="idUsuario">ID</label>
                      <input type="number" disabled class="form-control input-sm input-sm" name="idUsuario" id="idUsuario" requerido="no" value="">
                    </div>
                    <div class="col-md-6 ">
                      <label for="nombreUsuario">NOMBRE USUARIO *</label>
                      <input type="text" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreUsuario" id="nombreUsuario" requerido="si" value="" placeholder="Nombre Usuario" />
                    </div>
                  </fieldset>
                  <fieldset>
                    <div class="col-md-6">
                      <label for="CboRol">ROL DEL USUARIO *</label>
                      <select style="width:100%;" id="CboRol" nombre="CboRol" class="form-control input-sm" requerido="si">
                        <option value="">--Seleccione--</option>
                      </select>
                    </div>
                    <div class="col-md-4 ">
                      <label for="claveUsuario">CONTRASEÑA *</label>
                      <input type="password" class="form-control input-sm input-sm" maxlength='20' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="claveUsuario" id="claveUsuario" requerido="si" value="" placeholder="Contraseña" />
                    </div>

                    <div class="col-md-2">
                      <div class="btn-group" role="group" aria-label="...">
                        <div class="checkbox checkbox-success">
                          <br>
                          <input id="ckMostrarClaveUsuario" type="checkbox">
                          <label for="ckMostrarClaveUsuario">
                            Mostrar
                          </label>
                        </div>
                      </div>
                    </div>
                  </fieldset>
                  <fieldset>
                    <div class="col-md-12 ">
                      <label for="correoAccesoWeb">CORREO ACCESO WEB *</label>
                      <input type="text" class="form-control input-sm input-sm" maxlength='28' onpaste="return false;" ondrop="return false;" autocomplete="off" name="correoAccesoWeb" id="correoAccesoWeb" requerido="si" value="" placeholder="Ingrese El Correo De Acceso Para El Portal" />
                    </div>
                  </fieldset>

                  <fieldset>

                    <div class="col-md-3">
                      <div class="btn-group" role="group" aria-label="...">
                        <div class="checkbox checkbox-success">
                          <br>
                          <input id="ckEsAdmin" type="checkbox">
                          <label for="ckEsAdmin">
                            Es Admin
                          </label>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-3">
                      <div class="btn-group" role="group" aria-label="...">
                        <div class="checkbox checkbox-success">
                          <br>
                          <input id="ckEsGarzon" type="checkbox">
                          <label for="ckEsGarzon">
                            Es Garzon
                          </label>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-3">
                      <div class="btn-group" role="group" aria-label="...">
                        <div class="checkbox checkbox-success">
                          <br>
                          <input id="ckVigente" type="checkbox">
                          <label for="ckVigente">
                            Vigente
                          </label>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-3">
                      <div class="btn-group" role="group" aria-label="...">
                        <div class="checkbox checkbox-success">
                          <br>
                          <input id="ckAutorizaCambioConexion" type="checkbox">
                          <label for="ckAutorizaCambioConexion">
                            Autoriza Cambio Conexion
                          </label>
                        </div>
                      </div>
                    </div>
                  </fieldset>
                  <!-- FIN CAMPOS DE REGISTRO  -->
                </div>
              </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
              <button type="button" class="btn btn-success" id="btnGrabarUsuario"><i class="fa fa-check-square-o"></i>GRABAR</button>
              <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
          </div>
        </div>
      </div>
      <!-- FIN  MODAL NUEVO REGISTRO  -->