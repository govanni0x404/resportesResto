<!--  MODAL ASIGNAR MESA A RECINTO  -->
<div id="ModalAsignarMesa" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalAsignar" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">ASIGNAR MESA A RECINTO
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <div class="tab-pane fade active in">
                        <!-- CAMPOS DE ASIGNACIÓN  -->
                        <fieldset>
                            <div class="col-md-6">
                                <label for="idMesaAsignar">ID MESA</label>
                                <input type="number" disabled class="form-control input-sm" name="idMesaAsignar" id="idMesaAsignar" value="" />
                            </div>
                            <div class="col-md-12">
                                <label for="recintoSelect">SELECCIONAR RECINTO *</label>
                                <select class="form-control input-sm" name="recintoSelect" id="recintoSelect" requerido="si">
                                    <option value="0">-- Seleccione un Recinto --</option>
                                </select>
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE ASIGNACIÓN  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="asignarMesa()" type="button" class="btn btn-primary"><i class=""></i> ASIGNAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL ASIGNAR MESA A RECINTO  -->

<script>
    function cargarRecintos() {
        $.post(base_url + "index.php/MesasPorRecinto/ListadoDeRecintos")
            .done(function(response) {
                var recintos = JSON.parse(response);
                var select = $('#recintoSelect');
                select.empty();
                select.append('<option value="0">-- Seleccione un Recinto --</option>');
                
                recintos.forEach(function(recinto) {
                    select.append('<option value="' + recinto.id_recinto + '">' + recinto.nombre_recinto + '</option>');
                });
            })
            .fail(function() {
                showAppAlert('error', "Error al cargar los recintos", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
            });
    }

    function asignarMesa() {
        var idMesa = $('#idMesaAsignar').val();
        var idRecinto = $('#recintoSelect').val();
        
        if (idRecinto == "0" || idRecinto == "" || idRecinto == null) {
            showAppAlert('error', "Debe seleccionar un recinto!", {
                duration: 5000,
                image: base_url + 'assets/img/errorGif.gif'
            });
            return;
        }

        var miJson = {
            'id_mesa': idMesa,
            'id_recinto': idRecinto
        };

        console.log('Datos a enviar:', miJson);
        
        $.post(base_url + "index.php/MesasPorRecinto/asignarMesaARecinto", {
                'prod': miJson
            }).done(function(response) {
                console.log('Respuesta del servidor:', response);
                showAppAlert('success', "Mesa asignada exitosamente al recinto!", {
                    duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
                $('#ModalAsignarMesa').modal('hide');
                tablaMesasSinAsignar.ajax.reload();
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Error:', textStatus, errorThrown);
                showAppAlert('error', "Se produjo un error al realizar el proceso", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
            });
    }

    // Cargar recintos al abrir el modal
    $('#ModalAsignarMesa').on('show.bs.modal', function() {
        cargarRecintos();
    });
</script>