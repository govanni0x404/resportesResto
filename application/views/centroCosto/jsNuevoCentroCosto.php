<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoCentroCosto" class="modal fade" role="dialog" style="overflow-y: scroll;">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- TITULO  MODAL  -->
      <div id="tituloModalUsuario" class="modal-header btn-success">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title text text-center">REGISTRO DE CENTRO DE COSTOS
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
              <div class="col-md-4 ">
                <label for="idCentroCosto">ID</label>
                <input type="number" disabled class="form-control input-sm input-sm" name="idCentroCosto" id="idCentroCosto" requerido="no" value="" />
              </div>
              <div class="col-md-10 ">
                <label for="nombreCentroCosto">NOMBRE CENTRO COSTO *</label>
                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreCentroCosto" id="nombreCentroCosto" requerido="si" value="" placeholder="Nombre Centro De Costo" />
              </div>
            </fieldset>
            <fieldset>
              <div class="col-md-6">
                <div class="btn-group" role="group" aria-label="...">
                  <div class="checkbox checkbox-success">
                    <br>
                    <input id="ckEsIngreso" type="checkbox">
                    <label for="ckEsIngreso">
                      ES INGRESO
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
        <button onclick="grabarNuevoCentroCosto()" type="button" class="btn btn-primary"><i class=""></i>GRABAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>SALIR</button>
      </div>
      <!-- FIN BOTONES  -->
    </div>
  </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
  function AbrirModalNuevoCentroCosto() {
    $('#idCentroCosto').val("");
    $('#nombreCentroCosto').val("");
    $('#ckEsIngreso').prop('checked', false);
    $('#ModalNuevoCentroCosto').modal({
      backdrop: 'static',
      keyboard: false
    });
  }

  function grabarNuevoCentroCosto() {
    if ($('#nombreCentroCosto').val() == 0) {
      showAppAlert('error',"No Ingreso El Nombre Del Centro De Costo.", {
        duration: 5000,
        image: base_url+ 'assets/img/errorGif.gif'
      });
    } else {
      idCentroCosto = $('#idCentroCosto').val();
      NombreCentroCosto = $('#nombreCentroCosto').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
      if ($('#ckEsIngreso').is(":checked")) {
        var EsIngreso = 1
      } else {
        var EsIngreso = 0
      }
      var miJson = {
        'id_ccosto': idCentroCosto,
        'nombre_ccosto': NombreCentroCosto,
        'esingreso': EsIngreso,
      };
      console.log(miJson);
      $.post(base_url + "index.php/CentroCosto/grabarNuevoCentroCosto", {
          'prod': miJson
        }).done(function() {
          showAppAlert('success', "Se registro Exitosamente El Nuevo Centro De Costo!", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
          });
          $('#ModalNuevoCentroCosto').modal('hide');
          tablaCentroCosto.ajax.reload();
        })
        .fail(function() {
          showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador.", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
          });
        })
        .always(function() {});
    }
  }
</script>
