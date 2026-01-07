<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaFamiliaInsumo" class="modal fade" role="dialog" style="overflow-y: scroll;">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- TITULO  MODAL  -->
      <div id="tituloModalUsuario" class="modal-header btn-success">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title text text-center">REGISTRO FAMILIAS INSUMOS
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
                <label for="idFamiliaInsumo">ID</label>
                <input type="number" disabled class="form-control input-sm input-sm" name="idFamiliaInsumo" id="idFamiliaInsumo" requerido="no" value="" />
              </div>
              <div class="col-md-10">
                <label for="nombreFamiliaInsumo">NOMBRE FAMILIA INSUMO *</label>
                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreFamiliaInsumo" id="nombreFamiliaInsumo" requerido="si" value="" placeholder="Nombre Familia Insumo" />
              </div>
            </fieldset>
            <!-- FIN CAMPOS DE REGISTRO  -->
          </div>
        </div>
      </div>
      <!-- CONTENIDO  MODAL  -->

      <!-- BOTONES  -->
      <div class="modal-footer">
        <button onclick="GrabarNuevaFamiliaInsumo()" type="button" class="btn btn-primary"><i class=""></i>GRABAR</button>
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>SALIR</button>
      </div>
      <!-- FIN BOTONES  -->
    </div>
  </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
  function AbrirModalNuevaFamiliaInsumo() {
    $('#idFamiliaInsumo').val("");
    $('#nombreFamiliaInsumo').val("");
    $('#ModalNuevaFamiliaInsumo').modal({
      backdrop: 'static',
      keyboard: false
    });
  }

  function GrabarNuevaFamiliaInsumo() {
    if ($('#nombreFamiliaInsumo').val() == 0) {
      showAppAlert('error',"No Ingreso El nombre de la Familia de Insumos!", {
        duration: 5000,
        image: base_url+ 'assets/img/errorGif.gif'
      });
    } else {
      idFamiliaInsumo = $('#idFamiliaInsumo').val();
      NombreFamiliaInsumo = $('#nombreFamiliaInsumo').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
      var miJson = {
        'id_grupo_insumo': idFamiliaInsumo,
        'nombre_grupo_insumo': NombreFamiliaInsumo
      };
      console.log(miJson);
      $.post(base_url + "index.php/FamiliasInsumos/grabarNuevaFamiliaInsumo", {
          'prod': miJson
        }).done(function() {
          showAppAlert('success', "Se registro Exitosamente La Nueva Familia Insumos!", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
          });
          $('#ModalNuevaFamiliaInsumo').modal('hide');
          if (typeof ListarFamiliasInsumo === 'function') {
					  ListarFamiliasInsumo("");
						
					  try {
						  var nuevaFamiliaInsumo = JSON.parse(response);
						  if (nuevaFamiliaInsumo.id_grupo_insumo) {
							  setTimeout(function() {
								  $('#cboGrupoInsumo').val(nuevaFamiliaInsumo.id_grupo_insumo).trigger('change');
							  }, 500);
						  }
					  } catch(e) {
						  console.log("No se pudo seleccionar automáticamente la nueva familia de insumos");
					  }
				  }

          if (typeof tablaFamiliasInsumo !== 'undefined') {
            tablaFamiliasInsumo.ajax.reload();
          }
        })
        .fail(function() {
          showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
          });
        })
        .always(function() {});
    }
  }
</script>
