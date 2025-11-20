<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoTurno" class="modal fade" role="dialog" style="overflow-y: scroll;">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- TITULO  MODAL  -->
      <div id="tituloModalUsuario" class="modal-header btn-success">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title text text-center">REGISTRO TURNOS
        </h4>
      </div>
      <!-- FIN TITULO  MODAL  -->

      <!-- CONTENIDO  MODAL  -->
      <div class="modal-body">
        <div class="h-panel">
          <ul id="myTab" class="nav nav-with title font">
            <li class="active"><a href="#tabBodegas" data-toggle="tab" id="tab1" class="text color1"></a></li>
          </ul>
          <div class="tab-pane fade active in" id="tabBodegas">
            <!-- CAMPOS DE REGISTRO  -->
            <fieldset>
              <div class="col-md-6 ">
                <label for="idTurnos">ID</label>
                <input type="number" disabled class="form-control input-sm input-sm" name="idTurnos" id="idTurnos" requerido="no" value="" />
              </div>
              <div class="col-md-6 ">
                <label for="nombreTurno">NOMBRE TURNO *</label>
                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreTurno" id="nombreTurno" requerido="si" value="" placeholder="Nombre Turno" />
              </div>
            </fieldset>
            <fieldset>
              <div class="col-md-4">
                <div class="btn-group" role="group" aria-label="...">
                  <div class="checkbox checkbox-success">
                    <br>
                    <input id="ckCierreTurno" type="checkbox">
                    <label for="ckCierreTurno">
                      CIERRE TURNO
                    </label>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="btn-group" role="group" aria-label="...">
                  <div class="checkbox checkbox-success">
                    <br>
                    <input id="ckInformeZ" type="checkbox">
                    <label for="ckInformeZ">
                      INFORME Z
                    </label>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="btn-group" role="group" aria-label="...">
                  <div class="checkbox checkbox-success">
                    <br>
                    <input id="ckImprimirCierreConMesasAbiertas" type="checkbox">
                    <label for="ckImprimirCierreConMesasAbiertas">
                      IMPRIMIR CIERRE CON MESAS ABIERTAS
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
        <button onclick="grabarNuevoTurno()" type="button" class="btn btn-success"><i class="fa fa-check-square-o"></i>GRABAR</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
      </div>
      <!-- FIN BOTONES  -->
    </div>
  </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
  function AbrirModalNuevoTurno() {
    $('#idTurnos').val("");
    $('#nombreTurno').val("");
    $('#ckCierreTurno').prop('checked', false);
    $('#ckInformeZ').prop('checked', false);
    $('#ckImprimirCierreConMesasAbiertas').prop('checked', false);
    $('#ModalNuevoTurno').modal({
      backdrop: 'static',
      keyboard: false
    });
  }

  function grabarNuevoTurno() {
    if ($('#nombreTurno').val() == 0) {
      swal("ERROR!", "No Ingreso El Nombre Del Turno!", "error");
    } else {
      idTurno = $('#idTurnos').val();
      NombreTurno = $('#nombreTurno').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
      if ($('#ckCierreTurno').is(":checked")) {
        var cierreTurno = 1
      } else {
        var cierreTurno = 0
      }
      if ($('#ckInformeZ').is(":checked")) {
        var informeZ = 1
      } else {
        var informeZ = 0
      }
      if ($('#ckImprimirCierreConMesasAbiertas').is(":checked")) {
        var imprimirCierreConMesasAbiertas = 1
      } else {
        var imprimirCierreConMesasAbiertas = 0
      }
      var miJson = {
        'id_turno': idTurno,
        'nombre_turno': NombreTurno,
        'cierre_turno': cierreTurno,
        'informe_z': informeZ,
        'impedir_cierre_con_mesas_abiertas': imprimirCierreConMesasAbiertas,
      };
      console.log(miJson);
      $.post(base_url + "index.php/Turnos/grabarNuevoTurno", {
          'prod': miJson
        }).done(function() {
          swal("Listo", "Se registro Exitosamente El Nuevo Turno", "success");
          $('#ModalNuevoTurno').modal('hide');
          tablaTurnos.ajax.reload();
        })
        .fail(function() {
          swal("Se produjo un error al realizar el proceso, favor contacte a su administrador", "error");
        })
        .always(function() {});
    }
  }
</script>
