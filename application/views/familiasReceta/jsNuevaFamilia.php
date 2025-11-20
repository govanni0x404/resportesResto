<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaFamiliaReceta" class="modal fade" role="dialog" style="overflow-y: scroll;">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- TITULO  MODAL  -->
      <div id="tituloModalUsuario" class="modal-header btn-success">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title text text-center">REGISTRO FAMILIAS RECETA
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
                <label for="idFamiliaReceta">ID</label>
                <input type="number" disabled class="form-control input-sm input-sm" name="idFamiliaReceta" id="idFamiliaReceta" requerido="no" value="" />
              </div>
              <div class="col-md-6 ">
                <label for="nombreFamiliaReceta">NOMBRE FAMILIA RECETA *</label>
                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreFamiliaReceta" id="nombreFamiliaReceta" requerido="si" value="" placeholder="Nombre Familia Receta" />
              </div>
            </fieldset>
            <!-- FIN CAMPOS DE REGISTRO  -->
          </div>
        </div>
      </div>
      <!-- CONTENIDO  MODAL  -->

      <!-- BOTONES  -->
      <div class="modal-footer">
        <button onclick="GrabarNuevaFamiliaReceta()" type="button" class="btn btn-success"><i class="fa fa-check-square-o"></i>GRABAR</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
      </div>
      <!-- FIN BOTONES  -->
    </div>
  </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
  function AbrirModalNuevaFamiliaReceta() {
    $('#idFamiliaReceta').val("");
    $('#nombreFamiliaReceta').val("");
    $('#ModalNuevaFamiliaReceta').modal({
      backdrop: 'static',
      keyboard: false
    });
  }

  function GrabarNuevaFamiliaReceta() {
    if ($('#nombreFamiliaReceta').val() == 0) {
      swal("ERROR!", "No Ingreso El Nombre De La Familia Receta!", "error");
    } else {
      idFamiliaReceta = $('#idFamiliaReceta').val();
      NombreFamiliaReceta = $('#nombreFamiliaReceta').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
      var miJson = {
        'id_familia_receta': idFamiliaReceta,
        'nombre_familia_receta': NombreFamiliaReceta
      };
      console.log(miJson);
      $.post(base_url + "index.php/FamiliasReceta/grabarNuevaFamiliaReceta", {
          'prod': miJson
        }).done(function() {
          swal("Listo", "Se registro Exitosamente La Nueva Familia Receta", "success");
          $('#ModalNuevaFamiliaReceta').modal('hide');
          tablaFamiliasReceta.ajax.reload();
        })
        .fail(function() {
          swal("Se produjo un error al realizar el proceso, favor contacte a su administrador", "error");
        })
        .always(function() {});
    }
  }
</script>
