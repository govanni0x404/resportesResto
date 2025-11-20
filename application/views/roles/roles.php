<div class="content-header">
  <div class="header-icon">
    <i class="pe-7s-home"></i>
  </div>
  <div class="header-title">
    <h1>Roles</h1>
    <small id="caja"></small>
    <ol class="breadcrumb">
      <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Configuracion</a></li>
      <li class="active">Roles</li>
    </ol>
  </div>
</div> <!-- /.Content Header (Page header) -->


<div class="container-padding">
  <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
      <div class="panel-body">

        <div class="panel panel-success lobidisable">
          <div class="panel-heading">
            <div class="panel-title">
              <i class="pe-7s-cash"></i>
              <h4>Roles De Usuarios</h4>
            </div>
          </div>
          <br>
          <button onclick="crearNuevoRol()" type="button" class="btn btn-success"></i>Habilitar Nuevo Rol</button>
          <div class="table-responsive" style="margin-top:10px; padding:5px;">
            <table id="tableRoles" class="display responsive" style="width: 100%">
              <thead>
                <tr>
                  <th class="text text-right" style='height:10px'>ID</th>
                  <th class="text text-center" style='height:45px'>ROL</th>

                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>



      <div id="modalPrivilegiosRoles" class="modal fade" role="dialog" style="overflow-y: scroll;">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 id="nombreCliente" class="modal-title text text-center">ACESSO A MODULOS DEL SISTEMA</h4>
            </div>

            <div class="modal-body">
              <div class="container-widget">
                <div class="row">
                  <div class="col-md-12">
                    <div class="panel panel-default">
                      <div id="tituloPanel" class="panel-title">
                      </div>
                      <br>
                      <div class="write" style="width:100%">
                        <div class="col-md-6-t" style="max-height:650px; height:auto; width:100%; overflow:auto;">
                          <table id="tableDetalleRol" class="table table-hover table-bordered">
                            <thead>
                              <tr>
                                <th class='text-center' style='display:none'>ID DETALLE ROL</th>
                                <th class='text-center' style='display:none'>ID ROL</th>
                                <th class='text-center' style='display:none'>ROL</th>
                                <th class='text-center' style='display:none'>ID PSISTEMA</th>
                                <th class='text-center' style="padding: 12px;">SISTEMA</th>
                                <th class='text-center' style="padding: 12px;">MODULO SISTEMA</th>
                                <th class='text-center'>PERMISO</th>
                              </tr>
                            </thead>
                            <tbody id="detalleRol">
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- FIN CONTENIDO MODAL -->

            <!-- BOTONES -->
            <div class="modal-footer">
              <button type='button' class='btn btn-primary text-center' id='btnHabilitarAccesoAtodosLosModulos'><i class='fa fa-check-circle'></i> HABILITAR ACCESO A TODOS LOS MODULOS</button>
              <button type='button' class='btn btn-danger text-center' id='btnBloquearaccesoAtodosLosModulos'><i class='fa fa-times-circle'></i>BLOQUEAR ACCESO A TODOS LOS MODULOS</button>
              <button type="button" class="btn btn-success" data-dismiss="modal"><i class="fa fa-times-circle"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES -->
          </div>
        </div>
      </div>


      <!--  MODAL NUEVO REGISTRO  -->
      <div id="ModalNuevoRol" class="modal fade" role="dialog" style="overflow-y: scroll;">
        <div class="modal-dialog">
          <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalUsuario" class="modal-header btn-success">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title text text-center">REGISTRO DE ROLES
              </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
              <div class="h-panel">
                <ul id="myTab" class="nav nav-with title font">
                  <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1" class="text color1">ROLES</a></li>
                </ul>
                <div class="tab-pane fade active in" id="tabDatosUsuarios">
                  <!-- CAMPOS DE REGISTRO  -->
                  <fieldset>
                    <div class="col-md-6 ">
                      <label for="idRoles">ID</label>
                      <input type="number" disabled class="form-control input-sm input-sm" name="idRoles" id="idRoles" requerido="no" value="">
                    </div>
                    <div class="col-md-6 ">
                      <label for="nombreRol">NOMBRE ROL *</label>
                      <input type="text" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreRol" id="nombreRol" requerido="si" value="" placeholder="Nombre Rol" />
                    </div>
                  </fieldset>
                  <!-- FIN CAMPOS DE REGISTRO  -->
                </div>
              </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
              <button onclick="GrabarRol()" type="button" class="btn btn-success" id="btnGrabarRol"><i class="fa fa-check-square-o"></i>GRABAR</button>
              <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
          </div>
        </div>
      </div>
      <!-- FIN  MODAL NUEVO REGISTRO  -->