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
    if ($('#nombreFamiliaInsumo').val() == 0 || $('#nombreFamiliaInsumo').val().trim() == '') {
        showAppAlert('error',"No Ingreso El nombre de la Familia de Insumos!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
    } else {
        // Asegurarse que idFamiliaInsumo sea 0 si está vacío
        idFamiliaInsumo = $('#idFamiliaInsumo').val();
        if (idFamiliaInsumo === '' || idFamiliaInsumo === null) {
            idFamiliaInsumo = 0;
        }
        
        NombreFamiliaInsumo = $('#nombreFamiliaInsumo').val().toUpperCase().trim().replace(/[,.'"$#%&]+/g, '');
        
        var miJson = {
            'id_grupo_insumo': parseInt(idFamiliaInsumo) || 0, // Asegurar que sea número
            'nombre_grupo_insumo': NombreFamiliaInsumo
        };
        
        console.log("Datos a enviar:", miJson);
        
        $.post(base_url + "index.php/FamiliasInsumos/grabarNuevaFamiliaInsumo", {
            'prod': miJson
        }).done(function(response) {
            console.log("Respuesta recibida:", response);
            
            // Parsear la respuesta
            var respuesta = typeof response === 'string' ? JSON.parse(response) : response;
            
            console.log("Respuesta parseada:", respuesta);
            
            if (respuesta.success && respuesta.id_grupo_insumo) {
                showAppAlert('success', "Se registro Exitosamente La Nueva Familia Insumos!", {
                    duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
                
                $('#ModalNuevaFamiliaInsumo').modal('hide');
                
                // Recargar el combo de familias en el modal de insumos
                if (typeof llenarGrupoInsumos === 'function') {
                    // Esperar un momento antes de recargar
                    setTimeout(function() {
                        llenarGrupoInsumos('', respuesta.id_grupo_insumo);
                    }, 300);
                }
                
                // Recargar tabla si existe
                if (typeof tablaFamiliasInsumo !== 'undefined') {
                    tablaFamiliasInsumo.ajax.reload();
                }
            } else {
                showAppAlert('error', respuesta.message || "Error al grabar la familia de insumos", {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                });
            }
        })
        .fail(function(xhr, status, error) {
            console.error("Error en la petición:", status, error);
            console.error("Respuesta:", xhr.responseText);
            showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        });
    }
}
</script>
