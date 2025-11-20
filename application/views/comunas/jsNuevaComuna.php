<div id="ModalNuevaComuna" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO COMUNAS
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
                                <label for="idComuna">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idComuna" id="idComuna" requerido="no" value="">
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="nombreComuna">COMUNA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreComuna" id="nombreComuna" requerido="si" value="" placeholder="Nombre Comuna" />
                            </div>
                            <div class="col-md-6">
                                <label for="cboIdProvincia">PROVINCIA *</label>
                                <select id="cboIdProvincia" nombre="cboIdProvincia" class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="grabarNuevaComuna()" type="button" class="btn btn-success"><i class="fa fa-check-square-o"></i>GRABAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<script>
    function AbrirModalNuevaComuna() {
        $('#idComuna').val("");
        $('#nombreComuna').val("");
        ListadoProvincias("");
        $('#ModalNuevaComuna').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function grabarNuevaComuna() {
        if ($('#nombreComuna').val() === '') {
            swal("ERROR!", "No Ingreso El Nombre De La Comuna!", "error");
        }else if ($('#cboIdProvincia').val() == 0) {
            swal("ERROR!", "No Selecciono La Provincia!", "error");
        } else {
            IdComuna = $('#idComuna').val();
            NombreComuna = $('#nombreComuna').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            IdProvincia = $('#cboIdProvincia').val();
            var miJson = {
                'id_comuna': IdComuna,
                'nombre_comuna': NombreComuna,
                'id_provincia': IdProvincia
            };
            $.post(base_url + "index.php/Comuna/grabarNuevaComuna", {
                    'prod': miJson
                }).done(function() {
                    swal("Listo", "Se registro Exitosamente La Nueva Comuna", "success");
                    $('#ModalNuevaComuna').modal('hide');
                    tablaComuna.ajax.reload();
                })
                .fail(function() {
                    swal("Se produjo un error al realizar el proceso, favor contacte a su administrador");
                })
                .always(function() {});
        }
    }

    function ListadoProvincias(objSelected) {
        $.post(base_url + "index.php/Provincias/ListadoProvincias", {
            id: ""
        }, function(datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = '<option selected value="0">Seleccione La Provincia</option>';
            $.each(obj, function(index, v) { // recorro el array
                if (v.nombre_provincia == objSelected) {
                    tbl = tbl + '<option selected value="' + v.id_provincia + '">' + v.nombre_provincia + '</option>';
                } else {
                    tbl = tbl + '<option value="' + v.id_provincia + '">' + v.nombre_provincia + '</option>';
                }
            }); //fin each
            $("#cboIdProvincia").html(tbl);
            $("#cboIdProvincia").select2();
        });
    }
</script>
