<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoItemGastoMensual" class="modal fade" role="dialog" style="overflow-y: scroll;">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- TITULO  MODAL  -->
      <div id="tituloModalUsuario" class="modal-header btn-success">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title text text-center">REGISTRO DE ITEM GASTOS MENSUALES
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
                <label for="idItemGastoMensual">ID</label>
                <input type="number" disabled class="form-control input-sm input-sm" name="idItemGastoMensual" id="idItemGastoMensual" requerido="no" value="" />
              </div>
              <div class="col-md-12 ">
                <label for="nombreItemGastoMensual">NOMBRE ITEM GASTO MENSUAL *</label>
                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreItemGastoMensual" id="nombreItemGastoMensual" requerido="si" value="" placeholder="Nombre Item Gasto Mensual" />
              </div>
            </fieldset>
            <fieldset>
              <div class="col-md-6">
                <div class="btn-group" role="group" aria-label="...">
                  <div class="checkbox checkbox-success">
                    <br>
                    <input id="ckVigente" type="checkbox">
                    <label for="ckVigente">
                      VIGENTE
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
        <button onclick="grabarNuevoItemGastoMensual()" type="button" class="btn btn-primary"><i class="fa fa-check-square-o"></i>GRABAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
      </div>
      <!-- FIN BOTONES  -->
    </div>
  </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
  function AbrirModalNuevoItemGastoMensual() {
    $('#idItemGastoMensual').val("");
    $('#nombreItemGastoMensual').val("");
    $('#ckVigente').prop('checked', true);
    $('#ModalNuevoItemGastoMensual').modal({
      backdrop: 'static',
      keyboard: false
    });
  }

  function grabarNuevoItemGastoMensual() {
    if ($('#nombreItemGastoMensual').val() == 0) {
      showAppAlert('error',"- No Ingreso El Nombre Del Item Gasto Mensual!", {
        duration: 5000,
        image: base_url+ 'assets/img/errorGif.gif'
      });
    } else {
      idItemGastoMensual = $('#idItemGastoMensual').val();
      nombreItemGastoMensual = $('#nombreItemGastoMensual').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
      if ($('#ckVigente').is(":checked")) {
        var Vigente = 1
      } else {
        var Vigente = 0
      }
      var miJson = {
        'id_item_gastos': idItemGastoMensual,
        'nombre_item_gastos': nombreItemGastoMensual,
        'vigente': Vigente,
      };
      console.log(miJson);
      $.post(base_url + "index.php/ItemGastosMensuales/grabarNuevoItemGastoMensual", {
          'prod': miJson
        }).done(function() {
          showAppAlert('success', "Se registro Exitosamente El Nuevo Item Gasto Mensual!", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
          });
          $('#ModalNuevoItemGastoMensual').modal('hide');
          tablaItemGastosMensuales.ajax.reload();
        })
        .fail(function() {
          showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
          });
        })
        .always(function() {});
    }
  }
</script>
