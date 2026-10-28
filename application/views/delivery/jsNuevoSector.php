<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoSector" class="modal fade t-form-modal" role="dialog" style="overflow-y: scroll;">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- TITULO  MODAL  -->
      <div id="tituloModalUsuario" class="modal-header btn-success">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title text text-center">REGISTRO SECTORES DELIVERY
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
              <div class="col-sm-3">
                <label for="idSector">ID</label>
                <input type="number" disabled class="form-control input-sm input-sm" name="idSector" id="idSector" requerido="no" value="" />
              </div>
              <div class="col-sm-9">
                <label for="nombreSector">NOMBRE SECTOR *</label>
                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreSector" id="nombreSector" requerido="si" value="" placeholder="Nombre Sector" />
              </div>
            </fieldset>
            <!-- FIN CAMPOS DE REGISTRO  -->
          </div>
        </div>
      </div>
      <!-- CONTENIDO  MODAL  -->

      <!-- BOTONES  -->
      <div class="modal-footer">
        <button onclick="GrabarNuevoSector()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Grabar</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
      </div>
      <!-- FIN BOTONES  -->
    </div>
  </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
  function AbrirModalNuevoSector() {
    $('#idSector').val("");
    $('#nombreSector').val("");
    $('#ModalNuevoSector').modal({
      backdrop: 'static',
      keyboard: false
    });
  }

  function GrabarNuevoSector() {
    if ($('#nombreSector').val() == 0) {
      showAppAlert('error',"No Ingreso El Nombre Del Sector!", {
        duration: 5000,
        image: base_url+ 'assets/img/errorGif.gif'
      });
    } else {
      idSector = $('#idSector').val();
      NombreSector = $('#nombreSector').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
      var miJson = {
        'id_sector': idSector,
        'nombre_sector': NombreSector
      };
      console.log(miJson);
      $.post(base_url + "index.php/Delivery/grabarNuevoSector", {
          'prod': miJson
        }).done(function() {
          showAppAlert('success', "Se registro Exitosamente El Nuevo Sector!", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
          });
          $('#ModalNuevoSector').modal('hide');
          tablaSector.ajax.reload();
        })
        .fail(function() {
          showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
          });
        })
        .always(function() {});
    }
  }
</script>
