<div id="ModalNuevaSubFamilia" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO SUB FAMILIA
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1" class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="idSubFamilia">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idSubFamilia" id="idSubFamilia" requerido="no" value="">
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <label for="cboIdFamilia">FAMILIA *</label>
                                <select id="cboIdFamilia" nombre="cboIdFamilia" class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                            <div class="col-md-6 ">
                                <label for="nombreSubFamilia">SUB FAMILIA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreSubFamilia" id="nombreSubFamilia" requerido="si" value="" placeholder="Nombre SubFamilia" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaSubFamilia()" type="button" class="btn btn-success"><i class="fa fa-check-square-o"></i>GRABAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<script>
    //NE SUBFAMILIA////////////////////
    function AbrirModalNuevaSubFamilia() {
        $('#idSubFamilia').val("");
        $('#nombreSubFamilia').val("");
        ListadoDeFamilias("");
        $('#ModalNuevaSubFamilia').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function GrabarNuevaSubFamilia() {
        if ($('#nombreSubFamilia').val() == 0) {
            showAppAlert('error',"- No Ingreso El Nombre De La Sub Familia!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        }
        else if ($('#cboIdFamilia').val() == null) {
            showAppAlert('error',"- No Selecciono La Familia!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        } else {
            //grabar datos
            IdSubFamilia = $('#idSubFamilia').val();
            NombreSubFamilia = $('#nombreSubFamilia').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            Familia = $('#cboIdFamilia').val();

            var miJson = {
                'id_subfamilia': IdSubFamilia,
                'nombre_subfamilia': NombreSubFamilia,
                'id_familia': Familia
            };
            $.post(base_url + "index.php/SubFamilias/grabarNuevaSubFamilia", {
                    'prod': miJson
                }).done(function() {
                    showAppAlert('success', "Se registro Exitosamente La Nueva Sub Familia!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    $('#ModalNuevaSubFamilia').modal('hide');
                    tablaSubFamilia.ajax.reload();
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

    function ListadoDeFamilias(objSelected) {
        $.post(base_url + "index.php/ListaDePrecios/ListadoDeFamilias", {
            id: ""
        }, function(datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = '<option selected disabled value="0">Seleccione La Familia</option>';
            $.each(obj, function(index, v) { // recorro el array
                if (v.nombre_familia == objSelected) {
                    tbl = tbl + '<option selected value="' + v.id_familia + '">' + v.nombre_familia + '</option>';
                } else {
                    tbl = tbl + '<option value="' + v.id_familia + '">' + v.nombre_familia + '</option>';
                }
            }); //fin each
            $("#cboIdFamilia").html(tbl);
            $("#cboIdFamilia").select2();
        });
    }
</script>
